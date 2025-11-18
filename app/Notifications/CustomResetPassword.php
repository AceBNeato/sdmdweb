<?php

namespace App\Notifications;

use App\Models\PasswordResetOtp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomResetPassword extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The password reset token.
     *
     * @var string
     */
    public $token;

    /**
     * The OTP for verification.
     *
     * @var string
     */
    public $otp;

    /**
     * Create a new notification instance.
     *
     * @param string $token
     * @param string $otp
     * @return void
     */
    public function __construct($token, $otp = null)
    {
        $this->token = $token;
        $this->otp = $otp;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $resetUrl = url(route('password.verify.otp', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('SDMD - Password Reset OTP')
            ->view('emails.password-reset', [
                'user' => $notifiable,
                'otp' => $this->otp,
                'resetUrl' => $resetUrl,
                'expiresAt' => now()->addMinutes(30)->format('M d, Y H:i')
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
