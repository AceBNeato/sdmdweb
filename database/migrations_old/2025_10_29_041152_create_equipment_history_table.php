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
        Schema::create('equipment_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained()->onDelete('cascade');
            $table->string('jo_number')->nullable()->comment('Job Order Number');
            $table->string('action_taken');
            $table->text('remarks')->nullable();
            $table->string('responsible_person');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
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
