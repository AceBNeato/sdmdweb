<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("DROP PROCEDURE IF EXISTS assign_equipment");
        DB::statement("
            CREATE PROCEDURE assign_equipment(
                IN p_equipment_id BIGINT,
                IN p_user_id BIGINT,
                IN p_assigned_by_id BIGINT
            )
            BEGIN
                -- Update equipment
                UPDATE equipment
                SET
                    assigned_to_type = 'App\\Models\\User',
                    assigned_to_id = p_user_id,
                    assigned_by_id = p_assigned_by_id,
                    assigned_at = NOW(),
                    status = 'assigned',
                    updated_at = NOW()
                WHERE id = p_equipment_id AND status = 'available';

                -- Log the assignment if successful and assigned_by_id is not null
                IF ROW_COUNT() > 0 AND p_assigned_by_id IS NOT NULL THEN
                    INSERT INTO activities (user_id, action, description, created_at, updated_at)
                    VALUES (p_assigned_by_id, 'assignment', CONCAT('Assigned equipment ID ', p_equipment_id, ' to user ID ', p_user_id), NOW(), NOW());
                END IF;
            END;
        ");
    }

    public function down()
    {
        DB::statement("DROP PROCEDURE IF EXISTS assign_equipment");
    }
};
