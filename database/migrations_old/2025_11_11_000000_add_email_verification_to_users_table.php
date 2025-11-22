<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'email_verification_token')) {
                $table->string('email_verification_token', 64)->nullable()->after('email_verified_at');
            }
            if (!Schema::hasColumn('users', 'email_verification_token_expires_at')) {
                $table->timestamp('email_verification_token_expires_at')->nullable()->after('email_verification_token');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('users', 'email_verified_at')) {
                $columnsToDrop[] = 'email_verified_at';
            }
            if (Schema::hasColumn('users', 'email_verification_token')) {
                $columnsToDrop[] = 'email_verification_token';
            }
            if (Schema::hasColumn('users', 'email_verification_token_expires_at')) {
                $columnsToDrop[] = 'email_verification_token_expires_at';
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
