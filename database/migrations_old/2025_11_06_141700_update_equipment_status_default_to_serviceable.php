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
        // Update existing 'available' status to 'serviceable'
        DB::table('equipment')->where('status', 'available')->update(['status' => 'serviceable']);

        // Change the default value for the status column
        Schema::table('equipment', function (Blueprint $table) {
            $table->string('status')->default('serviceable')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert 'serviceable' back to 'available'
        DB::table('equipment')->where('status', 'serviceable')->update(['status' => 'available']);

        // Revert the default value
        Schema::table('equipment', function (Blueprint $table) {
            $table->string('status')->default('available')->change();
        });
    }
};
