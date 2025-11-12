<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Role;
use Illuminate\Console\Command;

class FixSuperAdminRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:superadmin-roles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix superadmin roles if they are missing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking superadmin roles...');

        // Get super-admin role
        $superAdminRole = Role::where('name', 'super-admin')->first();

        if (!$superAdminRole) {
            $this->error('Super-admin role does not exist in database!');
            return 1;
        }

        // Find superadmin users
        $superAdmins = User::where('email', 'like', 'superadmin%')->get();

        if ($superAdmins->isEmpty()) {
            $this->error('No superadmin users found!');
            return 1;
        }

        foreach ($superAdmins as $user) {
            $this->info("Checking user: {$user->email}");

            // Check current roles
            $currentRoles = $user->roles->pluck('name')->toArray();
            $this->info("Current roles: " . implode(', ', $currentRoles));

            // Check if super-admin role is missing
            if (!in_array('super-admin', $currentRoles)) {
                $this->warn("Super-admin role missing for {$user->email}, assigning...");

                // Assign super-admin role
                $user->roles()->syncWithoutDetaching([$superAdminRole->id]);

                // Attach permissions
                foreach ($superAdminRole->permissions as $permission) {
                    $user->permissions()->syncWithoutDetaching([$permission->id => ['is_active' => true]]);
                }

                $this->info("✅ Super-admin role assigned to {$user->email}");
            } else {
                $this->info("✅ {$user->email} already has super-admin role");
            }
        }

        $this->info('Superadmin role check completed!');
        return 0;
    }
}
