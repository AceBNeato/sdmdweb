<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("DROP VIEW IF EXISTS equipment_summary");
        DB::statement("
            CREATE VIEW equipment_summary AS
            SELECT
                e.id,
                e.model_number,
                e.serial_number,
                e.status,
                e.purchase_date,
                e.qr_code,
                o.name AS office_name,
                c.name AS category_name,
                e.created_at,
                e.updated_at
            FROM equipment e
            LEFT JOIN offices o ON e.office_id = o.id
            LEFT JOIN categories c ON e.category_id = c.id
        ");
    }

    public function down()
    {
        DB::statement("DROP VIEW IF EXISTS equipment_summary");
    }
};
