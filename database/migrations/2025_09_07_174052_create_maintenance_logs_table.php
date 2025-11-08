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
        // Create maintenance_logs table without foreign key constraints initially
        // Foreign keys will be added in a separate migration after users table exists
        Schema::create('maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('equipment_id');
            $table->unsignedBigInteger('user_id');
            $table->string('maintenance_type'); // scheduled, repair, emergency, inspection
            $table->text('description');
            $table->string('status')->default('pending'); // pending, in_progress, completed, cancelled
            $table->date('scheduled_date');
            $table->date('completed_date')->nullable();
            $table->integer('priority')->default(3); // 1=high, 2=medium, 3=low
            $table->decimal('cost', 10, 2)->nullable(); // maintenance cost
            $table->json('parts_used')->nullable(); // JSON array of parts used
            $table->text('work_performed')->nullable(); // detailed work description
            $table->text('recommendations')->nullable(); // future recommendations
            $table->text('notes')->nullable(); // additional notes
            $table->timestamps();
            $table->softDeletes();

            // Indexes for better performance
            $table->index(['status', 'priority']);
            $table->index('maintenance_type');
            $table->index('scheduled_date');
            $table->index('completed_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_logs');
    }
};
