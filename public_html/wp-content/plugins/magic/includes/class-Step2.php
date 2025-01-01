<?php
if (!defined('ABSPATH')) {
    exit;
}

class Step2 {

    public static function init() {
        add_shortcode('calculate_step_2', [__CLASS__, 'calculate_step_2_for_user']);
    }

    public static function calculate_step_2_for_user() {
        global $wpdb;

        // Get the current logged-in user ID
        $user_id = get_current_user_id();
        if (!$user_id) {
            return "You must be logged in to perform this action.";
        }

        // Fetch the associated admin ID
        $admin_id = UserDashboardUtils::get_associated_admin($user_id);
        if (!$admin_id) {
            return "No associated admin found for this user.";
        }

        // Fetch user data
        $user = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}user_info WHERE user_id = %d",
            $user_id
        ));

        if (!$user) {
            return "User data not found.";
        }

        // Decode meal data
        $meal_data = json_decode($user->meal_data, true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($meal_data)) {
            return "Invalid or missing meal data.";
        }

        // Define days of the week
        $days_of_week = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

        // Create the step_2 table for the user if it doesn't exist
        $step_2_table = "{$wpdb->prefix}{$user_id}_step2";
        self::create_step2_table($step_2_table);

        // Process each day
        foreach ($days_of_week as $day) {
            if (!isset($meal_data[$day])) {
                continue; // Skip if no meal data for the day
            }

            $day_meal_data = $meal_data[$day];
            $meals_per_day = $day_meal_data['meals'];

            // Process each meal for the day
            for ($meal_num = 1; $meal_num <= $meals_per_day; $meal_num++) {
                // Fetch meal combo ID
                $meal_combo_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT meal_combo_id FROM {$wpdb->prefix}{$user_id}_meal_combos WHERE day_of_week = %s AND meal_number = %d",
                    $day, $meal_num
                ));

                if (!$meal_combo_id) {
                    error_log("No meal_combo_id found for user {$user_id}, day {$day}, meal {$meal_num}");
                    continue; // Skip if no meal combo ID
                }

                // Fetch combo data
                $combo_data = $wpdb->get_row($wpdb->prepare(
                    "SELECT p1, p2, c1, c2, f1, f2 FROM {$wpdb->prefix}combos WHERE c1_id = %d",
                    $meal_combo_id
                ));

                if (!$combo_data) {
                    error_log("No combo data found for meal_combo_id {$meal_combo_id}");
                    continue; // Skip if no combo data
                }

                // Insert or update adjustments in step_2 table
                $wpdb->query($wpdb->prepare(
                    "INSERT INTO $step_2_table (day, meal_number, error_id, meal_combo_id, pro1_n, pro2_n, carbs1_n, carbs2_n, fats1_n, fats2_n)
                    SELECT %s AS day, %d AS meal_number, error_id, %d AS meal_combo_id,
                        GREATEST(0, pro_negative * %f) AS pro1_n,
                        GREATEST(0, pro_negative * %f) AS pro2_n,
                        GREATEST(0, carbs_negative * %f) AS carbs1_n,
                        GREATEST(0, carbs_negative * %f) AS carbs2_n,
                        GREATEST(0, fats_negative * %f) AS fats1_n,
                        GREATEST(0, fats_negative * %f) AS fats2_n
                    FROM {$wpdb->prefix}{$user_id}_step1
                    WHERE day = %s AND meal_number = %d
                    ON DUPLICATE KEY UPDATE
                        pro1_n = VALUES(pro1_n),
                        pro2_n = VALUES(pro2_n),
                        carbs1_n = VALUES(carbs1_n),
                        carbs2_n = VALUES(carbs2_n),
                        fats1_n = VALUES(fats1_n),
                        fats2_n = VALUES(fats2_n)",
                    $day, $meal_num, $meal_combo_id,
                    $combo_data->p1, $combo_data->p2,
                    $combo_data->c1, $combo_data->c2,
                    $combo_data->f1, $combo_data->f2,
                    $day, $meal_num
                ));
            }
        }

        return "Step 2 calculations have been completed for the week.";
    }

    /**
     * Create the step2 table for the user if it doesn't exist.
     */
    private static function create_step2_table($table_name) {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id INT AUTO_INCREMENT PRIMARY KEY,
            day VARCHAR(10) NOT NULL,
            meal_number INT NOT NULL,
            error_id INT NOT NULL,
            meal_combo_id INT NOT NULL,
            pro1_n DECIMAL(9,3),
            pro2_n DECIMAL(9,3),
            carbs1_n DECIMAL(9,3),
            carbs2_n DECIMAL(9,3),
            fats1_n DECIMAL(9,3),
            fats2_n DECIMAL(9,3),
            UNIQUE KEY unique_meal (day, meal_number, error_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
