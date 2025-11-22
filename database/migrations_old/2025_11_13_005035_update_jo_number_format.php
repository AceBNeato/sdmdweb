<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update the generate_jo_number procedure to use new format: JO-YY-MM-###
        DB::statement("DROP PROCEDURE IF EXISTS generate_jo_number");
        DB::statement("
            CREATE PROCEDURE generate_jo_number(
                IN p_date DATE,
                OUT p_jo_number VARCHAR(50)
            )
            BEGIN
                DECLARE sequence INT DEFAULT 1;
                DECLARE max_attempts INT DEFAULT 999;
                DECLARE attempt INT DEFAULT 0;
                DECLARE candidate_jo VARCHAR(50);

                -- Find the next available sequence number for this month (resets monthly)
                SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(jo_number, '-', -1) AS UNSIGNED)), 0) + 1
                INTO sequence
                FROM equipment_history
                WHERE YEAR(date) = YEAR(p_date) 
                  AND MONTH(date) = MONTH(p_date) 
                  AND jo_number LIKE CONCAT('JO-', DATE_FORMAT(p_date, '%y-%m'), '-%');

                -- Generate and check uniqueness
                generate_loop: WHILE attempt < max_attempts DO
                    SET candidate_jo = CONCAT('JO-', DATE_FORMAT(p_date, '%y-%m'), '-', LPAD(sequence, 3, '0'));

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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to the old format: JO-YYYY-MM-DD-##
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
    }
};
