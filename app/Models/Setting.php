<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
        return static::getValue('session_timeout_minutes', 1);
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
}
