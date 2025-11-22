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
        // Roles table
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['name']);
        });

        // Permissions table
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->string('group')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['is_active']);
            $table->index(['group']);
            $table->index(['name']);
        });

        // Permission Role pivot table
        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->unique(['permission_id', 'role_id']);
            $table->index(['role_id', 'permission_id']);
        });

        // Users table
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->nullable()->constrained()->onDelete('set null');
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
            $table->string('profile_photo_path')->nullable();
            $table->binary('profile_photo')->nullable();
            $table->string('url_prefix')->nullable();
            $table->string('current_session_id')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('email_verification_token')->nullable();
            $table->timestamp('email_verification_token_expires_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['is_active']);
            $table->index(['office_id']);
            $table->index(['campus_id']);
            $table->index(['role_id']);
            $table->index(['is_available']);
            $table->index(['specialization']);
            $table->index(['email']);
        });

        // Role User pivot table (for migration purposes only)
        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->unique(['role_id', 'user_id']);
            $table->index(['user_id', 'role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('users');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
