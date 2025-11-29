<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

echo "=== MAIL DEBUG TEST ===\n";
echo "Default mailer: " . config('mail.default') . "\n";
echo "Mail host: " . config('mail.mailers.smtp.host') . "\n";
echo "Mail port: " . config('mail.mailers.smtp.port') . "\n";
echo "Mail username: " . config('mail.mailers.smtp.username') . "\n";
echo "From address: " . config('mail.from.address') . "\n";
echo "APP_URL: " . config('app.url') . "\n";
echo "\n";

// Test 1: Simple text email
echo "Test 1: Sending simple text email...\n";
try {
    Mail::raw('This is a test email from SDMD system at ' . date('Y-m-d H:i:s'), function ($message) {
        $message->to('giangarcia977@gmail.com')
                ->subject('SDMD Mail Test - Simple Text');
    });
    echo "✓ Simple text email sent successfully\n";
} catch (Exception $e) {
    echo "✗ Simple text email FAILED: " . $e->getMessage() . "\n";
}

// Test 2: HTML email
echo "\nTest 2: Sending HTML email...\n";
try {
    Mail::send(['html' => '<h2>HTML Test</h2><p>This is an <strong>HTML</strong> test email from SDMD.</p>'], [], function ($message) {
        $message->to('giangarcia977@gmail.com')
                ->subject('SDMD Mail Test - HTML');
    });
    echo "✓ HTML email sent successfully\n";
} catch (Exception $e) {
    echo "✗ HTML email FAILED: " . $e->getMessage() . "\n";
}

// Test 3: Using the verification template
echo "\nTest 3: Sending verification template...\n";
try {
    $user = new stdClass();
    $user->first_name = 'Test';
    $user->last_name = 'User';
    $user->email = 'giangarcia977@gmail.com';
    
    Mail::send('emails.verification', [
        'user' => $user,
        'verificationUrl' => 'https://test.example.com/verify/123',
        'password' => 'TestPassword123!'
    ], function ($message) use ($user) {
        $message->to($user->email)
                ->subject('SDMD Mail Test - Verification Template');
    });
    echo "✓ Verification template sent successfully\n";
} catch (Exception $e) {
    echo "✗ Verification template FAILED: " . $e->getMessage() . "\n";
}

echo "\n=== CHECK LOGS ===\n";
echo "Check storage/logs/laravel.log for any mail-related errors\n";
echo "Also check your spam/junk folder in Gmail\n";
echo "\n=== DONE ===\n";
