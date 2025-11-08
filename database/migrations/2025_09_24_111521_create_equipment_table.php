<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('equipment')) {
            Schema::create('equipment', function (Blueprint $table) {
                $table->id();
                $table->string('model_number');
                $table->string('serial_number')->unique();
                $table->string('equipment_type');
                $table->date('purchase_date')->nullable();

                // Campus and Office relationships (required in admin form)
                $table->unsignedBigInteger('office_id');

                // Category relationship (optional in admin form)
                $table->unsignedBigInteger('category_id')->nullable();

                // Equipment assignment using polymorphic relationship (set by controller)
                $table->nullableMorphs('assigned_to'); // assigned_to_type, assigned_to_id

                // Assignment tracking (set by controller, not form inputs)
                $table->string('assigned_by_type')->nullable();
                $table->unsignedBigInteger('assigned_by_id')->nullable();
                $table->timestamp('assigned_at')->nullable();

                // Equipment status and condition tracking (required in admin form)
                $table->string('status')->default('available'); // available, assigned, maintenance, retired


                // QR Code storage (generated automatically, not form input)
                $table->string('qr_code')->unique()->nullable();
                $table->string('qr_code_image_path')->nullable();

                $table->timestamps();
                $table->softDeletes();

                // Foreign key constraints
                $table->foreign('office_id')->references('id')->on('offices')->onDelete('cascade');
                $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
                $table->foreign('assigned_by_id')->references('id')->on('users')->onDelete('set null');

                // Indexes for better performance
                $table->index(['status']);
                $table->index('equipment_type');
                $table->index('category_id');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('equipment');
    }
};
