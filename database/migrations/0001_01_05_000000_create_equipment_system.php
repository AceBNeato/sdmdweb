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
            $table->foreignId('equipment_type_id')->nullable()->constrained()->onDelete('set null');
            $table->text('description')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('cost_of_purchase', 10, 2)->nullable();
            $table->foreignId('office_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('status', ['serviceable', 'needs_repair', 'out_of_service', 'disposed'])->default('serviceable');
            $table->enum('condition', ['good', 'not_working'])->default('good');
            $table->string('qr_code')->unique()->nullable();
            $table->string('warranty_expiry')->nullable();
            $table->string('vendor')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_available')->default(true);
            $table->string('jo_number')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['office_id']);
            $table->index(['equipment_type_id']);
            $table->index(['category_id']);
            $table->index(['status']);
            $table->index(['is_available']);
            $table->index(['serial_number']);
            $table->index(['qr_code']);
            $table->index(['jo_number']);
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
