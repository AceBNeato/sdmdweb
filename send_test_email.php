<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Notifications\EmailVerificationNotification;

// Create a dummy user for testing
$testUser = new User();
$testUser->first_name = 'Test';
$testUser->last_name = 'User';
$testUser->email = 'giangarcia977@gmail.com';
$testUser->id = 999999; // dummy ID

// Generate a verification URL
$verificationToken = \Illuminate\Support\Str::random(60);
$verificationUrl = config('app.url') . '/email/verify/' . $verificationToken;

// Send the email
try {
    echo "Sending test email to giangarcia977@gmail.com...\n";
    echo "Verification URL: " . $verificationUrl . "\n";
    
    $testUser->notify(new EmailVerificationNotification($verificationUrl, $testUser, 'TestPassword123!'));
    
    echo "Email sent successfully!\n";
} catch (Exception $e) {
    echo "Error sending email: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
