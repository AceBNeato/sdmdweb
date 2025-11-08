<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("DROP TRIGGER IF EXISTS log_equipment_status_change");
        DB::statement("
            CREATE TRIGGER log_equipment_status_change
            AFTER UPDATE ON equipment
            FOR EACH ROW
            BEGIN
                IF OLD.status != NEW.status AND NEW.assigned_by_id IS NOT NULL THEN
                    INSERT INTO activities (user_id, action, description, created_at, updated_at)
                    VALUES (NEW.assigned_by_id, 'status_change', CONCAT('Equipment ID ', NEW.id, ' status changed from ', OLD.status, ' to ', NEW.status), NOW(), NOW());
                END IF;
            END;
        ");
    }

    public function down()
    {
        DB::statement("DROP TRIGGER IF EXISTS log_equipment_status_change");
    }
};
