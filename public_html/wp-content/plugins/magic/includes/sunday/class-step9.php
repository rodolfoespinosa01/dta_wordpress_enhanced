<?php
if (!defined('ABSPATH')) {
    exit;
}

class Step9 {

    public static function init() {
        add_shortcode('run_step9', [__CLASS__, 'run_step9_shortcode']);
    }

    public static function run_step9_shortcode() {
        $result = self::run('sunday'); // Hardcoded for now to 'sunday'
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
            return ['success' => false, 'message' => 'You must be logged in to run Step 9.'];
        }

        error_log("Processing Step 9 for $day for user $user_id");

        // Define table names
        $step_8_table = "{$wpdb->prefix}{$user_id}_step8_{$day}";
        $step_9_table = "{$wpdb->prefix}{$user_id}_step9_{$day}";

        // Check if Step 8 table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$step_8_table'") !== $step_8_table) {
            return ['success' => false, 'message' => "Step 8 table does not exist for $day."];
        }

        // Recreate the Step 9 table for storing smallest errors
        $wpdb->query("DROP TABLE IF EXISTS $step_9_table");
        $wpdb->query("
            CREATE TABLE $step_9_table (
                meal_number INT(10) NOT NULL,
                error_id INT(10) NOT NULL,
                error_est DECIMAL(12,6),
                PRIMARY KEY (meal_number)
            );
        ");

        error_log("Step 9 table created for user $user_id for $day");

        // Query to find and insert the smallest error for each meal
        $query = "
            INSERT INTO $step_9_table (meal_number, error_id, error_est)
            SELECT meal_number, error_id, error_est
            FROM (
                SELECT meal_number, error_id, error_est,
                ROW_NUMBER() OVER (PARTITION BY meal_number ORDER BY error_est ASC) AS rn
                FROM $step_8_table
            ) AS ranked
            WHERE ranked.rn = 1
            ON DUPLICATE KEY UPDATE
                error_id = VALUES(error_id),
                error_est = VALUES(error_est);
        ";

        // Execute the query
        $wpdb->query($query);

        error_log("Step 9 processing completed for user $user_id for $day");

        return ['success' => true, 'message' => "Step 9 completed successfully for $day!"];
    }
}

// Initialize the Step 9 class
Step9::init();
