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
        Schema::table('equipment', function (Blueprint $table) {
            // Add missing columns that the controllers expect
            $table->text('description')->nullable()->after('serial_number');
            $table->decimal('cost_of_purchase', 10, 2)->nullable()->after('purchase_date');
            $table->string('condition')->nullable()->after('status');
            $table->text('notes')->nullable()->after('condition');
        });
    }

    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropColumn(['description', 'cost_of_purchase', 'condition', 'notes']);
        });
    }
};
