<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, ensure campuses table exists
        if (!Schema::hasTable('campuses')) {
            throw new \Exception('Campuses table does not exist. Please run the campuses migration first.');
        }

        // Get or create a default campus
        $firstCampus = DB::table('campuses')->first();

        if (!$firstCampus) {
            // Create a default campus if none exists
            $firstCampusId = DB::table('campuses')->insertGetId([
                'name' => 'Main Campus',
                'code' => 'MAIN',
                'address' => 'Main Campus',
                'contact_number' => '000-000-0000',
                'email' => 'campus@example.com',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $firstCampus = (object)['id' => $firstCampusId];
        }

        // Add campus_id column with a default value
        Schema::table('offices', function (Blueprint $table) use ($firstCampus) {
            $table->foreignId('campus_id')
                  ->after('id')
                  ->default($firstCampus->id)
                  ->constrained('campuses')
                  ->onDelete('restrict');
        });

        // Remove unique constraint from code if it exists
        if (Schema::hasColumn('offices', 'code')) {
            Schema::table('offices', function (Blueprint $table) {
                $table->dropUnique('offices_code_unique');
                $table->string('code')->nullable()->change();
            });
        }
    }
};
