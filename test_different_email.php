<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Mail;

echo "Testing with different email addresses...\n";

$emails = [
    'giangarcia977@gmail.com',
    // Add another email you can check immediately
];

foreach ($emails as $email) {
    echo "Testing: $email\n";
    try {
        Mail::raw("Test email to $email at " . date('H:i:s'), function ($message) use ($email) {
            $message->to($email)
                    ->subject("SDMD Test to $email");
        });
        echo "✓ Sent to $email\n";
    } catch (Exception $e) {
        echo "✗ Failed to $email: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

echo "If you still don't receive emails, the issue is likely:\n";
echo "1. Gmail app password needs regeneration\n";
echo "2. Gmail sending limits exceeded\n";
echo "3. Emails going to spam\n";
echo "4. Network/firewall blocking SMTP (port 587)\n";
