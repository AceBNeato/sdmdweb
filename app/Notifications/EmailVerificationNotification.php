<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationNotification extends Notification
{
    public $verificationUrl;
    public $user;
    public $password;

    /**
     * Create a new notification instance.
     *
     * @param string $verificationUrl
     * @param object $user
     * @param string|null $password
     * @return void
     */
    public function __construct($verificationUrl, $user, $password = null)
    {
        $this->verificationUrl = $verificationUrl;
        $this->user = $user;
        $this->password = $password;
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
        return (new MailMessage)
            ->subject('Verify Your Email - SDMD Equipment Management System')
            ->view('emails.verification', [
                'user' => $this->user,
                'verificationUrl' => $this->verificationUrl,
                'password' => $this->password,
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
