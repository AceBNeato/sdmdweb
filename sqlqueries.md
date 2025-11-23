# SQL Objects Defined by Migrations

This document summarizes all **Indexes, Views, Stored Procedures, Functions, Triggers, and Events** created by the Laravel migrations in this project.

---

## 1. Indexes (and Unique Constraints)

> Note: Laravel generates index names automatically. Below, indexes are documented by their **columns**, which is usually what you need. These definitions are derived directly from the migration files.

### 1.1 `sessions`

Defined in `0001_01_01_000000_create_laravel_tables.php`:

```sql
-- Table: sessions
CREATE INDEX idx_sessions_user_id
    ON sessions (`user_id`);
CREATE INDEX idx_sessions_last_activity
    ON sessions (`last_activity`);
```

### 1.2 `cache`

```sql
-- Table: cache
CREATE INDEX idx_cache_key_expiration
    ON cache (`key`, `expiration`);
```

### 1.3 `cache_locks`

```sql
-- Table: cache_locks
CREATE INDEX idx_cache_locks_key_expiration
    ON cache_locks (`key`, `expiration`);
```

### 1.4 `password_reset_tokens`

```sql
-- Table: password_reset_tokens
CREATE INDEX idx_password_reset_tokens_email_token
    ON password_reset_tokens (`email`, `token`);
```

### 1.5 `personal_access_tokens`

```sql
-- Table: personal_access_tokens
CREATE UNIQUE INDEX uq_personal_access_tokens_token
    ON personal_access_tokens (`token`);
```

### 1.6 `categories`

Defined in `0001_01_02_000000_create_categories.php`:
 
```sql
-- Table: categories
CREATE UNIQUE INDEX uq_categories_name
    ON categories (`name`);
CREATE INDEX idx_categories_is_active
    ON categories (`is_active`);
```

### 1.7 `campuses`

Defined in `0001_01_03_000000_create_campuses_and_offices.php`:

```sql
-- Table: campuses
CREATE UNIQUE INDEX uq_campuses_code
    ON campuses (`code`);
CREATE INDEX idx_campuses_is_active
    ON campuses (`is_active`);
CREATE INDEX idx_campuses_code
    ON campuses (`code`);
```

### 1.8 `offices`

```sql
-- Table: offices
CREATE UNIQUE INDEX uq_offices_code
    ON offices (`code`);
CREATE INDEX idx_offices_is_active
    ON offices (`is_active`);
CREATE INDEX idx_offices_campus_id
    ON offices (`campus_id`);
CREATE INDEX idx_offices_code
    ON offices (`code`);
```

### 1.9 `roles`

Defined in `0001_01_04_000000_create_rbac_system.php`:

```sql
-- Table: roles
CREATE UNIQUE INDEX uq_roles_name
    ON roles (`name`);
CREATE INDEX idx_roles_name
    ON roles (`name`);
```

### 1.10 `permissions`

```sql
-- Table: permissions
CREATE UNIQUE INDEX uq_permissions_name
    ON permissions (`name`);
CREATE INDEX idx_permissions_is_active
    ON permissions (`is_active`);
CREATE INDEX idx_permissions_group
    ON permissions (`group`);
CREATE INDEX idx_permissions_name
    ON permissions (`name`);
```

### 1.11 `permission_role`

```sql
-- Table: permission_role
CREATE UNIQUE INDEX uq_permission_role_permission_id_role_id
    ON permission_role (`permission_id`, `role_id`);
CREATE INDEX idx_permission_role_role_id_permission_id
    ON permission_role (`role_id`, `permission_id`);

-- Additional composite indexes (0001_01_10_000000_create_database_indexes.php)
CREATE INDEX idx_permission_role_role_id
    ON permission_role (`role_id`);
CREATE INDEX idx_permission_role_permission_id
    ON permission_role (`permission_id`);
```

### 1.12 `users`

From `0001_01_04_000000_create_rbac_system.php` and
`0001_01_10_000000_create_database_indexes.php`:

```sql
-- Table: users
CREATE UNIQUE INDEX uq_users_email
    ON users (`email`);

-- Base indexes
CREATE INDEX idx_users_is_active
    ON users (`is_active`);
CREATE INDEX idx_users_office_id
    ON users (`office_id`);
CREATE INDEX idx_users_campus_id
    ON users (`campus_id`);
CREATE INDEX idx_users_role_id
    ON users (`role_id`);
CREATE INDEX idx_users_is_available
    ON users (`is_available`);
CREATE INDEX idx_users_specialization
    ON users (`specialization`);
CREATE INDEX idx_users_email_index
    ON users (`email`);

-- Additional composite/performance indexes
CREATE INDEX idx_users_role_is_active
    ON users (`role_id`, `is_active`);
CREATE INDEX idx_users_office_is_active
    ON users (`office_id`, `is_active`);
CREATE INDEX idx_users_campus_is_active
    ON users (`campus_id`, `is_active`);
CREATE INDEX idx_users_first_last_name
    ON users (`first_name`, `last_name`);
CREATE INDEX idx_users_email_verified_at
    ON users (`email_verified_at`);
```

### 1.13 `role_user`

```sql
-- Table: role_user
CREATE UNIQUE INDEX uq_role_user_role_id_user_id
    ON role_user (`role_id`, `user_id`);
CREATE INDEX idx_role_user_user_id_role_id
    ON role_user (`user_id`, `role_id`);

-- Additional single-column indexes (0001_01_10_000000_create_database_indexes.php)
CREATE INDEX idx_role_user_user_id
    ON role_user (`user_id`);
CREATE INDEX idx_role_user_role_id
    ON role_user (`role_id`);
```

### 1.14 `equipment_types`

Defined in `0001_01_05_000000_create_equipment_system.php`:

```sql
-- Table: equipment_types
CREATE UNIQUE INDEX uq_equipment_types_name
    ON equipment_types (`name`);
CREATE INDEX idx_equipment_types_is_active
    ON equipment_types (`is_active`);
```

### 1.15 `equipment`

```sql
-- Table: equipment
CREATE UNIQUE INDEX uq_equipment_serial_number
    ON equipment (`serial_number`);
CREATE UNIQUE INDEX uq_equipment_office_serial_number
    ON equipment (`office_id`, `serial_number`);
CREATE UNIQUE INDEX uq_equipment_qr_code
    ON equipment (`qr_code`);  -- column is defined as unique

-- Base indexes
CREATE INDEX idx_equipment_assigned_to
    ON equipment (`assigned_to_type`, `assigned_to_id`);
CREATE INDEX idx_equipment_office_id
    ON equipment (`office_id`);
CREATE INDEX idx_equipment_assigned_by_id
    ON equipment (`assigned_by_id`);
CREATE INDEX idx_equipment_status
    ON equipment (`status`);
CREATE INDEX idx_equipment_category_id
    ON equipment (`category_id`);
CREATE INDEX idx_equipment_equipment_type_id
    ON equipment (`equipment_type_id`);
CREATE INDEX idx_equipment_status_office_id
    ON equipment (`status`, `office_id`);
CREATE INDEX idx_equipment_serial_number_index
    ON equipment (`serial_number`);
CREATE INDEX idx_equipment_purchase_date
    ON equipment (`purchase_date`);

-- Additional composite/performance indexes (0001_01_10_000000_create_database_indexes.php)
CREATE INDEX idx_equipment_office_status
    ON equipment (`office_id`, `status`);
CREATE INDEX idx_equipment_type_status
    ON equipment (`equipment_type_id`, `status`);
CREATE INDEX idx_equipment_category_status
    ON equipment (`category_id`, `status`);
CREATE INDEX idx_equipment_purchase_date_status
    ON equipment (`purchase_date`, `status`);
CREATE INDEX idx_equipment_qr_code_index
    ON equipment (`qr_code`);  -- extra non-unique index
CREATE INDEX idx_equipment_assigned_full
    ON equipment (`assigned_to_type`, `assigned_to_id`, `assigned_at`);
```

### 1.16 `equipment_history`

From `0001_01_06_000000_create_equipment_history.php` and
`0001_01_10_000000_create_database_indexes.php`:

```sql
-- Table: equipment_history
-- Base indexes
CREATE INDEX idx_equipment_history_equipment_id
    ON equipment_history (`equipment_id`);
CREATE INDEX idx_equipment_history_user_id
    ON equipment_history (`user_id`);
CREATE INDEX idx_equipment_history_jo_number
    ON equipment_history (`jo_number`);
CREATE INDEX idx_equipment_history_date
    ON equipment_history (`date`);

-- Additional composite indexes
CREATE INDEX idx_eh_equipment_action
    ON equipment_history (`equipment_id`, `action_taken`);
CREATE INDEX idx_eh_user_action
    ON equipment_history (`user_id`, `action_taken`);
CREATE INDEX idx_eh_equipment_user
    ON equipment_history (`equipment_id`, `user_id`);
CREATE INDEX idx_eh_date_equipment
    ON equipment_history (`date`, `equipment_id`);
```

### 1.17 `activities`

From `0001_01_07_000000_create_activities_and_settings.php` and
`0001_01_10_000000_create_database_indexes.php`:

```sql
-- Table: activities
-- Base indexes
CREATE INDEX idx_activities_user_created_at
    ON activities (`user_id`, `created_at`);
CREATE INDEX idx_activities_type_created_at
    ON activities (`type`, `created_at`);

-- Additional composite indexes
CREATE INDEX idx_activities_user_type
    ON activities (`user_id`, `type`);
CREATE INDEX idx_activities_created_user
    ON activities (`created_at`, `user_id`);
CREATE INDEX idx_activities_created_type
    ON activities (`created_at`, `type`);
```

### 1.18 `settings`

```sql
-- Table: settings
CREATE UNIQUE INDEX uq_settings_key
    ON settings (`key`);
CREATE INDEX idx_settings_key
    ON settings (`key`);
CREATE INDEX idx_settings_is_public
    ON settings (`is_public`);
```

### 1.19 `password_reset_otps`

Defined in `0001_01_08_000000_create_password_reset_otps.php`:

```sql
-- Table: password_reset_otps
CREATE INDEX idx_pro_email
    ON password_reset_otps (`email`);
CREATE UNIQUE INDEX uq_pro_token
    ON password_reset_otps (`token`);

-- Additional indexes
CREATE INDEX idx_pro_email_is_used
    ON password_reset_otps (`email`, `is_used`);
CREATE INDEX idx_pro_user_is_used
    ON password_reset_otps (`user_id`, `is_used`);
CREATE INDEX idx_pro_expires_at
    ON password_reset_otps (`expires_at`);
CREATE INDEX idx_pro_otp
    ON password_reset_otps (`otp`);
CREATE INDEX idx_pro_token_index
    ON password_reset_otps (`token`);
```

---

## 2. Views

All view definitions are from
`database/migrations/0001_01_09_000000_create_database_objects.php`.

### 2.1 `user_summary_view`

```sql
CREATE VIEW user_summary_view AS
SELECT 
    u.id,
    CONCAT(u.first_name, ' ', u.last_name) AS full_name,
    u.email,
    u.position,
    u.phone,
    u.is_active,
    u.is_available,
    u.specialization,
    u.employee_id,
    r.name AS role_name,
    o.name AS office_name,
    c.name AS campus_name,
    COUNT(e.id) AS equipment_count,
    u.created_at
FROM users u
LEFT JOIN roles    r ON u.role_id   = r.id
LEFT JOIN offices  o ON u.office_id = o.id
LEFT JOIN campuses c ON u.campus_id = c.id
LEFT JOIN equipment e 
       ON e.assigned_to_id   = u.id
      AND e.assigned_to_type = 'user'
WHERE u.deleted_at IS NULL
GROUP BY
    u.id, u.first_name, u.last_name, u.email, u.position, u.phone,
    u.is_active, u.is_available, u.specialization, u.employee_id,
    r.name, o.name, c.name, u.created_at;
```

### 2.2 `office_summary_view`

```sql
CREATE VIEW office_summary_view AS
SELECT 
    o.id,
    o.code,
    o.name,
    o.location,
    o.contact_number,
    o.email,
    o.is_active,
    c.name AS campus_name,
    COUNT(u.id) AS user_count,
    COUNT(e.id) AS equipment_count,
    COALESCE(SUM(e.cost_of_purchase), 0) AS total_equipment_cost,
    o.created_at
FROM offices o
LEFT JOIN campuses c ON o.campus_id = c.id
LEFT JOIN users    u ON u.office_id = o.id AND u.deleted_at IS NULL
LEFT JOIN equipment e ON e.office_id = o.id AND e.deleted_at IS NULL
WHERE o.deleted_at IS NULL
GROUP BY
    o.id, o.code, o.name, o.location, o.contact_number, o.email,
    o.is_active, c.name, o.created_at;
```

### 2.3 `category_summary_view`

```sql
CREATE VIEW category_summary_view AS
SELECT 
    cat.id,
    cat.name,
    cat.description,
    cat.is_active,
    COUNT(e.id) AS equipment_count,
    COALESCE(SUM(e.cost_of_purchase), 0) AS total_equipment_cost,
    COUNT(CASE WHEN e.status = 'serviceable' THEN 1 END) AS serviceable_count,
    COUNT(CASE WHEN e.status = 'for_repair'  THEN 1 END) AS repair_count,
    COUNT(CASE WHEN e.status = 'defective'   THEN 1 END) AS defective_count,
    cat.created_at
FROM categories cat
LEFT JOIN equipment e 
       ON e.category_id = cat.id 
      AND e.deleted_at IS NULL
WHERE cat.deleted_at IS NULL
GROUP BY
    cat.id, cat.name, cat.description, cat.is_active, cat.created_at;
```

### 2.4 `activity_summary_view`

```sql
CREATE VIEW activity_summary_view AS
SELECT 
    a.id,
    a.type,
    a.description,
    a.created_at,
    CONCAT(u.first_name, ' ', u.last_name) AS user_name,
    u.email AS user_email,
    r.name AS user_role,
    o.name AS office_name
FROM activities a
LEFT JOIN users u ON a.user_id = u.id
LEFT JOIN roles r ON u.role_id = r.id
LEFT JOIN offices o ON u.office_id = o.id
ORDER BY a.created_at DESC;
```

### 2.5 `equipment_summary_view`

```sql
CREATE VIEW equipment_summary_view AS
SELECT 
    e.id,
    CONCAT(e.brand, ' ', e.model_number) AS name,
    e.serial_number,
    e.status,
    e.`condition`,
    e.office_id,
    o.name AS office_name,
    e.equipment_type_id,
    et.name AS equipment_type_name,
    e.category_id,
    c.name AS category_name,
    COUNT(eh.id) AS history_count,
    MAX(eh.date) AS last_activity_date
FROM equipment e
LEFT JOIN offices          o  ON e.office_id        = o.id
LEFT JOIN equipment_types  et ON e.equipment_type_id = et.id
LEFT JOIN categories       c  ON e.category_id      = c.id
LEFT JOIN equipment_history eh ON e.id              = eh.equipment_id
WHERE e.deleted_at IS NULL
GROUP BY
    e.id, e.brand, e.model_number, e.serial_number, e.status, e.`condition`,
    e.office_id, o.name, e.equipment_type_id, et.name,
    e.category_id, c.name;
```

### 2.6 `equipment_summary`

```sql
CREATE VIEW equipment_summary AS
SELECT 
    e.id,
    CONCAT(e.brand, ' ', e.model_number) AS name,
    e.serial_number,
    e.description,
    e.status,
    e.purchase_date,
    e.cost_of_purchase AS purchase_cost,
    e.model_number AS model,
    e.qr_code,
    e.office_id,
    e.category_id,
    o.name  AS office_name,
    cat.name AS category_name,
    e.created_at,
    e.updated_at
FROM equipment e
LEFT JOIN offices    o   ON e.office_id  = o.id
LEFT JOIN categories cat ON e.category_id = cat.id;
```

### 2.7 `equipment_assignment_view`

```sql
CREATE VIEW equipment_assignment_view AS
SELECT 
    e.id,
    e.brand,
    e.model_number,
    e.serial_number,
    e.status,
    e.assigned_to_type,
    e.assigned_to_id,
    e.assigned_at,
    u.first_name,
    u.last_name,
    o.name AS office_name
FROM equipment e
LEFT JOIN users   u ON e.assigned_to_id = u.id
LEFT JOIN offices o ON e.office_id      = o.id
WHERE e.assigned_to_type IS NOT NULL;
```

---

## 3. Functions

From `0001_01_09_000000_create_database_objects.php`.

### 3.1 `get_user_count`

```sql
CREATE FUNCTION get_user_count(
    p_office_id INT,
    p_role_id   INT,
    p_is_active BOOLEAN
) RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE user_count INT;
    
    SELECT COUNT(*) INTO user_count
    FROM users
    WHERE (p_office_id IS NULL OR office_id = p_office_id)
      AND (p_role_id   IS NULL OR role_id   = p_role_id)
      AND (p_is_active IS NULL OR is_active = p_is_active)
      AND deleted_at IS NULL;
    
    RETURN user_count;
END;
```

### 3.2 `get_equipment_count`

```sql
CREATE FUNCTION get_equipment_count(
    p_office_id INT,
    p_status    VARCHAR(20)
) RETURNS INT
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
END;
```

### 3.3 `get_office_equipment_stats`

```sql
CREATE FUNCTION get_office_equipment_stats(
    p_office_id INT
) RETURNS JSON
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE stats JSON;
    
    SELECT JSON_OBJECT(
        'total',       COUNT(*),
        'serviceable', COUNT(CASE WHEN status = 'serviceable' THEN 1 END),
        'for_repair',  COUNT(CASE WHEN status = 'for_repair'  THEN 1 END),
        'defective',   COUNT(CASE WHEN status = 'defective'   THEN 1 END),
        'total_cost',  COALESCE(SUM(cost_of_purchase), 0)
    ) INTO stats
    FROM equipment
    WHERE office_id = p_office_id
      AND deleted_at IS NULL;
    
    RETURN stats;
END;
```

### 3.4 `get_total_equipment_cost`

```sql
CREATE FUNCTION get_total_equipment_cost(
    p_office_id INT
) RETURNS DECIMAL(15,2)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE total_cost DECIMAL(15,2);
    SELECT COALESCE(SUM(cost_of_purchase), 0) INTO total_cost
    FROM equipment
    WHERE office_id = p_office_id
      AND deleted_at IS NULL;
    RETURN total_cost;
END;
```

---

## 4. Stored Procedures

From `0001_01_09_000000_create_database_objects.php`.

### 4.1 `get_users_by_office`

```sql
CREATE PROCEDURE get_users_by_office(IN p_office_id INT)
BEGIN
    SELECT 
        u.id,
        CONCAT(u.first_name, ' ', u.last_name) AS full_name,
        u.email,
        u.position,
        u.is_active,
        u.is_available,
        r.name AS role_name,
        COUNT(e.id) AS equipment_count
    FROM users u
    LEFT JOIN roles     r ON u.role_id = r.id
    LEFT JOIN equipment e 
           ON e.assigned_to_id   = u.id
          AND e.assigned_to_type = 'user'
    WHERE u.office_id = p_office_id
      AND u.deleted_at IS NULL
    GROUP BY
        u.id, u.first_name, u.last_name, u.email, u.position,
        u.is_active, u.is_available, r.name
    ORDER BY u.first_name, u.last_name;
END;
```

### 4.2 `transfer_user_equipment`

```sql
CREATE PROCEDURE transfer_user_equipment(
    IN p_from_user_id INT,
    IN p_to_user_id   INT,
    IN p_notes        TEXT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    UPDATE equipment
    SET assigned_to_id = p_to_user_id,
        assigned_at    = NOW(),
        updated_at     = NOW()
    WHERE assigned_to_id   = p_from_user_id
      AND assigned_to_type = 'user';
    
    INSERT INTO activities (user_id, type, description, created_at)
    VALUES (
        p_from_user_id,
        'equipment_transfer',
        CONCAT(
            'Transferred all equipment from user ',
            p_from_user_id, ' to user ', p_to_user_id,
            IFNULL(CONCAT(': ', p_notes), '')
        ),
        NOW()
    );
    
    COMMIT;
END;
```

### 4.3 `get_equipment_by_office`

```sql
CREATE PROCEDURE get_equipment_by_office(IN p_office_id INT)
BEGIN
    SELECT 
        e.id,
        CONCAT(e.brand, ' ', e.model_number) AS name,
        e.serial_number,
        e.status,
        e.`condition`,
        et.name AS equipment_type_name,
        c.name  AS category_name
    FROM equipment e
    LEFT JOIN equipment_types et ON e.equipment_type_id = et.id
    LEFT JOIN categories      c  ON e.category_id       = c.id
    WHERE e.office_id = p_office_id
      AND e.deleted_at IS NULL
    ORDER BY e.brand, e.model_number;
END;
```

### 4.4 `bulk_update_equipment_status`

```sql
CREATE PROCEDURE bulk_update_equipment_status(
    IN p_status        VARCHAR(50),
    IN p_equipment_ids TEXT
)
BEGIN
    UPDATE equipment
    SET status     = p_status,
        updated_at = NOW()
    WHERE FIND_IN_SET(id, p_equipment_ids) > 0;
END;
```

---

## 5. Triggers

From `0001_01_09_000000_create_database_objects.php`.

### 5.1 `user_activity_trigger`

```sql
CREATE TRIGGER user_activity_trigger
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    IF OLD.is_active != NEW.is_active OR OLD.role_id != NEW.role_id THEN
        INSERT INTO activities (user_id, type, description, created_at)
        VALUES (
            NEW.id,
            'user_status_change',
            CONCAT(
                CASE 
                    WHEN OLD.is_active != NEW.is_active THEN 
                        CASE 
                            WHEN NEW.is_active THEN 'User reactivated' 
                            ELSE 'User deactivated' 
                        END
                    WHEN OLD.role_id != NEW.role_id THEN 'User role changed'
                    ELSE 'User status updated'
                END,
                ' by system'
            ),
            NOW()
        );
    END IF;
END;
```

### 5.2 `office_equipment_count_trigger`

```sql
CREATE TRIGGER office_equipment_count_trigger
AFTER INSERT ON equipment
FOR EACH ROW
BEGIN
    IF NEW.office_id IS NOT NULL THEN
        INSERT INTO activities (user_id, type, description, created_at)
        VALUES (
            COALESCE(NEW.assigned_by_id, 1),
            'equipment_added_to_office',
            CONCAT(
                'Equipment ', NEW.brand, ' ', NEW.model_number,
                ' added to office ID ', NEW.office_id
            ),
            NOW()
        );
    END IF;
END;
```

### 5.3 `category_usage_trigger`

```sql
CREATE TRIGGER category_usage_trigger
AFTER UPDATE ON equipment
FOR EACH ROW
BEGIN
    IF OLD.category_id != NEW.category_id AND NEW.category_id IS NOT NULL THEN
        INSERT INTO activities (user_id, type, description, created_at)
        VALUES (
            COALESCE(NEW.assigned_by_id, 1),
            'category_change',
            CONCAT(
                'Equipment moved to category: ',
                (SELECT name FROM categories WHERE id = NEW.category_id)
            ),
            NOW()
        );
    END IF;
END;
```

---

## 6. Events (MySQL Event Scheduler)

Also from `0001_01_09_000000_create_database_objects.php`.

> The migrations also run:
> ```sql
> SET GLOBAL event_scheduler = ON;
> ```

### 6.1 `archive_old_sessions`

```sql
CREATE EVENT archive_old_sessions
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP + INTERVAL 2 HOUR
DO
    DELETE FROM sessions 
    WHERE last_activity < DATE_SUB(NOW(), INTERVAL 1 WEEK);
```

### 6.2 `create_database_backup`

```sql
CREATE EVENT create_database_backup
ON SCHEDULE EVERY 1 DAY
STARTS CONCAT(DATE_FORMAT(NOW(), '%Y-%m-%d'), ' 02:00:00')
DO
    INSERT INTO activities (user_id, type, description, created_at)
    VALUES (
        1,
        'backup_trigger',
        CONCAT(
            'Database backup triggered by event: ',
            DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s')
        ),
        NOW()
    );
```

### 6.3 `cleanup_temp_files`

```sql
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
    );
```

### 6.4 `optimize_tables`

```sql
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
    );
```

