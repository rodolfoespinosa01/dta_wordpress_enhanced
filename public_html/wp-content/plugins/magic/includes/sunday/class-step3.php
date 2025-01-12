<?php
if (!defined('ABSPATH')) {
    exit;
}

class Step3 {

    public static function init() {
        // Step 3 does not require a shortcode, as it will be triggered programmatically after Step 2
    }

    public static function run($day) {
        global $wpdb;

        // Ensure this runs only for the specified day
        if ($day !== 'sunday') {
            return ['success' => false, 'message' => 'Step 3 is configured to run only for Sunday.'];
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

        // Step 2 and Step 3 table names
        $step_2_table = "{$wpdb->prefix}{$user_id}_step2_{$day}";
        $step_3_table = "{$wpdb->prefix}{$user_id}_step3_{$day}";

        // Check if Step 2 table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$step_2_table'") !== $step_2_table) {
            return ['success' => false, 'message' => "Step 2 table for $day does not exist."];
        }

        // Recreate Step 3 table (drop and create)
        $wpdb->query("DROP TABLE IF EXISTS $step_3_table");
        $wpdb->query("
            CREATE TABLE $step_3_table (
                meal_number INT(10) NOT NULL,
                error_id INT(10) NOT NULL,
                meal_combo_id INT(10) NOT NULL,
                protein1_total DECIMAL(10,6),
                protein2_total DECIMAL(10,6),
                carbs1_total DECIMAL(10,6),
                carbs2_total DECIMAL(10,6),
                fats1_total DECIMAL(10,6),
                fats2_total DECIMAL(10,6),
                PRIMARY KEY (meal_number, error_id)
            );
        ");

        // Process each meal
        for ($meal_num = 1; $meal_num <= $day_meals; $meal_num++) {
            // Fetch the meal combo ID
            $meal_combo_id = $wpdb->get_var($wpdb->prepare("
                SELECT meal_combo_id 
                FROM {$wpdb->prefix}meal_combos 
                WHERE user_id = %d AND day_of_week = %s AND meal_number = %d",
                $user_id, $day, $meal_num
            ));
            if (!$meal_combo_id) {
                error_log("No meal_combo_id found for user $user_id, day $day, meal $meal_num");
                continue;
            }

            // Fetch combo data
            $combo_data = $wpdb->get_row($wpdb->prepare("
                SELECT p1, p2, c1, c2, f1, f2 
                FROM {$wpdb->prefix}combos 
                WHERE c1_id = %d",
                $meal_combo_id
            ));
            if (!$combo_data) {
                error_log("No combo data found for meal_combo_id $meal_combo_id");
                continue;
            }

            // Insert data into Step 3 table
$wpdb->query($wpdb->prepare("
    INSERT INTO $step_3_table (meal_number, error_id, meal_combo_id, protein1_total, protein2_total, carbs1_total, carbs2_total, fats1_total, fats2_total)
    SELECT 
        meal_number,
        error_id,
        %d AS meal_combo_id,
        GREATEST(0, pro1_n * %f) AS protein1_total,
        GREATEST(0, pro2_n * %f) AS protein2_total,
        GREATEST(0, carbs1_n * %f) AS carbs1_total,
        GREATEST(0, carbs2_n * %f) AS carbs2_total,
        GREATEST(0, fats1_n * %f) AS fats1_total,
        GREATEST(0, fats2_n * %f) AS fats2_total
    FROM $step_2_table
    WHERE meal_number = %d
    ON DUPLICATE KEY UPDATE
        protein1_total = VALUES(protein1_total),
        protein2_total = VALUES(protein2_total),
        carbs1_total = VALUES(carbs1_total),
        carbs2_total = VALUES(carbs2_total),
        fats1_total = VALUES(fats1_total),
        fats2_total = VALUES(fats2_total)",
    $meal_combo_id,
    $combo_data->p1, $combo_data->p2,
    $combo_data->c1, $combo_data->c2,
    $combo_data->f1, $combo_data->f2,
    $meal_num
));

        }

        return ['success' => true, 'message' => "Step 3 completed successfully for $day!"];
    }
}
