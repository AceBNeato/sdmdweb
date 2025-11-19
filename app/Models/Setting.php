<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value', 'type', 'description'];

    protected $casts = [
        'value' => 'string',
    ];

    /**
     * Get a setting value by key
     */
    public static function getValue(string $key, $default = null)
    {
        // Ensure settings table exists
        static::ensureTableExists();

        $setting = static::where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        return match ($setting->type) {
            'integer' => (int) $setting->value,
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    /**
     * Ensure the settings table exists
     */
    private static function ensureTableExists()
    {
        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function ($table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->string('type')->default('string');
                $table->string('description')->nullable();
                $table->timestamps();
            });

            // Insert default session timeout setting
            DB::table('settings')->insert([
                'key' => 'session_timeout_minutes',
                'value' => '1',
                'type' => 'integer',
                'description' => 'Session timeout in minutes for inactivity',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Set a setting value
     */
    public static function setValue(string $key, $value, string $type = 'string', string $description = null): void
    {
        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : (string) $value,
                'type' => $type,
                'description' => $description,
            ]
        );
    }

    /**
     * Get session timeout in minutes
     */
    public static function getSessionTimeoutMinutes(): int
    {
        return static::getValue('session_timeout_minutes', 5);
    }

    /**
     * Get session lockout timeout in minutes (defaults to session timeout if not set)
     */
    public static function getSessionLockoutMinutes(): int
    {
        return static::getValue('session_lockout_minutes', static::getSessionTimeoutMinutes());
    }

    /**
     * Get session lockout timeout in milliseconds for JavaScript
     */
    public static function getSessionLockoutMilliseconds(): int
    {
        return static::getSessionLockoutMinutes() * 60 * 1000;
    }

    /**
     * Retrieve backup configuration settings.
     */
    public static function getBackupSettings(): array
    {
        $days = static::getValue('backup_auto_days', []);

        if (!is_array($days)) {
            $decoded = json_decode((string) $days, true);
            $days = is_array($decoded) ? $decoded : [];
        }

        $normalizedDays = array_values(array_unique(array_map(static function ($day) {
            return strtolower((string) $day);
        }, $days)));

        return [
            'enabled' => (bool) static::getValue('backup_auto_enabled', false),
            'time' => static::getValue('backup_auto_time', '02:00'),
            'days' => $normalizedDays,
            'last_run_at' => static::getValue('backup_last_run_at'),
        ];
    }

    /**
     * Persist backup configuration settings.
     */
    public static function setBackupSettings(bool $enabled, string $time, array $days): void
    {
        $days = array_values(array_unique(array_map(static function ($day) {
            return strtolower((string) $day);
        }, $days)));

        static::setValue(
            'backup_auto_enabled',
            $enabled ? '1' : '0',
            'boolean',
            'Whether automatic database backups are enabled'
        );

        static::setValue(
            'backup_auto_time',
            $time,
            'string',
            'Scheduled time of day for automatic database backups'
        );

        static::setValue(
            'backup_auto_days',
            $days,
            'json',
            'Weekdays when automatic database backups should run'
        );
    }

    /**
     * Determine if backups are scheduled for the provided date.
     */
    public static function isBackupScheduledForDate(?Carbon $date = null): bool
    {
        $date = $date ?? now();
        $settings = static::getBackupSettings();

        if (!$settings['enabled'] || empty($settings['days'])) {
            return false;
        }

        return in_array(strtolower($date->format('l')), $settings['days'], true);
    }

    /**
     * Persist details about the last backup execution time.
     */
    public static function recordBackupRun(?string $timestamp = null): void
    {
        $timestamp = $timestamp ?? now()->toDateTimeString();

        static::setValue(
            'backup_last_run_at',
            $timestamp,
            'string',
            'Timestamp of the latest database backup run'
        );
    }
}
