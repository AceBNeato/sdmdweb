<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('equipment', function (Blueprint $table) {
            // Composite index for status and office_id to speed up filtering
            $table->index(['status', 'office_id']);
            // Index on serial_number if not already unique
            $table->index('serial_number');
            // Index on purchase_date for date-based queries
            $table->index('purchase_date');
        });

        Schema::table('users', function (Blueprint $table) {
            // Composite index for is_active and office_id
            $table->index(['is_active', 'office_id']);
        });

        Schema::table('offices', function (Blueprint $table) {
            // Index for active offices filtering
            $table->index('is_active');
            // Index for campus-based queries
            $table->index('campus_id');
        });

        Schema::table('campuses', function (Blueprint $table) {
            // Index for active campuses filtering
            $table->index('is_active');
        });

        Schema::table('categories', function (Blueprint $table) {
            // Already has index on is_active - good
        });

        Schema::table('activities', function (Blueprint $table) {
            // Index for user activity queries
            $table->index(['user_id', 'created_at']);
            // Index for action-based filtering
            $table->index('action');
        });

        Schema::table('roles', function (Blueprint $table) {
            // Index for active roles if needed later
            $table->index('name');
        });

        Schema::table('permissions', function (Blueprint $table) {
            // Index for permission lookups
            $table->index('name');
            $table->index('group');
        });
    }

    public function down()
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropIndex(['status', 'office_id']);
            $table->dropIndex(['serial_number']);
            $table->dropIndex(['purchase_date']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'office_id']);
        });

        Schema::table('offices', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['campus_id']);
        });

        Schema::table('campuses', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropIndex(['action']);
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['group']);
        });

        Schema::table('maintenance_logs', function (Blueprint $table) {
            // Assuming maintenance_logs has a date or status column
            $table->dropIndex(['equipment_id', 'created_at']);
        });
    }
};
