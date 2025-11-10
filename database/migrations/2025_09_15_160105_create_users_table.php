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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('position')->nullable();
            $table->foreignId('office_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('campus_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('specialization')->nullable();
            $table->text('skills')->nullable();
            $table->boolean('is_available')->default(true);
            $table->string('employee_id')->nullable();
            $table->binary('profile_photo')->nullable();
            $table->rememberToken();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for better performance
            $table->index(['is_active']);
            $table->index('office_id');
            $table->index('campus_id');
            $table->index(['is_available']);
            $table->index('specialization');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }













    
};
