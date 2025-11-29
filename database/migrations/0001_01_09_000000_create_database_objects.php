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
        // User Summary View (create only if doesn't exist)
        if (!DB::select("SELECT * FROM information_schema.views WHERE table_schema = DATABASE() AND table_name = 'user_summary_view'")) {
            DB::statement("
                CREATE VIEW user_summary_view AS
                SELECT 
                    u.id,
                    CONCAT(u.first_name, ' ', u.last_name) as full_name,
                    u.email,
                    u.position,
                    u.phone,
                    u.is_active,
                    u.is_available,
                    u.specialization,
                    u.employee_id,
                    r.name as role_name,
                    o.name as office_name,
                    c.name as campus_name,
                    COUNT(e.id) as equipment_count,
                    u.created_at
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                LEFT JOIN offices o ON u.office_id = o.id
                LEFT JOIN campuses c ON u.campus_id = c.id
                LEFT JOIN equipment e ON 1=0 -- Removed assignment fields
                WHERE u.deleted_at IS NULL
                GROUP BY u.id, u.first_name, u.last_name, u.email, u.position, u.phone, 
                         u.is_active, u.is_available, u.specialization, u.employee_id,
                         r.name, o.name, c.name, u.created_at
            ");
        }

        // Office Summary View (create only if doesn't exist)
        if (!DB::select("SELECT * FROM information_schema.views WHERE table_schema = DATABASE() AND table_name = 'office_summary_view'")) {
            DB::statement("
                CREATE VIEW office_summary_view AS
                SELECT 
                    o.id,
                    o.name,
                    o.location,
                    o.contact_number,
                    o.email,
                    o.is_active,
                    c.name as campus_name,
                    COUNT(u.id) as user_count,
                    COUNT(e.id) as equipment_count,
                    COALESCE(SUM(e.cost_of_purchase), 0) as total_equipment_cost,
                    o.created_at
                FROM offices o
                LEFT JOIN campuses c ON o.campus_id = c.id
                LEFT JOIN users u ON u.office_id = o.id AND u.deleted_at IS NULL
                LEFT JOIN equipment e ON e.office_id = o.id AND e.deleted_at IS NULL
                WHERE o.deleted_at IS NULL
                GROUP BY o.id, o.name, o.location, o.contact_number, o.email, 
                         o.is_active, c.name, o.created_at
            ");
        }

        // Category Summary View (create only if doesn't exist)
        if (!DB::select("SELECT * FROM information_schema.views WHERE table_schema = DATABASE() AND table_name = 'category_summary_view'")) {
            DB::statement("
                CREATE VIEW category_summary_view AS
                SELECT 
                    cat.id,
                    cat.name,
                    cat.is_active,
                    COUNT(e.id) as equipment_count,
                    COALESCE(SUM(e.cost_of_purchase), 0) as total_equipment_cost,
                    COUNT(CASE WHEN e.status = 'serviceable' THEN 1 END) as serviceable_count,
                    COUNT(CASE WHEN e.status = 'for_repair' THEN 1 END) as repair_count,
                    COUNT(CASE WHEN e.status = 'defective' THEN 1 END) as defective_count,
                    cat.created_at
                FROM categories cat
                LEFT JOIN equipment e ON e.category_id = cat.id AND e.deleted_at IS NULL
                WHERE cat.deleted_at IS NULL
                GROUP BY cat.id, cat.name, cat.is_active, cat.created_at
            ");
        }

        // Activity Summary View (create only if doesn't exist)
        if (!DB::select("SELECT * FROM information_schema.views WHERE table_schema = DATABASE() AND table_name = 'activity_summary_view'")) {
            DB::statement("
                CREATE VIEW activity_summary_view AS
                SELECT 
                    a.id,
                    a.type,
                    a.description,
                    a.created_at,
                    CONCAT(u.first_name, ' ', u.last_name) as user_name,
                    u.email as user_email,
                    r.name as user_role,
                    o.name as office_name
                FROM activities a
                LEFT JOIN users u ON a.user_id = u.id
                LEFT JOIN roles r ON u.role_id = r.id
                LEFT JOIN offices o ON u.office_id = o.id
                ORDER BY a.created_at DESC
            ");
        }

        // Equipment Summary View (create only if doesn't exist)
        if (!DB::select("SELECT * FROM information_schema.views WHERE table_schema = DATABASE() AND table_name = 'equipment_summary_view'")) {
            DB::statement("
                CREATE VIEW equipment_summary_view AS
                SELECT 
                    e.id,
                    CONCAT(e.brand, ' ', e.model_number) as name,
                    e.serial_number,
                    e.status,
                    e.`condition`,
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
                GROUP BY e.id, e.brand, e.model_number, e.serial_number, e.status, e.`condition`, 
                         e.office_id, o.name, e.equipment_type_id, et.name, 
                         e.category_id, c.name
            ");
        }

        // Equipment Summary View for phpMyAdmin (create only if doesn't exist)
        if (!DB::select("SELECT * FROM information_schema.views WHERE table_schema = DATABASE() AND table_name = 'equipment_summary'")) {
            DB::statement("
                CREATE VIEW equipment_summary AS
                SELECT 
                    e.id,
                    CONCAT(e.brand, ' ', e.model_number) as name,
                    e.serial_number,
                    e.description,
                    e.status,
                    e.purchase_date,
                    e.cost_of_purchase as purchase_cost,
                    e.model_number as model,
                    e.qr_code,
                    e.office_id,
                    e.category_id,
                    o.name as office_name,
                    cat.name as category_name,
                    e.created_at,
                    e.updated_at
                FROM equipment e
                LEFT JOIN offices o ON e.office_id = o.id
                LEFT JOIN categories cat ON e.category_id = cat.id
            ");
        }

        // User Count Function (create only if doesn't exist)
        if (!DB::select("SELECT * FROM information_schema.routines WHERE routine_schema = DATABASE() AND routine_name = 'get_user_count'")) {
            DB::statement("
                CREATE FUNCTION get_user_count(p_office_id INT, p_role_id INT, p_is_active BOOLEAN) 
                RETURNS INT
                DETERMINISTIC
                READS SQL DATA
                BEGIN
                    DECLARE user_count INT;
                    
                    SELECT COUNT(*) INTO user_count
                    FROM users 
                    WHERE (p_office_id IS NULL OR office_id = p_office_id)
                    AND (p_role_id IS NULL OR role_id = p_role_id)
                    AND (p_is_active IS NULL OR is_active = p_is_active)
                    AND deleted_at IS NULL;
                    
                    RETURN user_count;
                END
            ");
        }

        // Equipment Count Function (create only if doesn't exist)
        if (!DB::select("SELECT * FROM information_schema.routines WHERE routine_schema = DATABASE() AND routine_name = 'get_equipment_count'")) {
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
        }

        // Office Equipment Statistics Function (create only if doesn't exist)
        if (!DB::select("SELECT * FROM information_schema.routines WHERE routine_schema = DATABASE() AND routine_name = 'get_office_equipment_stats'")) {
            DB::statement("
                CREATE FUNCTION get_office_equipment_stats(p_office_id INT) 
                RETURNS JSON
                DETERMINISTIC
                READS SQL DATA
                BEGIN
                    DECLARE stats JSON;
                    
                    SELECT JSON_OBJECT(
                        'total', COUNT(*),
                        'serviceable', COUNT(CASE WHEN status = 'serviceable' THEN 1 END),
                        'for_repair', COUNT(CASE WHEN status = 'for_repair' THEN 1 END),
                        'defective', COUNT(CASE WHEN status = 'defective' THEN 1 END),
                        'total_cost', COALESCE(SUM(cost_of_purchase), 0)
                    ) INTO stats
                    FROM equipment 
                    WHERE office_id = p_office_id 
                    AND deleted_at IS NULL;
                    
                    RETURN stats;
                END
            ");
        }

        // User Management Procedures (create only if doesn't exist)
        if (!DB::select("SELECT * FROM information_schema.routines WHERE routine_schema = DATABASE() AND routine_name = 'get_users_by_office'")) {
            DB::statement("
                CREATE PROCEDURE get_users_by_office(IN p_office_id INT)
                BEGIN
                    SELECT 
                        u.id,
                        CONCAT(u.first_name, ' ', u.last_name) as full_name,
                        u.email,
                        u.position,
                        u.is_active,
                        u.is_available,
                        r.name as role_name,
                        COUNT(e.id) as equipment_count
                    FROM users u
                    LEFT JOIN roles r ON u.role_id = r.id
                    LEFT JOIN equipment e ON 1=0 -- Removed assignment fields
                    WHERE u.office_id = p_office_id
                    AND u.deleted_at IS NULL
                    GROUP BY u.id, u.first_name, u.last_name, u.email, u.position, 
                             u.is_active, u.is_available, r.name
                    ORDER BY u.first_name, u.last_name;
                END
            ");
        }

        // Additional stored procedures (create only if doesn't exist)
        if (!DB::select("SELECT * FROM information_schema.routines WHERE routine_schema = DATABASE() AND routine_name = 'get_equipment_by_office'")) {
            DB::statement("
                CREATE PROCEDURE get_equipment_by_office(IN p_office_id INT)
                BEGIN
                    SELECT 
                        e.id,
                        CONCAT(e.brand, ' ', e.model_number) as name,
                        e.serial_number,
                        e.status,
                        e.`condition`,
                        et.name as equipment_type_name,
                        c.name as category_name
                    FROM equipment e
                    LEFT JOIN equipment_types et ON e.equipment_type_id = et.id
                    LEFT JOIN categories c ON e.category_id = c.id
                    WHERE e.office_id = p_office_id
                    AND e.deleted_at IS NULL
                    ORDER BY e.brand, e.model_number;
                END
            ");
        }

        // Equipment Assignment View (create only if doesn't exist) - Simplified since assignment fields removed
        if (!DB::select("SELECT * FROM information_schema.views WHERE table_schema = DATABASE() AND table_name = 'equipment_assignment_view'")) {
            DB::statement("
                CREATE VIEW equipment_assignment_view AS
                SELECT 
                    e.id, e.brand, e.model_number, e.serial_number, e.status,
                    o.name as office_name
                FROM equipment e
                LEFT JOIN offices o ON e.office_id = o.id
                WHERE 1=0 -- No assignment data available
            ");
        }

        
        // Equipment Cost Analysis Function (create only if doesn't exist)
        if (!DB::select("SELECT * FROM information_schema.routines WHERE routine_schema = DATABASE() AND routine_name = 'get_total_equipment_cost'")) {
            DB::statement("
                CREATE FUNCTION get_total_equipment_cost(p_office_id INT) 
                RETURNS DECIMAL(15,2)
                DETERMINISTIC
                READS SQL DATA
                BEGIN
                    DECLARE total_cost DECIMAL(15,2);
                    SELECT COALESCE(SUM(cost_of_purchase), 0) INTO total_cost
                    FROM equipment 
                    WHERE office_id = p_office_id 
                    AND deleted_at IS NULL;
                    RETURN total_cost;
                END
            ");
        }

        
        // Bulk Status Update Procedure (create only if doesn't exist)
        if (!DB::select("SELECT * FROM information_schema.routines WHERE routine_schema = DATABASE() AND routine_name = 'bulk_update_equipment_status'")) {
            DB::statement("
                CREATE PROCEDURE bulk_update_equipment_status(
                    IN p_status VARCHAR(50), 
                    IN p_equipment_ids TEXT
                )
                BEGIN
                    UPDATE equipment 
                    SET status = p_status, updated_at = NOW()
                    WHERE FIND_IN_SET(id, p_equipment_ids) > 0;
                END
            ");
        }

        // User Activity Trigger (create only if doesn't exist)
        if (!DB::select("SELECT * FROM information_schema.triggers WHERE trigger_schema = DATABASE() AND trigger_name = 'user_activity_trigger'")) {
            DB::statement("
                CREATE TRIGGER user_activity_trigger
                AFTER UPDATE ON users
                FOR EACH ROW
                BEGIN
                    IF (OLD.is_active != NEW.is_active OR OLD.role_id != NEW.role_id)
                       AND (@SDMD_SUPPRESS_ACTIVITY IS NULL OR @SDMD_SUPPRESS_ACTIVITY = 0) THEN
                        INSERT INTO activities (user_id, type, description, created_at)
                        VALUES (
                            NEW.id,
                            'user_status_change',
                            CONCAT(
                                CASE 
                                    WHEN OLD.is_active != NEW.is_active THEN 
                                        CASE WHEN NEW.is_active THEN 'User reactivated' ELSE 'User deactivated' END
                                    WHEN OLD.role_id != NEW.role_id THEN 'User role changed'
                                    ELSE 'User status updated'
                                END,
                                ' by system'
                            ),
                            NOW()
                        );
                    END IF;
                END
            ");
        }

        // Office Equipment Count Trigger (create only if doesn't exist)
        if (!DB::select("SELECT * FROM information_schema.triggers WHERE trigger_schema = DATABASE() AND trigger_name = 'office_equipment_count_trigger'")) {
            DB::statement("
                CREATE TRIGGER office_equipment_count_trigger
                AFTER INSERT ON equipment
                FOR EACH ROW
                BEGIN
                    IF NEW.office_id IS NOT NULL
                       AND (@SDMD_SUPPRESS_ACTIVITY IS NULL OR @SDMD_SUPPRESS_ACTIVITY = 0) THEN
                        INSERT INTO activities (user_id, type, description, created_at)
                        VALUES (
                            COALESCE(NEW.assigned_by_id, 1),
                            'equipment_added_to_office',
                            CONCAT('Equipment ', NEW.brand, ' ', NEW.model_number, ' added to office ID ', NEW.office_id),
                            NOW()
                        );
                    END IF;
                END
            ");
        }

        // Category Usage Trigger (create only if doesn't exist)
        if (!DB::select("SELECT * FROM information_schema.triggers WHERE trigger_schema = DATABASE() AND trigger_name = 'category_usage_trigger'")) {
            DB::statement("
                CREATE TRIGGER category_usage_trigger
                AFTER UPDATE ON equipment
                FOR EACH ROW
                BEGIN
                    IF OLD.category_id != NEW.category_id AND NEW.category_id IS NOT NULL
                       AND (@SDMD_SUPPRESS_ACTIVITY IS NULL OR @SDMD_SUPPRESS_ACTIVITY = 0) THEN
                        INSERT INTO activities (user_id, type, description, created_at)
                        VALUES (
                            COALESCE(NEW.assigned_by_id, 1),
                            'category_change',
                            CONCAT('Equipment moved to category: ', (SELECT name FROM categories WHERE id = NEW.category_id)),
                            NOW()
                        );
                    END IF;
                END
            ");
        }

        // Application code handles equipment history logging to avoid duplicate trigger entries

        // Enable MySQL Event Scheduler (required for events to work)
        DB::statement("SET GLOBAL event_scheduler = ON");

        // Create Database Events for automated maintenance tasks
        if (!DB::select("SELECT * FROM information_schema.events WHERE event_schema = DATABASE() AND event_name = 'archive_old_sessions'")) {
            DB::statement("
                CREATE EVENT archive_old_sessions
                ON SCHEDULE EVERY 1 DAY
                STARTS CURRENT_TIMESTAMP + INTERVAL 2 HOUR
                DO
                    DELETE FROM sessions 
                    WHERE last_activity < DATE_SUB(NOW(), INTERVAL 1 WEEK)
            ");
        }

        if (!DB::select("SELECT * FROM information_schema.events WHERE event_schema = DATABASE() AND event_name = 'create_database_backup'")) {
            DB::statement("
                CREATE EVENT create_database_backup
                ON SCHEDULE EVERY 1 DAY
                STARTS CONCAT(DATE_FORMAT(NOW(), '%Y-%m-%d'), ' 02:00:00')
                DO
                    INSERT INTO activities (user_id, type, description, created_at)
                    VALUES (
                        1,
                        'backup_trigger',
                        CONCAT('Database backup triggered by event: ', DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s')),
                        NOW()
                    )
            ");
        }
        
        if (!DB::select("SELECT * FROM information_schema.events WHERE event_schema = DATABASE() AND event_name = 'cleanup_temp_files'")) {
            DB::statement("
                CREATE EVENT cleanup_temp_files
                ON SCHEDULE EVERY 1 WEEK
                STARTS CURRENT_TIMESTAMP + INTERVAL 1 DAY
                DO
                    INSERT INTO activities (user_id, type, description, created_at)
                    VALUES (
                        1,
                        'system_maintenance',
                        'Weekly temporary files cleanup completed',
                        NOW()
                    )
            ");
        }

        if (!DB::select("SELECT * FROM information_schema.events WHERE event_schema = DATABASE() AND event_name = 'optimize_tables'")) {
            DB::statement("
                CREATE EVENT optimize_tables
                ON SCHEDULE EVERY 1 MONTH
                STARTS CONCAT(DATE_FORMAT(NOW(), '%Y-%m-01'), ' 03:00:00')
                DO
                    INSERT INTO activities (user_id, type, description, created_at)
                    VALUES (
                        1,
                        'system_maintenance',
                        'Monthly database tables optimization completed',
                        NOW()
                    )
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop events
        DB::statement("DROP EVENT IF EXISTS archive_old_sessions");
        DB::statement("DROP EVENT IF EXISTS create_database_backup");
        DB::statement("DROP EVENT IF EXISTS cleanup_temp_files");
        DB::statement("DROP EVENT IF EXISTS optimize_tables");
        
        // Drop triggers
        DB::statement("DROP TRIGGER IF EXISTS user_activity_trigger");
        DB::statement("DROP TRIGGER IF EXISTS office_equipment_count_trigger");
        DB::statement("DROP TRIGGER IF EXISTS category_usage_trigger");
        
        // Drop procedures
        DB::statement("DROP PROCEDURE IF EXISTS get_equipment_by_office");
        DB::statement("DROP PROCEDURE IF EXISTS bulk_update_equipment_status");
        
        // Drop functions
        DB::statement("DROP FUNCTION IF EXISTS get_equipment_count");
        DB::statement("DROP FUNCTION IF EXISTS get_total_equipment_cost");
        
        // Drop views
        DB::statement("DROP VIEW IF EXISTS activity_summary_view");
        DB::statement("DROP VIEW IF EXISTS category_summary_view");
        DB::statement("DROP VIEW IF EXISTS office_summary_view");
        DB::statement("DROP VIEW IF EXISTS user_summary_view");
        DB::statement("DROP VIEW IF EXISTS equipment_summary_view");
        DB::statement("DROP VIEW IF EXISTS equipment_summary");
        DB::statement("DROP VIEW IF EXISTS equipment_assignment_view");
        
        // Drop procedures
        DB::statement("DROP PROCEDURE IF EXISTS get_users_by_office");
        DB::statement("DROP PROCEDURE IF EXISTS get_equipment_by_office");
        DB::statement("DROP PROCEDURE IF EXISTS bulk_update_equipment_status");
        
        // Drop functions
        DB::statement("DROP FUNCTION IF EXISTS get_user_count");
        DB::statement("DROP FUNCTION IF EXISTS get_equipment_count");
        DB::statement("DROP FUNCTION IF EXISTS get_office_equipment_stats");
        DB::statement("DROP FUNCTION IF EXISTS get_total_equipment_cost");
    }
};
