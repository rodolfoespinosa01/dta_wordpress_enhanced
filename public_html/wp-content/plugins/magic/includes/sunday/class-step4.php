<?php
if (!defined('ABSPATH')) {
    exit;
}

class Step4 {

    public static function init() {
        // Register the shortcode for Step 4
        add_shortcode('run_step4', [__CLASS__, 'run_step4_shortcode']);
    }

    public static function run_step4_shortcode() {
        $day = 'sunday'; // Hardcoded for Sunday for now
        $result = self::run($day);
        return $result['message'];
    }

    public static function run($day) {
        global $wpdb;

        // Validate the day of the week
        if (!in_array($day, ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'])) {
            return ['success' => false, 'message' => 'Invalid day of the week.'];
        }

        // Get the current logged-in user ID
        $user_id = get_current_user_id();
        if (!$user_id) {
            return ['success' => false, 'message' => 'You must be logged in to run this step.'];
        }

        // Retrieve user meal data
        $meal_data = $wpdb->get_var($wpdb->prepare("
            SELECT meal_data FROM {$wpdb->prefix}user_info WHERE user_id = %d",
            $user_id
        ));
        if (!$meal_data) {
            return ['success' => false, 'message' => 'No meal data found for this user.'];
        }

        // Decode the meal data
        $meal_data = json_decode($meal_data, true);
        if (json_last_error() !== JSON_ERROR_NONE || !isset($meal_data[$day])) {
            return ['success' => false, 'message' => 'Invalid or missing meal data for the selected day.'];
        }

        $day_meals = $meal_data[$day]['meals'] ?? 0;
        if ($day_meals < 1) {
            return ['success' => false, 'message' => "No meals found for $day."];
        }

        // Loop through each meal
        for ($meal_num = 1; $meal_num <= $day_meals; $meal_num++) {
            // Step 3 and Step 4 table names
            $step_3_table = "{$wpdb->prefix}{$user_id}_step3_meal{$meal_num}_{$day}";
            $step_4_table = "{$wpdb->prefix}{$user_id}_step4_meal{$meal_num}_{$day}";

            // Check if Step 3 table exists
            if ($wpdb->get_var("SHOW TABLES LIKE '$step_3_table'") !== $step_3_table) {
                error_log("Step 3 table for Meal $meal_num on $day does not exist.");
                continue;
            }

            // Create Step 4 table for this meal
            $wpdb->query("
                CREATE TABLE IF NOT EXISTS $step_4_table (
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
            $wpdb->query($wpdb->prepare("
    INSERT INTO $step_4_table (
        meal_number, error_id, meal_combo_id, 
        p1_protein, p1_carbs, p1_fats, 
        p2_protein, p2_carbs, p2_fats, 
        c1_protein, c1_carbs, c1_fats, 
        c2_protein, c2_carbs, c2_fats, 
        f1_protein, f1_carbs, f1_fats, 
        f2_protein, f2_carbs, f2_fats
    )
    SELECT 
        step3.meal_number,
        step3.error_id,
        step3.meal_combo_id,

        -- Protein 1
        IFNULL(step3.protein1_total * (
            SELECT fm.protein FROM {$wpdb->prefix}food_macros fm
            JOIN {$wpdb->prefix}combos c ON c.c1_protein_1 = fm.name
            WHERE c.c1_id = step3.meal_combo_id
        ), 0) AS p1_protein,

        -- Carbs 1
        IFNULL(step3.protein1_total * (
            SELECT fm.carbs FROM {$wpdb->prefix}food_macros fm
            JOIN {$wpdb->prefix}combos c ON c.c1_protein_1 = fm.name
            WHERE c.c1_id = step3.meal_combo_id
        ), 0) AS p1_carbs,

        -- Fats 1
        IFNULL(step3.protein1_total * (
            SELECT fm.fats FROM {$wpdb->prefix}food_macros fm
            JOIN {$wpdb->prefix}combos c ON c.c1_protein_1 = fm.name
            WHERE c.c1_id = step3.meal_combo_id
        ), 0) AS p1_fats,

        -- Protein 2
        IFNULL(step3.protein2_total * (
            SELECT fm.protein FROM {$wpdb->prefix}food_macros fm
            JOIN {$wpdb->prefix}combos c ON c.c1_protein_2 = fm.name
            WHERE c.c1_id = step3.meal_combo_id
        ), 0) AS p2_protein,

        -- Carbs 2
        IFNULL(step3.protein2_total * (
            SELECT fm.carbs FROM {$wpdb->prefix}food_macros fm
            JOIN {$wpdb->prefix}combos c ON c.c1_protein_2 = fm.name
            WHERE c.c1_id = step3.meal_combo_id
        ), 0) AS p2_carbs,

        -- Fats 2
        IFNULL(step3.protein2_total * (
            SELECT fm.fats FROM {$wpdb->prefix}food_macros fm
            JOIN {$wpdb->prefix}combos c ON c.c1_protein_2 = fm.name
            WHERE c.c1_id = step3.meal_combo_id
        ), 0) AS p2_fats,

        -- Carbs 1 totals
        IFNULL(step3.carbs1_total * (
            SELECT fm.protein FROM {$wpdb->prefix}food_macros fm
            JOIN {$wpdb->prefix}combos c ON c.c1_carbs_1 = fm.name
            WHERE c.c1_id = step3.meal_combo_id
        ), 0) AS c1_protein,

        IFNULL(step3.carbs1_total * (
            SELECT fm.carbs FROM {$wpdb->prefix}food_macros fm
            JOIN {$wpdb->prefix}combos c ON c.c1_carbs_1 = fm.name
            WHERE c.c1_id = step3.meal_combo_id
        ), 0) AS c1_carbs,

        IFNULL(step3.carbs1_total * (
            SELECT fm.fats FROM {$wpdb->prefix}food_macros fm
            JOIN {$wpdb->prefix}combos c ON c.c1_carbs_1 = fm.name
            WHERE c.c1_id = step3.meal_combo_id
        ), 0) AS c1_fats,

        -- Carbs 2 totals
        IFNULL(step3.carbs2_total * (
            SELECT fm.protein FROM {$wpdb->prefix}food_macros fm
            JOIN {$wpdb->prefix}combos c ON c.c1_carbs_2 = fm.name
            WHERE c.c1_id = step3.meal_combo_id
        ), 0) AS c2_protein,

        IFNULL(step3.carbs2_total * (
            SELECT fm.carbs FROM {$wpdb->prefix}food_macros fm
            JOIN {$wpdb->prefix}combos c ON c.c1_carbs_2 = fm.name
            WHERE c.c1_id = step3.meal_combo_id
        ), 0) AS c2_carbs,

        IFNULL(step3.carbs2_total * (
            SELECT fm.fats FROM {$wpdb->prefix}food_macros fm
            JOIN {$wpdb->prefix}combos c ON c.c1_carbs_2 = fm.name
            WHERE c.c1_id = step3.meal_combo_id
        ), 0) AS c2_fats,

        -- Fats 1 totals
        IFNULL(step3.fats1_total * (
            SELECT fm.protein FROM {$wpdb->prefix}food_macros fm
            JOIN {$wpdb->prefix}combos c ON c.c1_fats_1 = fm.name
            WHERE c.c1_id = step3.meal_combo_id
        ), 0) AS f1_protein,

        IFNULL(step3.fats1_total * (
            SELECT fm.carbs FROM {$wpdb->prefix}food_macros fm
            JOIN {$wpdb->prefix}combos c ON c.c1_fats_1 = fm.name
            WHERE c.c1_id = step3.meal_combo_id
        ), 0) AS f1_carbs,

        IFNULL(step3.fats1_total * (
            SELECT fm.fats FROM {$wpdb->prefix}food_macros fm
            JOIN {$wpdb->prefix}combos c ON c.c1_fats_1 = fm.name
            WHERE c.c1_id = step3.meal_combo_id
        ), 0) AS f1_fats,

        -- Fats 2 totals
        IFNULL(step3.fats2_total * (
            SELECT fm.protein FROM {$wpdb->prefix}food_macros fm
            JOIN {$wpdb->prefix}combos c ON c.c1_fats_2 = fm.name
            WHERE c.c1_id = step3.meal_combo_id
        ), 0) AS f2_protein,

        IFNULL(step3.fats2_total * (
            SELECT fm.carbs FROM {$wpdb->prefix}food_macros fm
            JOIN {$wpdb->prefix}combos c ON c.c1_fats_2 = fm.name
            WHERE c.c1_id = step3.meal_combo_id
        ), 0) AS f2_carbs,

        IFNULL(step3.fats2_total * (
            SELECT fm.fats FROM {$wpdb->prefix}food_macros fm
            JOIN {$wpdb->prefix}combos c ON c.c1_fats_2 = fm.name
            WHERE c.c1_id = step3.meal_combo_id
        ), 0) AS f2_fats

    FROM $step_3_table AS step3
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
"));

        }

        return ['success' => true, 'message' => "Step 4 completed successfully for $day!"];
    }
}

// Initialize the Step 4 class
Step4::init();
