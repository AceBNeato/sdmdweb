<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

// Create settings table if it doesn't exist
if (!Schema::hasTable('settings')) {
    Schema::create('settings', function ($table) {
        $table->id();
        $table->string('key')->unique();
        $table->text('value')->nullable();
        $table->string('type')->default('string');
        $table->string('description')->nullable();
        $table->timestamps();
    });

    // Insert default session timeout setting
    DB::table('settings')->insert([
        'key' => 'session_timeout_minutes',
        'value' => '1',
        'type' => 'integer',
        'description' => 'Session timeout in minutes for inactivity',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    echo "Settings table created successfully!\n";
} else {
    echo "Settings table already exists!\n";
}
