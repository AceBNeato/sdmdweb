<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add role_id column if it doesn't exist
        if (!Schema::hasColumn('users', 'role_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('role_id')->after('campus_id')->nullable();
                $table->foreign('role_id')->references('id')->on('roles')->onDelete('set null');
                $table->index('role_id');
            });
        }

        // Migrate existing role_user data to role_id
        // For users with multiple roles, keep the most recent one
        $roleUserData = DB::table('role_user')
            ->select('user_id', 'role_id', 'created_at')
            ->orderBy('user_id')
            ->orderBy('created_at', 'desc')
            ->get();

        $processedUsers = [];
        foreach ($roleUserData as $roleUser) {
            if (!in_array($roleUser->user_id, $processedUsers)) {
                DB::table('users')
                    ->where('id', $roleUser->user_id)
                    ->update(['role_id' => $roleUser->role_id]);
                
                $processedUsers[] = $roleUser->user_id;
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove role_id column
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropIndex(['role_id']);
            $table->dropColumn('role_id');
        });
    }
};
