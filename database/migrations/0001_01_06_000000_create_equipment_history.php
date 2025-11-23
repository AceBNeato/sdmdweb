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
            $table->datetime('date')->nullable();
            $table->string('jo_number', 255)->nullable()->comment('Job Order Number');
            $table->string('action_taken', 255);
            $table->text('remarks')->nullable();
            $table->string('responsible_person', 255);
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
            
            // Indexes
            $table->index(['equipment_id']);
            $table->index(['user_id']);
            $table->index(['jo_number']);
            $table->index(['date']);
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
