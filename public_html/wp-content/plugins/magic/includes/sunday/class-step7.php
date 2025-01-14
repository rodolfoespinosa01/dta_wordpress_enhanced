<?php
if (!defined('ABSPATH')) {
    exit;
}

class Step7 {

    public static function init() {
        add_shortcode('run_step7', [__CLASS__, 'run_step7_shortcode']);
    }

    public static function run_step7_shortcode() {
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
            return ['success' => false, 'message' => 'You must be logged in to run Step 7.'];
        }

        error_log("Processing Step 7 for $day for user $user_id");

        // Step 6 and Step 7 table names
        $step_6_table = "{$wpdb->prefix}{$user_id}_step6_{$day}";
        $step_7_table = "{$wpdb->prefix}{$user_id}_step7_{$day}";

        // Check if Step 6 table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$step_6_table'") !== $step_6_table) {
            return ['success' => false, 'message' => "Step 6 table does not exist for $day."];
        }

        // Recreate Step 7 table
        $wpdb->query("DROP TABLE IF EXISTS $step_7_table");
        $wpdb->query("
            CREATE TABLE $step_7_table (
                meal_number INT(10) NOT NULL,
                error_id INT(10) NOT NULL,
                meal_combo_id INT(10) NOT NULL,
                abs_pro DECIMAL(12,6),
                abs_carbs DECIMAL(12,6),
                abs_fats DECIMAL(12,6),
                PRIMARY KEY (meal_number, error_id)
            );
        ");

        error_log("Step 7 table created for user $user_id for $day");

        // Fetch data from Step 6
        $step_6_data = $wpdb->get_results("SELECT * FROM $step_6_table", ARRAY_A);

        if (empty($step_6_data)) {
            return ['success' => false, 'message' => "No data available in Step 6 table for $day."];
        }

        // Prepare values for batch insert
        $query_values = [];
        foreach ($step_6_data as $row) {
            $meal_number = $row['meal_number'];
            $error_id = $row['error_id'];
            $meal_combo_id = $row['meal_combo_id'];

            // Calculate absolute values
            $abs_pro = abs($row['pro_goal']);
            $abs_carbs = abs($row['carb_goal']);
            $abs_fats = abs($row['fats_goal']);

            // Prepare the values for insertion
            $query_values[] = $wpdb->prepare(
                "(%d, %d, %d, %f, %f, %f)",
                $meal_number, $error_id, $meal_combo_id, $abs_pro, $abs_carbs, $abs_fats
            );
        }

        // Execute the batch insert query
        if (!empty($query_values)) {
            $query = "
                INSERT INTO $step_7_table (meal_number, error_id, meal_combo_id, abs_pro, abs_carbs, abs_fats)
                VALUES " . implode(", ", $query_values) . "
                ON DUPLICATE KEY UPDATE
                    abs_pro = VALUES(abs_pro),
                    abs_carbs = VALUES(abs_carbs),
                    abs_fats = VALUES(abs_fats)
            ";
            $wpdb->query($query);

            error_log("Step 7 data inserted for $day for user $user_id");
        }

        return ['success' => true, 'message' => "Step 7 completed successfully for $day!"];
    }
}

// Initialize the Step 7 class
Step7::init();
