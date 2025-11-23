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
        // Equipment types table
        Schema::create('equipment_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['is_active']);
        });

        // Equipment table
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->string('brand');
            $table->string('model_number');
            $table->string('serial_number')->unique();
            $table->text('description')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('cost_of_purchase', 10, 2)->nullable();
            $table->foreignId('office_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->string('assigned_to_type')->nullable();
            $table->unsignedBigInteger('assigned_to_id')->nullable();
            $table->foreignId('assigned_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('assigned_at')->nullable();
            $table->string('status')->default('serviceable');
            $table->string('condition')->nullable();
            $table->text('notes')->nullable();
            $table->string('qr_code')->unique()->nullable();
            $table->string('qr_code_image_path')->nullable();
            $table->foreignId('equipment_type_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['assigned_to_type', 'assigned_to_id']);
            $table->index(['office_id']);
            $table->index(['assigned_by_id']);
            $table->index(['status']);
            $table->index(['category_id']);
            $table->index(['equipment_type_id']);
            $table->index(['status', 'office_id']);
            $table->index(['serial_number']);
            $table->index(['purchase_date']);
            $table->unique(['office_id', 'serial_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment');
        Schema::dropIfExists('equipment_types');
    }
};
