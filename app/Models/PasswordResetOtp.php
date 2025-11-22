<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PasswordResetOtp extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'user_id',
        'token',
        'otp',
        'expires_at',
        'is_used',
        'used_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'is_used' => 'boolean',
    ];

    /**
     * Generate a new OTP for password reset
     *
     * @param string      $email
     * @param string|null $token  Existing password broker token (optional)
     * @return array
     */
    public static function createOtp($email, $token = null)
    {
        // Find user by email (optional - for user_id reference)
        $user = \App\Models\User::where('email', $email)->first();

        // Delete any existing OTPs for this email
        self::where('email', $email)->delete();

        // Generate a new OTP (6 digits)
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        // If a broker token is provided, reuse it so OTP and password reset share the same token
        $token = $token ?: Str::random(64);
        $expiresAt = now()->addMinutes(30); // OTP valid for 30 minutes

        // Create the OTP record with the expires_at timestamp
        $otpRecord = self::create([
            'email' => $email,
            'user_id' => $user ? $user->id : null, // Store user_id if user exists
            'token' => $token,
            'otp' => $otp,
            'expires_at' => $expiresAt,
        ]);

        return [
            'token' => $token,
            'otp' => $otp,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Verify if the provided OTP is valid
     *
     * @param string $email
     * @param string $otp
     * @return bool
     */
    public static function verifyOtp($email, $otp)
    {
        $record = self::where('email', $email)
            ->where('otp', $otp)
            ->where('expires_at', '>', now())
            ->where('is_used', false)
            ->first();

        return $record !== null;
    }

    /**
     * Get the token associated with the OTP
     *
     * @param string $email
     * @param string $otp
     * @return string|null
     */
    public static function getTokenFromOtp($email, $otp)
    {
        $record = self::where('email', $email)
            ->where('otp', $otp)
            ->where('expires_at', '>', now())
            ->where('is_used', false)
            ->first();

        return $record ? $record->token : null;
    }

    /**
     * Delete expired OTPs
     *
     * @return int Number of deleted records
     */
    public static function deleteExpired()
    {
        return self::where('expires_at', '<=', now())->delete();
    }

    /**
     * Delete all OTPs for a specific email
     *
     * @param string $email
     * @return int Number of deleted records
     */
    public static function deleteForEmail($email)
    {
        return self::where('email', $email)->delete();
    }

    /**
     * Clean up old and used OTPs
     * 
     * @return void
     */
    public static function cleanup()
    {
        // Delete expired OTPs
        self::deleteExpired();
        
        // Also clean up OTPs older than 1 day as an extra measure
        self::where('created_at', '<=', now()->subDay())->delete();
    }
}
