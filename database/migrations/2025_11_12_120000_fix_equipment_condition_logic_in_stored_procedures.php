<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Fix the create_equipment_history stored procedure
        DB::statement("DROP PROCEDURE IF EXISTS create_equipment_history");
        DB::statement("
            CREATE PROCEDURE create_equipment_history(
                IN p_equipment_id BIGINT,
                IN p_user_id BIGINT,
                IN p_date DATE,
                IN p_jo_number VARCHAR(50),
                IN p_action_taken TEXT,
                IN p_remarks TEXT,
                IN p_responsible_person VARCHAR(255),
                IN p_equipment_status ENUM('serviceable', 'for_repair', 'defective'),
                IN p_assigned_by_id BIGINT
            )
            BEGIN
                DECLARE EXIT HANDLER FOR SQLEXCEPTION
                BEGIN
                    ROLLBACK;
                    RESIGNAL;
                END;

                START TRANSACTION;

                -- Insert history record
                INSERT INTO equipment_history (
                    equipment_id, user_id, date, jo_number, action_taken,
                    remarks, responsible_person, created_at, updated_at
                ) VALUES (
                    p_equipment_id, p_user_id, p_date, p_jo_number, p_action_taken,
                    p_remarks, p_responsible_person, NOW(), NOW()
                );

                -- Update equipment status and condition
                UPDATE equipment
                SET
                    status = p_equipment_status,
                    `condition` = CASE
                        WHEN p_equipment_status = 'serviceable' THEN 'good'
                        WHEN p_equipment_status IN ('for_repair', 'defective') THEN 'not_working'
                        ELSE `condition`
                    END,
                    assigned_by_id = p_assigned_by_id,
                    updated_at = NOW()
                WHERE id = p_equipment_id;

                -- Log the activity
                INSERT INTO activities (user_id, action, description, created_at, updated_at)
                VALUES (p_user_id, 'equipment.history', CONCAT('Created history for equipment ID ', p_equipment_id, ' - Status: ', p_equipment_status), NOW(), NOW());

                COMMIT;
            END;
        ");

        // Fix the bulk_update_equipment_status stored procedure
        DB::statement("DROP PROCEDURE IF EXISTS bulk_update_equipment_status");
        DB::statement("
            CREATE PROCEDURE bulk_update_equipment_status(
                IN p_equipment_ids JSON,
                IN p_new_status ENUM('serviceable', 'for_repair', 'defective'),
                IN p_updated_by_id BIGINT,
                IN p_reason TEXT
            )
            BEGIN
                DECLARE equipment_id BIGINT;
                DECLARE i INT DEFAULT 0;
                DECLARE equipment_count INT;
                DECLARE updated_count INT DEFAULT 0;

                DECLARE EXIT HANDLER FOR SQLEXCEPTION
                BEGIN
                    ROLLBACK;
                    RESIGNAL;
                END;

                START TRANSACTION;

                SET equipment_count = JSON_LENGTH(p_equipment_ids);

                -- Update each equipment item
                WHILE i < equipment_count DO
                    SET equipment_id = JSON_EXTRACT(p_equipment_ids, CONCAT('$[', i, ']'));

                    UPDATE equipment
                    SET
                        status = p_new_status,
                        `condition` = CASE
                            WHEN p_new_status = 'serviceable' THEN 'good'
                            WHEN p_new_status IN ('for_repair', 'defective') THEN 'not_working'
                            ELSE `condition`
                        END,
                        assigned_by_id = p_updated_by_id,
                        updated_at = NOW()
                    WHERE id = equipment_id;

                    IF ROW_COUNT() > 0 THEN
                        SET updated_count = updated_count + 1;
                    END IF;

                    SET i = i + 1;
                END WHILE;

                -- Log the bulk operation
                INSERT INTO activities (user_id, action, description, created_at, updated_at)
                VALUES (p_updated_by_id, 'equipment.bulk_update',
                       CONCAT('Bulk updated ', updated_count, ' equipment items to status: ', p_new_status, '. Reason: ', COALESCE(p_reason, 'No reason provided')),
                       NOW(), NOW());

                COMMIT;

                SELECT updated_count AS affected_rows;
            END;
        ");
    }

    public function down()
    {
        // Revert to the old logic (keeping existing condition for non-serviceable statuses)
        DB::statement("DROP PROCEDURE IF EXISTS create_equipment_history");
        DB::statement("
            CREATE PROCEDURE create_equipment_history(
                IN p_equipment_id BIGINT,
                IN p_user_id BIGINT,
                IN p_date DATE,
                IN p_jo_number VARCHAR(50),
                IN p_action_taken TEXT,
                IN p_remarks TEXT,
                IN p_responsible_person VARCHAR(255),
                IN p_equipment_status ENUM('serviceable', 'for_repair', 'defective'),
                IN p_assigned_by_id BIGINT
            )
            BEGIN
                DECLARE EXIT HANDLER FOR SQLEXCEPTION
                BEGIN
                    ROLLBACK;
                    RESIGNAL;
                END;

                START TRANSACTION;

                -- Insert history record
                INSERT INTO equipment_history (
                    equipment_id, user_id, date, jo_number, action_taken,
                    remarks, responsible_person, created_at, updated_at
                ) VALUES (
                    p_equipment_id, p_user_id, p_date, p_jo_number, p_action_taken,
                    p_remarks, p_responsible_person, NOW(), NOW()
                );

                -- Update equipment status and condition
                UPDATE equipment
                SET
                    status = p_equipment_status,
                    `condition` = CASE
                        WHEN p_equipment_status = 'serviceable' THEN 'good'
                        ELSE `condition`
                    END,
                    assigned_by_id = p_assigned_by_id,
                    updated_at = NOW()
                WHERE id = p_equipment_id;

                -- Log the activity
                INSERT INTO activities (user_id, action, description, created_at, updated_at)
                VALUES (p_user_id, 'equipment.history', CONCAT('Created history for equipment ID ', p_equipment_id, ' - Status: ', p_equipment_status), NOW(), NOW());

                COMMIT;
            END;
        ");

        DB::statement("DROP PROCEDURE IF EXISTS bulk_update_equipment_status");
        DB::statement("
            CREATE PROCEDURE bulk_update_equipment_status(
                IN p_equipment_ids JSON,
                IN p_new_status ENUM('serviceable', 'for_repair', 'defective'),
                IN p_updated_by_id BIGINT,
                IN p_reason TEXT
            )
            BEGIN
                DECLARE equipment_id BIGINT;
                DECLARE i INT DEFAULT 0;
                DECLARE equipment_count INT;
                DECLARE updated_count INT DEFAULT 0;

                DECLARE EXIT HANDLER FOR SQLEXCEPTION
                BEGIN
                    ROLLBACK;
                    RESIGNAL;
                END;

                START TRANSACTION;

                SET equipment_count = JSON_LENGTH(p_equipment_ids);

                -- Update each equipment item
                WHILE i < equipment_count DO
                    SET equipment_id = JSON_EXTRACT(p_equipment_ids, CONCAT('$[', i, ']'));

                    UPDATE equipment
                    SET
                        status = p_new_status,
                        `condition` = CASE
                            WHEN p_new_status = 'serviceable' THEN 'good'
                            ELSE `condition`
                        END,
                        assigned_by_id = p_updated_by_id,
                        updated_at = NOW()
                    WHERE id = equipment_id;

                    IF ROW_COUNT() > 0 THEN
                        SET updated_count = updated_count + 1;
                    END IF;

                    SET i = i + 1;
                END WHILE;

                -- Log the bulk operation
                INSERT INTO activities (user_id, action, description, created_at, updated_at)
                VALUES (p_updated_by_id, 'equipment.bulk_update',
                       CONCAT('Bulk updated ', updated_count, ' equipment items to status: ', p_new_status, '. Reason: ', COALESCE(p_reason, 'No reason provided')),
                       NOW(), NOW());

                COMMIT;

                SELECT updated_count AS affected_rows;
            END;
        ");
    }
};
