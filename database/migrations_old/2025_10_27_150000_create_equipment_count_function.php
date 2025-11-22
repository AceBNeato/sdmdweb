<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("DROP FUNCTION IF EXISTS get_equipment_count_by_status");
        DB::statement("
            CREATE FUNCTION get_equipment_count_by_status(p_status VARCHAR(255))
            RETURNS INT
            DETERMINISTIC
            BEGIN
                DECLARE count INT;
                SELECT COUNT(*) INTO count FROM equipment WHERE status = p_status;
                RETURN count;
            END;
        ");
    }

    public function down()
    {
        DB::statement("DROP FUNCTION IF EXISTS get_equipment_count_by_status");
    }
};
