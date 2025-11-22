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
            // Add missing columns only if they don't already exist
            if (!Schema::hasColumn('equipment', 'description')) {
                $table->text('description')->nullable()->after('serial_number');
            }
            if (!Schema::hasColumn('equipment', 'cost_of_purchase')) {
                $table->decimal('cost_of_purchase', 10, 2)->nullable()->after('purchase_date');
            }
            if (!Schema::hasColumn('equipment', 'condition')) {
                $table->string('condition')->nullable()->after('status');
            }
            if (!Schema::hasColumn('equipment', 'notes')) {
                $table->text('notes')->nullable()->after('condition');
            }
        });
    }

    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropColumn(['description', 'cost_of_purchase', 'condition', 'notes']);
        });
    }
};
