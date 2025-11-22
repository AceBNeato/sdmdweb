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
        Schema::create('equipment_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Insert default equipment types
        DB::table('equipment_types')->insert([
            ['name' => 'Laptop', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Desktop Computer', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Tablet', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Printer', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Scanner', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Projector', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Monitor', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Server', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Router', 'created_at' => now(), 'updated_at' => now()],
          ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_types');
    }
};
