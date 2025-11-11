<?php
// Debug script to test email configuration
require_once __DIR__ . '/../vendor/autoload.php';

// Load Laravel environment
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Email Configuration Debug ===\n\n";

echo "MAIL_MAILER: " . env('MAIL_MAILER') . "\n";
echo "MAIL_HOST: " . env('MAIL_HOST') . "\n";
echo "MAIL_PORT: " . env('MAIL_PORT') . "\n";
echo "MAIL_USERNAME: " . (env('MAIL_USERNAME') ? 'SET' : 'NOT SET') . "\n";
echo "MAIL_PASSWORD: " . (env('MAIL_PASSWORD') ? 'SET' : 'NOT SET') . "\n";
echo "MAIL_ENCRYPTION: " . env('MAIL_ENCRYPTION') . "\n";
echo "MAIL_FROM_ADDRESS: " . env('MAIL_FROM_ADDRESS') . "\n";
echo "MAIL_FROM_NAME: " . env('MAIL_FROM_NAME') . "\n\n";

echo "=== Config Values ===\n\n";

echo "config('mail.default'): " . config('mail.default') . "\n";
echo "config('mail.mailers.smtp.host'): " . config('mail.mailers.smtp.host') . "\n";
echo "config('mail.mailers.smtp.port'): " . config('mail.mailers.smtp.port') . "\n";
echo "config('mail.mailers.smtp.username'): " . (config('mail.mailers.smtp.username') ? 'SET' : 'NOT SET') . "\n";
echo "config('mail.mailers.smtp.password'): " . (config('mail.mailers.smtp.password') ? 'SET' : 'NOT SET') . "\n";
echo "config('mail.from.address'): " . config('mail.from.address') . "\n";
echo "config('mail.from.name'): " . config('mail.from.name') . "\n\n";

echo "=== Testing EmailService ===\n\n";

try {
    $emailService = app(\App\Services\EmailService::class);
    echo "✅ EmailService instantiated successfully\n";
} catch (Exception $e) {
    echo "❌ EmailService failed: " . $e->getMessage() . "\n";
}

try {
    $testToken = 'test-token-123';
    $url = route('email.verify', ['token' => $testToken]);
    echo "✅ Route generated: " . $url . "\n";
} catch (Exception $e) {
    echo "❌ Route generation failed: " . $e->getMessage() . "\n";
}

echo "\n=== Debug Complete ===\n";
