<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('equipment', function (Blueprint $table) {
            // Only add indexes if they don't already exist
            if (!Schema::hasIndex('equipment', 'equipment_status_office_id_index')) {
                $table->index(['status', 'office_id']);
            }
            if (!Schema::hasIndex('equipment', 'equipment_serial_number_index')) {
                $table->index('serial_number');
            }
            if (!Schema::hasIndex('equipment', 'equipment_purchase_date_index')) {
                $table->index('purchase_date');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            // Only add index if it doesn't already exist
            if (!Schema::hasIndex('users', 'users_is_active_office_id_index')) {
                $table->index(['is_active', 'office_id']);
            }
        });

        Schema::table('offices', function (Blueprint $table) {
            // Only add indexes if they don't already exist
            if (!Schema::hasIndex('offices', 'offices_is_active_index')) {
                $table->index('is_active');
            }
            if (!Schema::hasIndex('offices', 'offices_campus_id_index')) {
                $table->index('campus_id');
            }
        });

        Schema::table('campuses', function (Blueprint $table) {
            // Only add index if it doesn't already exist
            if (!Schema::hasIndex('campuses', 'campuses_is_active_index')) {
                $table->index('is_active');
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            // Already has index on is_active - good
        });

        Schema::table('activities', function (Blueprint $table) {
            // Only add indexes if they don't already exist
            if (!Schema::hasIndex('activities', 'activities_user_id_created_at_index')) {
                $table->index(['user_id', 'created_at']);
            }
            if (!Schema::hasIndex('activities', 'activities_action_index')) {
                $table->index('action');
            }
        });

        Schema::table('roles', function (Blueprint $table) {
            // Only add index if it doesn't already exist
            if (!Schema::hasIndex('roles', 'roles_name_index')) {
                $table->index('name');
            }
        });

        Schema::table('permissions', function (Blueprint $table) {
            // Only add indexes if they don't already exist
            if (!Schema::hasIndex('permissions', 'permissions_name_index')) {
                $table->index('name');
            }
            if (!Schema::hasIndex('permissions', 'permissions_group_index')) {
                $table->index('group');
            }
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
