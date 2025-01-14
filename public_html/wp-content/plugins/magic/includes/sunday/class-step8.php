<?php
if (!defined('ABSPATH')) {
    exit;
}

class Step8 {

    public static function init() {
        add_shortcode('run_step8', [__CLASS__, 'run_step8_shortcode']);
    }

    public static function run_step8_shortcode() {
        $result = self::run('sunday'); // Hardcoded to 'sunday' for now
        return $result['message'];
    }

    public static function run($day) {
        global $wpdb;

        // Validate day
        if (!in_array($day, ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'])) {
            return ['success' => false, 'message' => 'Invalid day of the week.'];
        }

        // Get the current user ID
        $user_id = get_current_user_id();
        if (!$user_id) {
            return ['success' => false, 'message' => 'You must be logged in to run Step 8.'];
        }

        error_log("Processing Step 8 for $day for user $user_id");

        // Step 7 and Step 8 table names
        $step_7_table = "{$wpdb->prefix}{$user_id}_step7_{$day}";
        $step_8_table = "{$wpdb->prefix}{$user_id}_step8_{$day}";

        // Check if Step 7 table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$step_7_table'") !== $step_7_table) {
            return ['success' => false, 'message' => "Step 7 table does not exist for $day."];
        }

        // Recreate Step 8 table
        $wpdb->query("DROP TABLE IF EXISTS $step_8_table");
        $wpdb->query("
            CREATE TABLE $step_8_table (
                meal_number INT(10) NOT NULL,
                error_id INT(10) NOT NULL,
                meal_combo_id INT(10) NOT NULL,
                error_est DECIMAL(12,6),
                PRIMARY KEY (meal_number, error_id)
            );
        ");

        error_log("Step 8 table created for user $user_id for $day");

        // Fetch data from Step 7
        $step_7_data = $wpdb->get_results("SELECT * FROM $step_7_table", ARRAY_A);

        if (empty($step_7_data)) {
            return ['success' => false, 'message' => "No data available in Step 7 table for $day."];
        }

        // Prepare values for batch insert
        $query_values = [];
        foreach ($step_7_data as $row) {
            $meal_number = $row['meal_number'];
            $error_id = $row['error_id'];
            $meal_combo_id = $row['meal_combo_id'];

            // Calculate the error estimate (sum of absolute values)
            $error_est = $row['abs_pro'] + $row['abs_carbs'] + $row['abs_fats'];

            // Prepare the values for insertion
            $query_values[] = $wpdb->prepare(
                "(%d, %d, %d, %f)",
                $meal_number, $error_id, $meal_combo_id, $error_est
            );
        }

        // Execute the batch insert query
        if (!empty($query_values)) {
            $query = "
                INSERT INTO $step_8_table (meal_number, error_id, meal_combo_id, error_est)
                VALUES " . implode(", ", $query_values) . "
                ON DUPLICATE KEY UPDATE
                    error_est = VALUES(error_est)
            ";
            $wpdb->query($query);

            error_log("Step 8 data inserted for $day for user $user_id");
        }

        return ['success' => true, 'message' => "Step 8 completed successfully for $day!"];
    }
}

// Initialize the Step 8 class
Step8::init();
