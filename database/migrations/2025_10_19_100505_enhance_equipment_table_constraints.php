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
        Schema::table('equipment', function (Blueprint $table) {
            // Improve existing constraints and add validations

            // Model number should not be empty and have reasonable length
            $table->string('model_number', 100)->nullable(false)->change();

            // Serial number should be unique and not empty (already unique from original migration)
            $table->string('serial_number')->nullable(false)->change();

            // Equipment type ID should reference the equipment_types table (only if column exists)
            if (Schema::hasColumn('equipment', 'equipment_type_id')) {
                // Drop existing foreign key constraint if it exists
                $table->dropForeign(['equipment_type_id']);
                // Add new foreign key constraint with set null on delete (keep nullable)
                $table->foreign('equipment_type_id')->references('id')->on('equipment_types')->onDelete('set null');
            }

            // Status and condition will remain as strings but we can add validation at model level
            // Converting to enum might cause issues with existing data
            $table->string('status')->default('available')->change();

            // QR code should be unique across all equipment (already unique from original migration)
            $table->string('qr_code')->nullable()->change();

            // Assignment tracking fields - ensure proper types
            $table->string('assigned_to_type', 100)->nullable()->change();
            $table->unsignedBigInteger('assigned_to_id')->nullable()->change();
            $table->string('assigned_by_type', 100)->nullable()->change();
            $table->unsignedBigInteger('assigned_by_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            // Revert back to more lenient constraints
            if (Schema::hasColumn('equipment', 'equipment_type_id')) {
                // Drop the current foreign key constraint
                $table->dropForeign(['equipment_type_id']);
                // Add back the original foreign key constraint with set null
                $table->foreign('equipment_type_id')->references('id')->on('equipment_types')->onDelete('set null');
            }
            $table->string('model_number')->nullable()->change();
            $table->string('serial_number')->nullable()->change();
            $table->string('status')->default('available')->change();
            $table->string('qr_code')->nullable()->change();
        });
    }
};
