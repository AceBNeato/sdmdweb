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
        'token',
        'otp',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Generate a new OTP for password reset
     *
     * @param string $email
     * @return array
     */
    public static function createOtp($email)
    {
        // Delete any existing OTPs for this email
        self::where('email', $email)->delete();

        // Generate a new OTP (6 digits)
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $token = Str::random(64);
        $expiresAt = now()->addMinutes(30); // OTP valid for 30 minutes

        // Create the OTP record with the expires_at timestamp
        $otpRecord = self::create([
            'email' => $email,
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
}
