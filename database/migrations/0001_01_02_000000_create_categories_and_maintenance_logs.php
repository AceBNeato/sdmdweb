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
        // Categories table
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['is_active']);
        });

        // Maintenance logs table
        Schema::create('maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('equipment_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('maintenance_type', ['preventive', 'corrective', 'emergency']);
            $table->text('description');
            $table->date('maintenance_date');
            $table->date('next_maintenance_date')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->decimal('cost', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['equipment_id', 'maintenance_date']);
            $table->index(['user_id', 'maintenance_date']);
            $table->index(['status', 'maintenance_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_logs');
        Schema::dropIfExists('categories');
    }
};
