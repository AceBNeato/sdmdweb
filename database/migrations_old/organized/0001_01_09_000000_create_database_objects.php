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
        // Equipment Summary View
        DB::statement("
            CREATE VIEW equipment_summary_view AS
            SELECT 
                e.id,
                e.name,
                e.serial_number,
                e.status,
                e.is_available,
                e.office_id,
                o.name as office_name,
                e.equipment_type_id,
                et.name as equipment_type_name,
                e.category_id,
                c.name as category_name,
                COUNT(eh.id) as history_count,
                MAX(eh.date) as last_activity_date
            FROM equipment e
            LEFT JOIN offices o ON e.office_id = o.id
            LEFT JOIN equipment_types et ON e.equipment_type_id = et.id
            LEFT JOIN categories c ON e.category_id = c.id
            LEFT JOIN equipment_history eh ON e.id = eh.equipment_id
            WHERE e.deleted_at IS NULL
            GROUP BY e.id, e.name, e.serial_number, e.status, e.is_available, 
                     e.office_id, o.name, e.equipment_type_id, et.name, 
                     e.category_id, c.name
        ");

        // Equipment Count Function
        DB::statement("
            CREATE FUNCTION get_equipment_count(p_office_id INT, p_status VARCHAR(20)) 
            RETURNS INT
            DETERMINISTIC
            READS SQL DATA
            BEGIN
                DECLARE equipment_count INT;
                
                SELECT COUNT(*) INTO equipment_count
                FROM equipment 
                WHERE office_id = p_office_id 
                AND (p_status IS NULL OR status = p_status)
                AND deleted_at IS NULL;
                
                RETURN equipment_count;
            END
        ");

        // Equipment Status Trigger
        DB::statement("
            CREATE TRIGGER equipment_status_trigger 
            BEFORE UPDATE ON equipment
            FOR EACH ROW
            BEGIN
                IF NEW.status != OLD.status THEN
                    INSERT INTO equipment_history (
                        equipment_id, 
                        user_id, 
                        action, 
                        description, 
                        old_values, 
                        new_values,
                        date,
                        created_at,
                        updated_at
                    ) VALUES (
                        NEW.id,
                        COALESCE(@current_user_id, 1),
                        'status_change',
                        CONCAT('Equipment status changed from ', OLD.status, ' to ', NEW.status),
                        JSON_OBJECT('status', OLD.status),
                        JSON_OBJECT('status', NEW.status),
                        CURDATE(),
                        NOW(),
                        NOW()
                    );
                END IF;
            END
        ");

        // Additional stored procedures
        DB::statement("
            CREATE PROCEDURE get_equipment_by_office(IN p_office_id INT)
            BEGIN
                SELECT 
                    e.id,
                    e.name,
                    e.serial_number,
                    e.status,
                    e.is_available,
                    et.name as equipment_type_name,
                    c.name as category_name
                FROM equipment e
                LEFT JOIN equipment_types et ON e.equipment_type_id = et.id
                LEFT JOIN categories c ON e.category_id = c.id
                WHERE e.office_id = p_office_id
                AND e.deleted_at IS NULL
                ORDER BY e.name;
            END
        ");

        DB::statement("
            CREATE PROCEDURE get_user_equipment_history(IN p_user_id INT)
            BEGIN
                SELECT 
                    eh.id,
                    eh.action,
                    eh.description,
                    eh.date,
                    e.name as equipment_name,
                    e.serial_number
                FROM equipment_history eh
                JOIN equipment e ON eh.equipment_id = e.id
                WHERE eh.user_id = p_user_id
                ORDER BY eh.date DESC, eh.created_at DESC;
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop procedures
        DB::statement("DROP PROCEDURE IF EXISTS get_user_equipment_history");
        DB::statement("DROP PROCEDURE IF EXISTS get_equipment_by_office");
        
        // Drop trigger
        DB::statement("DROP TRIGGER IF EXISTS equipment_status_trigger");
        
        // Drop function
        DB::statement("DROP FUNCTION IF EXISTS get_equipment_count");
        
        // Drop view
        DB::statement("DROP VIEW IF EXISTS equipment_summary_view");
    }
};
