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
        // Password reset OTPs table
        Schema::create('password_reset_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('otp');
            $table->timestamp('expires_at');
            $table->boolean('is_used')->default(false);
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'is_used']);
            $table->index(['expires_at']);
            $table->index(['otp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_reset_otps');
    }
};
