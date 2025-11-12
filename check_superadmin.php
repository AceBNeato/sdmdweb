<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = \App\Models\User::where('email', 'superadmin@sdmd.ph')->first();
if ($user) {
    echo 'Superadmin found: ' . $user->name . PHP_EOL;
    echo 'Roles count: ' . $user->roles->count() . PHP_EOL;
    echo 'is_super_admin: ' . ($user->is_super_admin ? 'true' : 'false') . PHP_EOL;

    foreach ($user->roles as $role) {
        echo '- Role: ' . $role->name . ' (expires_at: ' . ($role->pivot->expires_at ?? 'null') . ')' . PHP_EOL;
    }
} else {
    echo 'Superadmin not found' . PHP_EOL;
}

// Check if super-admin role exists
$superAdminRole = \App\Models\Role::where('name', 'super-admin')->first();
if ($superAdminRole) {
    echo 'Super-admin role exists in database' . PHP_EOL;
} else {
    echo 'Super-admin role does NOT exist in database' . PHP_EOL;
}
