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
        // Composite indexes for better performance
        
        // Equipment indexes
        Schema::table('equipment', function (Blueprint $table) {
            $table->index(['office_id', 'status']);
            $table->index(['equipment_type_id', 'status']);
            $table->index(['category_id', 'status']);
            $table->index(['status', 'is_available']);
            $table->index(['office_id', 'is_available']);
            $table->index(['purchase_date', 'status']);
        });

        // Equipment history indexes
        Schema::table('equipment_history', function (Blueprint $table) {
            $table->index(['equipment_id', 'action']);
            $table->index(['user_id', 'action']);
            $table->index(['action', 'date']);
            $table->index(['equipment_id', 'user_id']);
        });

        // Maintenance logs indexes
        Schema::table('maintenance_logs', function (Blueprint $table) {
            $table->index(['equipment_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['maintenance_type', 'status']);
            $table->index(['maintenance_date', 'status']);
            $table->index(['next_maintenance_date']);
        });

        // Users indexes
        Schema::table('users', function (Blueprint $table) {
            $table->index(['role_id', 'is_active']);
            $table->index(['office_id', 'is_active']);
            $table->index(['campus_id', 'is_active']);
            $table->index(['is_active', 'is_available']);
            $table->index(['first_name', 'last_name']);
            $table->index(['email_verified_at']);
        });

        // Activities indexes
        Schema::table('activities', function (Blueprint $table) {
            $table->index(['user_id', 'type']);
            $table->index(['type', 'created_at']);
            $table->index(['created_at', 'user_id']);
        });

        // Permission_role indexes
        Schema::table('permission_role', function (Blueprint $table) {
            $table->index(['role_id']);
            $table->index(['permission_id']);
        });

        // Role_user indexes
        Schema::table('role_user', function (Blueprint $table) {
            $table->index(['user_id']);
            $table->index(['role_id']);
        });

        // Fulltext indexes for search
        DB::statement("ALTER TABLE equipment ADD FULLTEXT(name, description, serial_number)");
        DB::statement("ALTER TABLE users ADD FULLTEXT(first_name, last_name, email)");
        DB::statement("ALTER TABLE offices ADD FULLTEXT(name, description)");
        DB::statement("ALTER TABLE categories ADD FULLTEXT(name, description)");
        DB::statement("ALTER TABLE equipment_types ADD FULLTEXT(name, description)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop fulltext indexes
        DB::statement("ALTER TABLE equipment DROP INDEX name");
        DB::statement("ALTER TABLE users DROP INDEX first_name");
        DB::statement("ALTER TABLE offices DROP INDEX name");
        DB::statement("ALTER TABLE categories DROP INDEX name");
        DB::statement("ALTER TABLE equipment_types DROP INDEX name");

        // Drop composite indexes (simplified - in production you'd want to be more specific)
        Schema::table('role_user', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['role_id']);
        });

        Schema::table('permission_role', function (Blueprint $table) {
            $table->dropIndex(['role_id']);
            $table->dropIndex(['permission_id']);
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'type']);
            $table->dropIndex(['type', 'created_at']);
            $table->dropIndex(['created_at', 'user_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role_id', 'is_active']);
            $table->dropIndex(['office_id', 'is_active']);
            $table->dropIndex(['campus_id', 'is_active']);
            $table->dropIndex(['is_active', 'is_available']);
            $table->dropIndex(['first_name', 'last_name']);
            $table->dropIndex(['email_verified_at']);
        });

        Schema::table('maintenance_logs', function (Blueprint $table) {
            $table->dropIndex(['equipment_id', 'status']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['maintenance_type', 'status']);
            $table->dropIndex(['maintenance_date', 'status']);
            $table->dropIndex(['next_maintenance_date']);
        });

        Schema::table('equipment_history', function (Blueprint $table) {
            $table->dropIndex(['equipment_id', 'action']);
            $table->dropIndex(['user_id', 'action']);
            $table->dropIndex(['action', 'date']);
            $table->dropIndex(['equipment_id', 'user_id']);
        });

        Schema::table('equipment', function (Blueprint $table) {
            $table->dropIndex(['office_id', 'status']);
            $table->dropIndex(['equipment_type_id', 'status']);
            $table->dropIndex(['category_id', 'status']);
            $table->dropIndex(['status', 'is_available']);
            $table->dropIndex(['office_id', 'is_available']);
            $table->dropIndex(['purchase_date', 'status']);
        });
    }
};
