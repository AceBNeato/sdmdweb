<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            // Only add the foreign key constraint since column and index already exist
            $table->foreign('equipment_type_id')->references('id')->on('equipment_types')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropForeign(['equipment_type_id']);
            $table->dropColumn('equipment_type_id');
        });
    }
};
