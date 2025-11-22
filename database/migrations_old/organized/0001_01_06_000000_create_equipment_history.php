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
        // Equipment history table
        Schema::create('equipment_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('action', ['created', 'updated', 'assigned', 'unassigned', 'maintenance', 'status_change', 'qr_generated']);
            $table->text('description');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->date('date')->default(now());
            $table->timestamps();
            
            // Indexes
            $table->index(['equipment_id', 'date']);
            $table->index(['user_id', 'date']);
            $table->index(['action', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_history');
    }
};
