<?php
if (!defined('ABSPATH')) {
    exit;
}

class Step5 {

    public static function init() {
        add_shortcode('run_step5', [__CLASS__, 'run_step5_shortcode']);
    }

    public static function run_step5_shortcode() {
        global $wpdb;

        // Get the current logged-in user ID
        $user_id = get_current_user_id();
        if (!$user_id) {
            return "You must be logged in to run Step 5.";
        }

        error_log("Processing Step 5 for user $user_id");

        // Retrieve meal data to get the total number of meals
        $meal_data = $wpdb->get_var($wpdb->prepare("
            SELECT meal_data FROM {$wpdb->prefix}user_info WHERE user_id = %d", $user_id
        ));

        if (!$meal_data) {
            return "No meal data found for this user.";
        }

        // Decode the meal data to get the number of meals for Sunday
        $meal_data = json_decode($meal_data, true);
        $day = 'sunday'; // You can make this dynamic if needed
        $total_meals = $meal_data[$day]['meals'] ?? 0;

        if ($total_meals < 1) {
            return "No meals found for this user.";
        }

        // Create the Step 5 table for all meals
        $step_5_table = "{$wpdb->prefix}{$user_id}_step5_{$day}";
        $wpdb->query("
            CREATE TABLE IF NOT EXISTS $step_5_table (
                meal_number INT(10) NOT NULL,
                error_id INT(10) NOT NULL,
                meal_combo_id INT(10) NOT NULL,
                complete_protein DECIMAL(10,6),
                complete_carbs DECIMAL(10,6),
                complete_fats DECIMAL(10,6),
                PRIMARY KEY (meal_number, error_id)
            );
        ");

        error_log("Step 5 table created for user $user_id");

        // Loop through each meal (from meal 1 to total_meals)
        for ($meal_number = 1; $meal_number <= $total_meals; $meal_number++) {
            // Define the Step 4 table for the current meal and day
            $step_4_table = "{$wpdb->prefix}{$user_id}_step4_meal{$meal_number}_{$day}";

            // Check if the Step 4 table exists
            if ($wpdb->get_var("SHOW TABLES LIKE '$step_4_table'") !== $step_4_table) {
                error_log("Step 4 table $step_4_table does not exist for meal $meal_number.");
                continue;
            }

            // Insert data into Step 5 table by aggregating values from Step 4 table
            $wpdb->query($wpdb->prepare("
                INSERT INTO $step_5_table (
                    meal_number, error_id, meal_combo_id, 
                    complete_protein, complete_carbs, complete_fats
                )
                SELECT 
                    %d AS meal_number,
                    error_id,
                    meal_combo_id,
                    -- Aggregate protein, carbs, and fats
                    IFNULL(p1_protein, 0) + IFNULL(p2_protein, 0) + IFNULL(c1_protein, 0) + IFNULL(c2_protein, 0) + IFNULL(f1_protein, 0) + IFNULL(f2_protein, 0) AS complete_protein,
                    IFNULL(p1_carbs, 0) + IFNULL(p2_carbs, 0) + IFNULL(c1_carbs, 0) + IFNULL(c2_carbs, 0) + IFNULL(f1_carbs, 0) + IFNULL(f2_carbs, 0) AS complete_carbs,
                    IFNULL(p1_fats, 0) + IFNULL(p2_fats, 0) + IFNULL(c1_fats, 0) + IFNULL(c2_fats, 0) + IFNULL(f1_fats, 0) + IFNULL(f2_fats, 0) AS complete_fats
                FROM $step_4_table
                ON DUPLICATE KEY UPDATE
                    complete_protein = VALUES(complete_protein),
                    complete_carbs = VALUES(complete_carbs),
                    complete_fats = VALUES(complete_fats);
            ", $meal_number));

            // Log that the meal was processed
            error_log("Processed Step 5 for meal $meal_number for user $user_id");
        }

        return "Step 5 for all meals has been completed successfully.";
    }
}

// Initialize the Step 5 class
Step5::init();