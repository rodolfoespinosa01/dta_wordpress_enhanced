<?php
if (!defined('ABSPATH')) {
    exit;
}

class Step10 {

    public static function init() {
        add_shortcode('run_step10', [__CLASS__, 'run_step10_shortcode']);
    }

    public static function run_step10_shortcode() {
        $result = self::run('sunday'); // Hardcoded to 'sunday' for now
        return $result['message'];
    }

    public static function run($day) {
        global $wpdb;

        // Validate the day of the week
        if (!in_array($day, ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'])) {
            return ['success' => false, 'message' => 'Invalid day of the week.'];
        }

        // Get the current user ID
        $user_id = get_current_user_id();
        if (!$user_id) {
            return ['success' => false, 'message' => 'You must be logged in to run Step 10.'];
        }

        error_log("Processing Step 10 for $day for user $user_id");

        // Define table names
        $step_9_table = "{$wpdb->prefix}{$user_id}_step9_{$day}";
        $step_10_table = "{$wpdb->prefix}{$user_id}_step10_{$day}";

        // Check if Step 9 table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$step_9_table'") !== $step_9_table) {
            return ['success' => false, 'message' => "Step 9 table does not exist for $day."];
        }

        // Recreate the Step 10 table
        $wpdb->query("DROP TABLE IF EXISTS $step_10_table");
        $wpdb->query("
            CREATE TABLE $step_10_table (
                meal_number INT(10) NOT NULL,
                error_id INT(10) NOT NULL,
                meal_combo_id INT(10) NOT NULL,
                protein1_total DECIMAL(12,6),
                protein2_total DECIMAL(12,6),
                carbs1_total DECIMAL(12,6),
                carbs2_total DECIMAL(12,6),
                fats1_total DECIMAL(12,6),
                fats2_total DECIMAL(12,6),
                PRIMARY KEY (meal_number, error_id)
            );
        ");

        error_log("Step 10 table created for user $user_id for $day");

        // Fetch smallest errors from Step 9
        $smallest_errors = $wpdb->get_results("SELECT meal_number, error_id FROM $step_9_table", ARRAY_A);

        if (empty($smallest_errors)) {
            return ['success' => false, 'message' => "No smallest errors found for Step 10 for $day."];
        }

        // Loop through each smallest error and process data from Step 3
        foreach ($smallest_errors as $row) {
            $meal_number = $row['meal_number'];
            $error_id = $row['error_id'];

            // Define the Step 3 table for this meal
            $step_3_table = "{$wpdb->prefix}{$user_id}_step3_meal{$meal_number}_{$day}";

            // Check if Step 3 table exists
            if ($wpdb->get_var("SHOW TABLES LIKE '$step_3_table'") !== $step_3_table) {
                error_log("Step 3 table $step_3_table does not exist for meal $meal_number.");
                continue; // Skip if the Step 3 table does not exist
            }

            // Fetch the corresponding data from the Step 3 table
            $step_3_data = $wpdb->get_row($wpdb->prepare(
                "SELECT meal_combo_id, protein1_total, protein2_total, carbs1_total, carbs2_total, fats1_total, fats2_total
                 FROM $step_3_table
                 WHERE error_id = %d",
                $error_id
            ));

            // Skip if no data is found
            if (!$step_3_data) {
                error_log("No Step 3 data found for meal_number: $meal_number and error_id: $error_id");
                continue;
            }

            // Insert the data into the Step 10 table
            $wpdb->query($wpdb->prepare(
                "INSERT INTO $step_10_table 
                    (meal_number, error_id, meal_combo_id, protein1_total, protein2_total, carbs1_total, carbs2_total, fats1_total, fats2_total)
                 VALUES (%d, %d, %d, %f, %f, %f, %f, %f, %f)
                 ON DUPLICATE KEY UPDATE 
                    meal_combo_id = VALUES(meal_combo_id),
                    protein1_total = VALUES(protein1_total),
                    protein2_total = VALUES(protein2_total),
                    carbs1_total = VALUES(carbs1_total),
                    carbs2_total = VALUES(carbs2_total),
                    fats1_total = VALUES(fats1_total),
                    fats2_total = VALUES(fats2_total)",
                $meal_number, $error_id, $step_3_data->meal_combo_id,
                $step_3_data->protein1_total, $step_3_data->protein2_total,
                $step_3_data->carbs1_total, $step_3_data->carbs2_total,
                $step_3_data->fats1_total, $step_3_data->fats2_total
            ));
        }

        return ['success' => true, 'message' => "Step 10 completed successfully for $day!"];
    }
}

// Initialize the Step 10 class
Step10::init();
