<?php
if (!defined('ABSPATH')) {
    exit;
}

class Step4 {

    public static function run($day) {
        global $wpdb;

        // Ensure this runs only for the given day (Sunday in this case)
        if ($day !== 'sunday') {
            return ['success' => false, 'message' => 'Step 4 is configured to run only for Sunday.'];
        }

        // Get the current logged-in user ID
        $user_id = get_current_user_id();
        if (!$user_id) {
            return ['success' => false, 'message' => 'You must be logged in to run this step.'];
        }

        // Check if Step 3 table exists
        $step_3_table = "{$wpdb->prefix}{$user_id}_step3_sunday";
        $step_4_table = "{$wpdb->prefix}{$user_id}_step4_sunday";

        if ($wpdb->get_var("SHOW TABLES LIKE '$step_3_table'") !== $step_3_table) {
            return ['success' => false, 'message' => 'Step 3 table for Sunday does not exist.'];
        }

        // Recreate Step 4 table
        $wpdb->query("DROP TABLE IF EXISTS $step_4_table");
        $wpdb->query("
            CREATE TABLE $step_4_table (
                meal_number INT(10) NOT NULL,
                error_id INT(10) NOT NULL,
                meal_combo_id INT(10) NOT NULL,
                p1_protein DECIMAL(10,6),
                p1_carbs DECIMAL(10,6),
                p1_fats DECIMAL(10,6),
                p2_protein DECIMAL(10,6),
                p2_carbs DECIMAL(10,6),
                p2_fats DECIMAL(10,6),
                c1_protein DECIMAL(10,6),
                c1_carbs DECIMAL(10,6),
                c1_fats DECIMAL(10,6),
                c2_protein DECIMAL(10,6),
                c2_carbs DECIMAL(10,6),
                c2_fats DECIMAL(10,6),
                f1_protein DECIMAL(10,6),
                f1_carbs DECIMAL(10,6),
                f1_fats DECIMAL(10,6),
                f2_protein DECIMAL(10,6),
                f2_carbs DECIMAL(10,6),
                f2_fats DECIMAL(10,6),
                PRIMARY KEY (meal_number, error_id)
            );
        ");

        // Insert data into Step 4 table
        $wpdb->query("
    INSERT INTO $step_4_table (
        meal_number, error_id, meal_combo_id, p1_protein, p1_carbs, p1_fats, 
        p2_protein, p2_carbs, p2_fats, c1_protein, c1_carbs, c1_fats, 
        c2_protein, c2_carbs, c2_fats, f1_protein, f1_carbs, f1_fats, 
        f2_protein, f2_carbs, f2_fats
    )
    SELECT 
        s3.meal_number,
        s3.error_id,
        s3.meal_combo_id,

        -- Protein 1
        COALESCE(s3.protein1_total * (
            SELECT fm.protein 
            FROM {$wpdb->prefix}food_macros fm
            JOIN {$wpdb->prefix}combos c 
            ON fm.name = c.c1_protein_1
            WHERE c.c1_id = s3.meal_combo_id
        ), 0) AS p1_protein,

        -- Carbs 1
        COALESCE(s3.protein1_total * (
            SELECT fm.carbs 
            FROM {$wpdb->prefix}food_macros fm
            JOIN {$wpdb->prefix}combos c 
            ON fm.name = c.c1_protein_1
            WHERE c.c1_id = s3.meal_combo_id
        ), 0) AS p1_carbs,

        -- Fats 1
        COALESCE(s3.protein1_total * (
            SELECT fm.fats 
            FROM {$wpdb->prefix}food_macros fm
            JOIN {$wpdb->prefix}combos c 
            ON fm.name = c.c1_protein_1
            WHERE c.c1_id = s3.meal_combo_id
        ), 0) AS p1_fats,

        -- Protein 2
        COALESCE(s3.protein2_total * (
            SELECT fm.protein 
            FROM {$wpdb->prefix}food_macros fm
            JOIN {$wpdb->prefix}combos c 
            ON fm.name = c.c1_protein_2
            WHERE c.c1_id = s3.meal_combo_id
        ), 0) AS p2_protein,

        -- Carbs 2
        COALESCE(s3.protein2_total * (
            SELECT fm.carbs 
            FROM {$wpdb->prefix}food_macros fm
            JOIN {$wpdb->prefix}combos c 
            ON fm.name = c.c1_protein_2
            WHERE c.c1_id = s3.meal_combo_id
        ), 0) AS p2_carbs,

        -- Fats 2
        COALESCE(s3.protein2_total * (
            SELECT fm.fats 
            FROM {$wpdb->prefix}food_macros fm
            JOIN {$wpdb->prefix}combos c 
            ON fm.name = c.c1_protein_2
            WHERE c.c1_id = s3.meal_combo_id
        ), 0) AS p2_fats,

        -- Carbs 1
        COALESCE(s3.carbs1_total * (
            SELECT fm.protein 
            FROM {$wpdb->prefix}food_macros fm
            JOIN {$wpdb->prefix}combos c 
            ON fm.name = c.c1_carbs_1
            WHERE c.c1_id = s3.meal_combo_id
        ), 0) AS c1_protein,
        COALESCE(s3.carbs1_total * (
            SELECT fm.carbs 
            FROM {$wpdb->prefix}food_macros fm
            JOIN {$wpdb->prefix}combos c 
            ON fm.name = c.c1_carbs_1
            WHERE c.c1_id = s3.meal_combo_id
        ), 0) AS c1_carbs,
        COALESCE(s3.carbs1_total * (
            SELECT fm.fats 
            FROM {$wpdb->prefix}food_macros fm
            JOIN {$wpdb->prefix}combos c 
            ON fm.name = c.c1_carbs_1
            WHERE c.c1_id = s3.meal_combo_id
        ), 0) AS c1_fats,

        -- Carbs 2
        COALESCE(s3.carbs2_total * (
            SELECT fm.protein 
            FROM {$wpdb->prefix}food_macros fm
            JOIN {$wpdb->prefix}combos c 
            ON fm.name = c.c1_carbs_2
            WHERE c.c1_id = s3.meal_combo_id
        ), 0) AS c2_protein,
        COALESCE(s3.carbs2_total * (
            SELECT fm.carbs 
            FROM {$wpdb->prefix}food_macros fm
            JOIN {$wpdb->prefix}combos c 
            ON fm.name = c.c1_carbs_2
            WHERE c.c1_id = s3.meal_combo_id
        ), 0) AS c2_carbs,
        COALESCE(s3.carbs2_total * (
            SELECT fm.fats 
            FROM {$wpdb->prefix}food_macros fm
            JOIN {$wpdb->prefix}combos c 
            ON fm.name = c.c1_carbs_2
            WHERE c.c1_id = s3.meal_combo_id
        ), 0) AS c2_fats,

        -- Fats 1
        COALESCE(s3.fats1_total * (
            SELECT fm.protein 
            FROM {$wpdb->prefix}food_macros fm
            JOIN {$wpdb->prefix}combos c 
            ON fm.name = c.c1_fats_1
            WHERE c.c1_id = s3.meal_combo_id
        ), 0) AS f1_protein,
        COALESCE(s3.fats1_total * (
            SELECT fm.carbs 
            FROM {$wpdb->prefix}food_macros fm
            JOIN {$wpdb->prefix}combos c 
            ON fm.name = c.c1_fats_1
            WHERE c.c1_id = s3.meal_combo_id
        ), 0) AS f1_carbs,
        COALESCE(s3.fats1_total * (
            SELECT fm.fats 
            FROM {$wpdb->prefix}food_macros fm
            JOIN {$wpdb->prefix}combos c 
            ON fm.name = c.c1_fats_1
            WHERE c.c1_id = s3.meal_combo_id
        ), 0) AS f1_fats,

        -- Fats 2
        COALESCE(s3.fats2_total * (
            SELECT fm.protein 
            FROM {$wpdb->prefix}food_macros fm
            JOIN {$wpdb->prefix}combos c 
            ON fm.name = c.c1_fats_2
            WHERE c.c1_id = s3.meal_combo_id
        ), 0) AS f2_protein,
        COALESCE(s3.fats2_total * (
            SELECT fm.carbs 
            FROM {$wpdb->prefix}food_macros fm
            JOIN {$wpdb->prefix}combos c 
            ON fm.name = c.c1_fats_2
            WHERE c.c1_id = s3.meal_combo_id
        ), 0) AS f2_carbs,
        COALESCE(s3.fats2_total * (
            SELECT fm.fats 
            FROM {$wpdb->prefix}food_macros fm
            JOIN {$wpdb->prefix}combos c 
            ON fm.name = c.c1_fats_2
            WHERE c.c1_id = s3.meal_combo_id
        ), 0) AS f2_fats

    FROM $step_3_table AS s3
    ON DUPLICATE KEY UPDATE 
        p1_protein = VALUES(p1_protein),
        p1_carbs = VALUES(p1_carbs),
        p1_fats = VALUES(p1_fats),
        p2_protein = VALUES(p2_protein),
        p2_carbs = VALUES(p2_carbs),
        p2_fats = VALUES(p2_fats),
        c1_protein = VALUES(c1_protein),
        c1_carbs = VALUES(c1_carbs),
        c1_fats = VALUES(c1_fats),
        c2_protein = VALUES(c2_protein),
        c2_carbs = VALUES(c2_carbs),
        c2_fats = VALUES(c2_fats),
        f1_protein = VALUES(f1_protein),
        f1_carbs = VALUES(f1_carbs),
        f1_fats = VALUES(f1_fats),
        f2_protein = VALUES(f2_protein),
        f2_carbs = VALUES(f2_carbs),
        f2_fats = VALUES(f2_fats);
");


        return ['success' => true, 'message' => 'Step 4 completed successfully for all meals on Sunday.'];
    }
}
