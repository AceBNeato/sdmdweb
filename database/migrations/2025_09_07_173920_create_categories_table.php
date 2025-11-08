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
            $table->text('description')->nullable();
            $table->string('color')->nullable(); // For UI display
            $table->string('icon')->nullable(); // Icon class for UI
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Indexes for better performance
            $table->index('is_active');
        });

        // Insert default categories
        DB::table('categories')->insert([
            [
                'name' => 'Computers',
                'description' => 'Desktop computers, laptops, and related equipment',
                'color' => '#3B82F6',
                'icon' => 'bx-laptop',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Printers',
                'description' => 'Printers, scanners, and multifunction devices',
                'color' => '#10B981',
                'icon' => 'bx-printer',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Networking',
                'description' => 'Routers, switches, access points, and network equipment',
                'color' => '#F59E0B',
                'icon' => 'bx-network-chart',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Audio/Visual',
                'description' => 'Projectors, speakers, microphones, and AV equipment',
                'color' => '#EF4444',
                'icon' => 'bx-video',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Furniture',
                'description' => 'Desks, chairs, cabinets, and office furniture',
                'color' => '#8B5CF6',
                'icon' => 'bx-chair',
                'is_active' => true,
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
