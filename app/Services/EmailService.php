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
        // Remove configureMailer() call from constructor
    }

    /**
     * Configure PHPMailer settings (called only when sending emails)
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

            // Only validate SMTP credentials if we're actually using SMTP
            $mailer = config('mail.default', 'log');
            if ($mailer === 'smtp') {
                if (!$username || !$password) {
                    throw new \Exception('Email configuration incomplete: username and password are required for SMTP');
                }
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
            // Configure mailer before sending
            $this->configureMailer();

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
            // Configure mailer before sending
            $this->configureMailer();

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
        $userName = htmlspecialchars($user->first_name . ' ' . $user->last_name);
        $sanitizedUrl = htmlspecialchars($verificationUrl);
        $logoUrl = rtrim(config('app.url'), '/') . '/images/SDMDlogo.png';

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Verify Your Email - SDMD Equipment Management System</title>
            <style>
                body {
                    font-family: 'Inter', Arial, sans-serif;
                    line-height: 1.6;
                    color: #f3f4f6;
                    margin: 0;
                    padding: 0;
                    background: linear-gradient(180deg, #1a1f26, #3f4b5d);
                    min-height: 100vh;
                }
                .email-container {
                    max-width: 600px;
                    margin: 0 auto;
                    background: rgba(255, 255, 255, 0.05);
                    border-radius: 12px;
                    overflow: hidden;
                    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
                    backdrop-filter: blur(10px);
                    border: 1px solid rgba(255, 255, 255, 0.1);
                }
                .header {
                    background: linear-gradient(135deg, #1a1f26 0%, #3f4b5d 100%);
                    padding: 40px 20px 30px;
                    text-align: center;
                    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                }
                .header img {
                    max-width: 120px;
                    height: auto;
                    margin-bottom: 15px;
                }
                .header h1 {
                    color: #ffffff;
                    margin: 10px 0 5px;
                    font-size: 24px;
                    font-weight: 600;
                }
                .header p {
                    color: #9ca3af;
                    margin: 0;
                    font-size: 16px;
                    font-weight: 400;
                }
                .content {
                    padding: 30px;
                    background: rgba(26, 31, 38, 0.85);
                    color: #e5e7eb;
                }
                .note {
                    background: rgba(59, 130, 246, 0.1);
                    border-left: 4px solid #3b82f6;
                    padding: 20px;
                    margin: 25px 0;
                    border-radius: 0 8px 8px 0;
                }
                .button {
                    display: inline-block;
                    padding: 14px 32px;
                    background: #3b82f6;
                    color: #ffffff !important;
                    text-decoration: none;
                    border-radius: 8px;
                    font-weight: 600;
                    letter-spacing: 0.03em;
                    transition: background 0.2s ease;
                }
                .button:hover {
                    background: #60a5fa;
                }
                .footer {
                    text-align: center;
                    padding: 25px 20px;
                    font-size: 13px;
                    color: #9ca3af;
                    background: rgba(15, 23, 42, 0.6);
                    border-top: 1px solid rgba(255, 255, 255, 0.05);
                }
                .text-muted {
                    color: #9ca3af;
                }
                .text-center {
                    text-align: center;
                }
                .mt-4 {
                    margin-top: 1rem;
                }
                strong {
                    color: #ffffff;
                    font-weight: 600;
                }
                a {
                    color: #3b82f6;
                    text-decoration: none;
                }
                a:hover {
                    color: #60a5fa;
                    text-decoration: underline;
                }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="header">
                    <img src="{$logoUrl}" alt="SDMD Logo">
                    <h1>SDMD Equipment Management System</h1>
                    <p>Email Verification</p>
                </div>

                <div class="content">
                    <p>Hello <strong>{$userName}</strong>,</p>
                    <p>Your account has been created by an administrator. To activate your account and start using the system, please verify your email address.</p>

                    <div class="note">
                        <p><strong>Click the button below to verify your email:</strong></p>
                        <div class="text-center mt-4">
                            <a href="{$verificationUrl}" class="button">Verify Email Address</a>
                        </div>
                        <p class="text-center text-muted mt-4">This verification link will expire in 24 hours for security reasons.</p>
                    </div>

                    <p>If the button doesn't work, copy and paste this link into your browser:</p>
                    <p class="text-muted">{$sanitizedUrl}</p>

                    <p class="text-muted">If you did not request this account, please ignore this email or contact support if you have any concerns.</p>
                </div>

                <div class="footer">
                    <p>© ' . date('Y') . ' SDMD Equipment Management System. All rights reserved.</p>
                    <p>This is an automated message, please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        HTML;
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
