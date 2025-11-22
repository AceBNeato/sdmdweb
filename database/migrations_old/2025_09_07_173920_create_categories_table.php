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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        // Insert default categories
        DB::table('categories')->insert([
            [
                'name' => 'Computers',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Printers',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Networking',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Audio/Visual',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Furniture',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
