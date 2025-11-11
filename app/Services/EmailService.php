<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Illuminate\Support\Facades\Log;

class EmailService
{
    protected $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->configureMailer();
    }

    /**
     * Configure PHPMailer settings
     */
    private function configureMailer()
    {
        try {
            // Get configuration values
            $host = config('mail.mailers.smtp.host', 'smtp.gmail.com');
            $username = config('mail.mailers.smtp.username');
            $password = config('mail.mailers.smtp.password');
            $port = config('mail.mailers.smtp.port', 587);

            // Log configuration for debugging
            Log::info('EmailService configuration', [
                'host' => $host,
                'username' => $username ? 'SET' : 'NOT SET',
                'password' => $password ? 'SET' : 'NOT SET',
                'port' => $port
            ]);

            // Validate required configuration
            if (!$username || !$password) {
                throw new \Exception('Email configuration incomplete: username and password are required');
            }

            // Server settings
            $this->mailer->SMTPDebug = SMTP::DEBUG_OFF; // Disable debug output
            $this->mailer->isSMTP();
            $this->mailer->Host = $host;
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $username;
            $this->mailer->Password = $password;
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = $port;

            // Set sender
            $this->mailer->setFrom(
                config('mail.from.address', 'noreply@sdmd.ph'),
                config('mail.from.name', 'SDMD Equipment Management System')
            );

        } catch (Exception $e) {
            Log::error('PHPMailer configuration error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send email verification to user
     */
    public function sendEmailVerification($user, $verificationUrl)
    {
        try {
            // Reset mailer for new email
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

            // Recipients
            $this->mailer->addAddress($user->email, $user->first_name . ' ' . $user->last_name);

            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Verify Your Email - SDMD Equipment Management System';

            $htmlContent = $this->getVerificationEmailTemplate($user, $verificationUrl);
            $this->mailer->Body = $htmlContent;
            $this->mailer->AltBody = strip_tags($htmlContent);

            $this->mailer->send();

            Log::info('Email verification sent successfully', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return true;

        } catch (Exception $e) {
            Log::error('Failed to send email verification', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send welcome email after verification
     */
    public function sendWelcomeEmail($user)
    {
        try {
            // Reset mailer for new email
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

            // Recipients
            $this->mailer->addAddress($user->email, $user->first_name . ' ' . $user->last_name);

            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Welcome to SDMD Equipment Management System!';

            $htmlContent = $this->getWelcomeEmailTemplate($user);
            $this->mailer->Body = $htmlContent;
            $this->mailer->AltBody = strip_tags($htmlContent);

            $this->mailer->send();

            Log::info('Welcome email sent successfully', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return true;

        } catch (Exception $e) {
            Log::error('Failed to send welcome email', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Get email verification HTML template
     */
    private function getVerificationEmailTemplate($user, $verificationUrl)
    {
        return '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Verify Your Email</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .button { display: inline-block; background: #667eea; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; font-weight: bold; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Welcome to SDMD Equipment Management System</h1>
                </div>
                <div class="content">
                    <h2>Hello ' . htmlspecialchars($user->first_name . ' ' . $user->last_name) . '!</h2>

                    <p>Your account has been created by an administrator. To activate your account and start using the system, please verify your email address.</p>

                    <p><strong>Click the button below to verify your email:</strong></p>

                    <a href="' . $verificationUrl . '" class="button">Verify Email Address</a>

                    <p>If the button doesn\'t work, you can also copy and paste this link into your browser:</p>
                    <p><a href="' . $verificationUrl . '">' . $verificationUrl . '</a></p>

                    <p><strong>Important:</strong> This verification link will expire in 24 hours for security reasons.</p>

                    <p>If you did not request this account, please ignore this email.</p>

                    <p>Best regards,<br>SDMD Equipment Management Team</p>
                </div>
                <div class="footer">
                    <p>This is an automated message from SDMD Equipment Management System.<br>
                    Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>';
    }

    /**
     * Get welcome email HTML template
     */
    private function getWelcomeEmailTemplate($user)
    {
        return '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Welcome to SDMD</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Welcome to SDMD Equipment Management System!</h1>
                </div>
                <div class="content">
                    <h2>Congratulations ' . htmlspecialchars($user->first_name . ' ' . $user->last_name) . '!</h2>

                    <p>Your email has been successfully verified and your account is now active!</p>

                    <p>You can now log in to the SDMD Equipment Management System using your credentials:</p>

                    <ul>
                        <li><strong>Email:</strong> ' . htmlspecialchars($user->email) . '</li>
                        <li><strong>Role:</strong> ' . ($user->roles->first() ? $user->roles->first()->name : 'Staff') . '</li>
                    </ul>

                    <p>If you have any questions or need assistance, please contact your system administrator.</p>

                    <p>Best regards,<br>SDMD Equipment Management Team</p>
                </div>
                <div class="footer">
                    <p>This is an automated message from SDMD Equipment Management System.<br>
                    Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>';
    }
}
