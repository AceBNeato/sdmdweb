<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class QrCodeService
{
    const CACHE_TTL = 86400; // 24 hours

    /**
     * Generate QR code with caching
     *
     * @param array $data QR code data payload
     * @param string $size QR code dimensions (e.g., '200x200')
     * @param string $format Output format ('png' or 'svg')
     * @return string|null Path to cached QR code file or null on failure
     */
    public function generateQrCode(array $data, string $size = '200x200', string $format = 'png'): ?string
    {
        $cacheKey = $this->getCacheKey($data, $size, $format);

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

        // Generate via optimized HTTP client
        try {
            $response = Http::qrServer()->get('/create-qr-code', [
                'data' => json_encode($data),
                'size' => $size,
                'format' => $format
            ]);

            if ($response->successful()) {
                // Create unique filename based on data hash
                $fileName = 'qr_' . md5(json_encode($data) . $size . $format) . '.' . $format;
                $path = 'qrcodes/cache/' . $fileName;

                // Store the QR code image
                Storage::disk('public')->put($path, $response->body());

                // Cache the path for future requests
                Cache::put($cacheKey, $path, self::CACHE_TTL);

                Log::info('QR Code generated and cached', [
                    'cache_key' => $cacheKey,
                    'path' => $path,
                    'size' => $size,
                    'format' => $format
                ]);

                return $path;
            } else {
                Log::error('QR Server API returned unsuccessful response', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('QR Code generation failed', [
                'error' => $e->getMessage(),
                'data' => $data,
                'size' => $size,
                'format' => $format
            ]);
        }

        return null;
    }

    /**
     * Generate cache key for QR code data
     */
    private function getCacheKey(array $data, string $size, string $format): string
    {
        return 'qr_code_' . md5(json_encode($data) . $size . $format);
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
