<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // 1. Equipment Assignment/Unassignment Procedure
        DB::statement("DROP PROCEDURE IF EXISTS assign_equipment");
        DB::statement("
            CREATE PROCEDURE assign_equipment(
                IN p_equipment_id BIGINT,
                IN p_user_id BIGINT,
                IN p_assigned_by_id BIGINT,
                IN p_action ENUM('assign', 'unassign')
            )
            BEGIN
                DECLARE EXIT HANDLER FOR SQLEXCEPTION
                BEGIN
                    ROLLBACK;
                    RESIGNAL;
                END;

                START TRANSACTION;

                IF p_action = 'assign' THEN
                    -- Update equipment assignment
                    UPDATE equipment
                    SET
                        assigned_to_type = 'App\\\\Models\\\\User',
                        assigned_to_id = p_user_id,
                        assigned_by_id = p_assigned_by_id,
                        assigned_at = NOW(),
                        status = 'assigned',
                        updated_at = NOW()
                    WHERE id = p_equipment_id AND status = 'available';

                    -- Log the assignment if successful
                    IF ROW_COUNT() > 0 AND p_assigned_by_id IS NOT NULL THEN
                        INSERT INTO activities (user_id, action, description, created_at, updated_at)
                        VALUES (p_assigned_by_id, 'equipment.assign', CONCAT('Assigned equipment ID ', p_equipment_id, ' to user ID ', p_user_id), NOW(), NOW());
                    END IF;

                ELSEIF p_action = 'unassign' THEN
                    -- Update equipment unassignment
                    UPDATE equipment
                    SET
                        assigned_to_type = NULL,
                        assigned_to_id = NULL,
                        assigned_by_id = p_assigned_by_id,
                        assigned_at = NULL,
                        status = 'available',
                        updated_at = NOW()
                    WHERE id = p_equipment_id AND assigned_to_id IS NOT NULL;

                    -- Log the unassignment if successful
                    IF ROW_COUNT() > 0 AND p_assigned_by_id IS NOT NULL THEN
                        INSERT INTO activities (user_id, action, description, created_at, updated_at)
                        VALUES (p_assigned_by_id, 'equipment.unassign', CONCAT('Unassigned equipment ID ', p_equipment_id), NOW(), NOW());
                    END IF;
                END IF;

                COMMIT;
            END;
        ");

        // 2. Equipment History Creation with Status Update Procedure
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

        // 3. User Creation with Role Assignment Procedure
        DB::statement("DROP PROCEDURE IF EXISTS create_user_with_roles");
        DB::statement("
            CREATE PROCEDURE create_user_with_roles(
                IN p_first_name VARCHAR(255),
                IN p_last_name VARCHAR(255),
                IN p_email VARCHAR(255),
                IN p_password VARCHAR(255),
                IN p_phone VARCHAR(15),
                IN p_position VARCHAR(255),
                IN p_office_id BIGINT,
                IN p_campus_id BIGINT,
                IN p_role_ids JSON,
                IN p_created_by_id BIGINT
            )
            BEGIN
                DECLARE new_user_id BIGINT;
                DECLARE role_id BIGINT;
                DECLARE i INT DEFAULT 0;
                DECLARE role_count INT;

                DECLARE EXIT HANDLER FOR SQLEXCEPTION
                BEGIN
                    ROLLBACK;
                    RESIGNAL;
                END;

                START TRANSACTION;

                -- Create the user
                INSERT INTO users (
                    first_name, last_name, email, password, phone, position,
                    office_id, campus_id, created_at, updated_at
                ) VALUES (
                    p_first_name, p_last_name, p_email, p_password, p_phone, p_position,
                    p_office_id, p_campus_id, NOW(), NOW()
                );

                SET new_user_id = LAST_INSERT_ID();

                -- Assign roles
                SET role_count = JSON_LENGTH(p_role_ids);
                WHILE i < role_count DO
                    SET role_id = JSON_EXTRACT(p_role_ids, CONCAT('$[', i, ']'));
                    INSERT INTO role_user (user_id, role_id, created_at, updated_at)
                    VALUES (new_user_id, role_id, NOW(), NOW());
                    SET i = i + 1;
                END WHILE;

                -- Log the activity
                INSERT INTO activities (user_id, action, description, created_at, updated_at)
                VALUES (p_created_by_id, 'user.create', CONCAT('Created user: ', p_first_name, ' ', p_last_name, ' (', p_email, ')'), NOW(), NOW());

                COMMIT;

                SELECT new_user_id AS user_id;
            END;
        ");

        // 4. JO Number Generation Procedure
        DB::statement("DROP PROCEDURE IF EXISTS generate_jo_number");
        DB::statement("
            CREATE PROCEDURE generate_jo_number(
                IN p_date DATE,
                OUT p_jo_number VARCHAR(50)
            )
            BEGIN
                DECLARE sequence INT DEFAULT 1;
                DECLARE max_attempts INT DEFAULT 99;
                DECLARE attempt INT DEFAULT 0;
                DECLARE candidate_jo VARCHAR(50);

                -- Find the next available sequence number for this date
                SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(jo_number, '-', -1) AS UNSIGNED)), 0) + 1
                INTO sequence
                FROM equipment_history
                WHERE DATE(date) = p_date AND jo_number LIKE CONCAT('JO-', DATE_FORMAT(p_date, '%Y-%m-%d'), '-%');

                -- Generate and check uniqueness
                generate_loop: WHILE attempt < max_attempts DO
                    SET candidate_jo = CONCAT('JO-', DATE_FORMAT(p_date, '%Y-%m-%d'), '-', LPAD(sequence, 2, '0'));

                    IF NOT EXISTS (SELECT 1 FROM equipment_history WHERE jo_number = candidate_jo) THEN
                        SET p_jo_number = candidate_jo;
                        LEAVE generate_loop;
                    END IF;

                    SET sequence = sequence + 1;
                    SET attempt = attempt + 1;
                END WHILE generate_loop;

                IF attempt >= max_attempts THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Unable to generate unique JO number';
                END IF;
            END;
        ");

        // 5. Bulk Equipment Status Update Procedure
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

    public function down()
    {
        DB::statement("DROP PROCEDURE IF EXISTS bulk_update_equipment_status");
        DB::statement("DROP PROCEDURE IF EXISTS generate_jo_number");
        DB::statement("DROP PROCEDURE IF EXISTS create_user_with_roles");
        DB::statement("DROP PROCEDURE IF EXISTS create_equipment_history");
        DB::statement("DROP PROCEDURE IF EXISTS assign_equipment");
    }
};
