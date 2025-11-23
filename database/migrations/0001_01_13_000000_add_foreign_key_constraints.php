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
        
        // Add foreign key constraints to equipment_history (only if they don't exist)
        $equipmentHistoryFks = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'equipment_history' AND CONSTRAINT_NAME != 'PRIMARY'");
        $hasEquipmentHistoryEquipmentFk = collect($equipmentHistoryFks)->pluck('CONSTRAINT_NAME')->contains('equipment_history_equipment_id_foreign');
        $hasEquipmentHistoryUserFk = collect($equipmentHistoryFks)->pluck('CONSTRAINT_NAME')->contains('equipment_history_user_id_foreign');
        
        if (!$hasEquipmentHistoryEquipmentFk) {
            Schema::table('equipment_history', function (Blueprint $table) {
                $table->foreign('equipment_id')->references('id')->on('equipment')->onDelete('cascade');
            });
        }
        if (!$hasEquipmentHistoryUserFk) {
            Schema::table('equipment_history', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        // Add foreign key constraints to activities (only if they don't exist)
        $activitiesFks = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'activities' AND CONSTRAINT_NAME != 'PRIMARY'");
        $hasActivitiesUserFk = collect($activitiesFks)->pluck('CONSTRAINT_NAME')->contains('activities_user_id_foreign');
        
        if (!$hasActivitiesUserFk) {
            Schema::table('activities', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        // Add foreign key constraints to password_reset_otps (only if they don't exist)
        $passwordResetOtpFks = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'password_reset_otps' AND CONSTRAINT_NAME != 'PRIMARY'");
        $hasPasswordResetOtpUserFk = collect($passwordResetOtpFks)->pluck('CONSTRAINT_NAME')->contains('password_reset_otps_user_id_foreign');
        
        if (!$hasPasswordResetOtpUserFk) {
            Schema::table('password_reset_otps', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key constraints

        Schema::table('equipment_history', function (Blueprint $table) {
            $table->dropForeign(['equipment_id']);
            $table->dropForeign(['user_id']);
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('password_reset_otps', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
    }
};
