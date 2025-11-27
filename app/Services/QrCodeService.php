<?php

namespace App\Services;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class QrCodeService
{
    const CACHE_TTL = 86400; // 24 hours

    /**
     * Generate QR code with caching using local library
     *
     * @param array $data QR code data payload
     * @param string $size QR code dimensions (e.g., '200x200')
     * @param string $format Output format ('png' or 'svg')
     * @param bool $publicUrl If true, encode a plain HTTPS URL for public scanning
     * @return string|null Path to cached QR code file or null on failure
     */
    public function generateQrCode(array $data, string $size = '200x200', string $format = 'svg', bool $publicUrl = false): ?string
    {
        $cacheKey = $this->getCacheKey($data, $size, $format, $publicUrl);

        // Check cache first
        if (Cache::has($cacheKey)) {
            $cachedPath = Cache::get($cacheKey);
            if (Storage::disk('public')->exists($cachedPath)) {
                Log::info('QR Code served from cache', ['cache_key' => $cacheKey, 'path' => $cachedPath]);
                return $cachedPath;
            } else {
                // Cache exists but file is missing - remove stale cache
                Cache::forget($cacheKey);
                Log::warning('Stale QR cache removed', ['cache_key' => $cacheKey]);
            }
        }

        // Generate locally using endroid/qr-code v6
        try {
            // Parse size
            [$width, $height] = explode('x', $size);
            $width = (int) $width;

            // Choose content: URL for public, JSON for internal
            if ($publicUrl && isset($data['equipment_id'])) {
                $baseUrl = config('app.url');
                // Use APP_URL directly (IP address) without forcing HTTPS
                $qrContent = $baseUrl . '/public/qr-scanner?equipment_id=' . $data['equipment_id'];
            } else {
                $qrContent = json_encode($data);
            }

            // Create QR code with v6 constructor (data as first param)
            $qrCode = new QrCode(
                data: $qrContent,
                size: $width,
                margin: 4
            );

            // Choose writer based on format
            $writer = $format === 'svg' ? new SvgWriter() : new PngWriter();

            // Write the QR code and get string result
            $result = $writer->write($qrCode)->getString();

            // Create unique filename based on data hash
            $fileName = 'qr_' . md5($qrContent . $size . $format) . '.' . $format;
            $path = 'qrcodes/cache/' . $fileName;

            // Store the QR code image
            Storage::disk('public')->put($path, $result);

            // Cache the path for future requests
            Cache::put($cacheKey, $path, self::CACHE_TTL);

            Log::info('QR Code generated locally and cached', [
                'cache_key' => $cacheKey,
                'path' => $path,
                'size' => $size,
                'format' => $format,
                'public_url' => $publicUrl
            ]);

            return $path;

        } catch (\Exception $e) {
            Log::error('QR Code generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data,
                'size' => $size,
                'format' => $format,
                'public_url' => $publicUrl
            ]);
        }

        return null;
    }

    /**
     * Generate cache key for QR code data
     */
    private function getCacheKey(array $data, string $size, string $format, bool $publicUrl = false): string
    {
        $base = json_encode($data) . $size . $format . ($publicUrl ? '_url' : '');
        return 'qr_code_' . md5($base);
    }

    /**
     * Clear all QR code cache
     */
    public function clearCache(): int
    {
        $cleared = 0;
        $keys = Cache::store('file')->getStore()->many(Cache::store('file')->getStore()->keys());

        foreach ($keys as $key => $value) {
            if (str_starts_with($key, 'qr_code_')) {
                Cache::forget($key);
                $cleared++;
            }
        }

        Log::info('QR Code cache cleared', ['entries_cleared' => $cleared]);
        return $cleared;
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        $totalEntries = 0;
        $qrEntries = 0;

        try {
            $keys = Cache::store('file')->getStore()->keys();
            $totalEntries = count($keys);

            foreach ($keys as $key) {
                if (str_starts_with($key, 'qr_code_')) {
                    $qrEntries++;
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to get cache stats', ['error' => $e->getMessage()]);
        }

        return [
            'total_cache_entries' => $totalEntries,
            'qr_cache_entries' => $qrEntries,
            'cache_ttl_seconds' => self::CACHE_TTL,
            'cache_ttl_hours' => self::CACHE_TTL / 3600
        ];
    }
}
