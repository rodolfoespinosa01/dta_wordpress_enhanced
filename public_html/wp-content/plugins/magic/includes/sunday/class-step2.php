<?php
if (!defined('ABSPATH')) {
    exit;
}

class Step2 {

    /**
     * Run Step 2 calculations for Sunday.
     *
     * @param string $day The day of the week (only "sunday" is allowed here).
     * @return array An array with success status and message.
     */
    public static function run($day) {
        if ($day !== 'sunday') {
            return ['success' => false, 'message' => 'Invalid day for Step 2.'];
        }

        global $wpdb;

        // Get the current logged-in user ID
        $user_id = get_current_user_id();
        if (!$user_id) {
            return ['success' => false, 'message' => 'User not logged in.'];
        }

        // Fetch associated admin for the user
        $admin_id = UserDashboardUtils::get_associated_admin($user_id);
        if (!$admin_id) {
            return ['success' => false, 'message' => 'No associated admin found for this user.'];
        }

        // Fetch user-specific meal data
        $user_info = $wpdb->get_row($wpdb->prepare(
            "SELECT meal_data FROM {$wpdb->prefix}user_info WHERE user_id = %d",
            $user_id
        ));

        if (!$user_info || empty($user_info->meal_data)) {
            return ['success' => false, 'message' => 'No meal data found for this user.'];
        }

        // Decode the meal data
        $meal_data = json_decode($user_info->meal_data, true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($meal_data['sunday'])) {
            return ['success' => false, 'message' => 'Invalid or missing meal data for Sunday.'];
        }

        // Use Step 1 table for Sunday
        $step_1_table = "{$wpdb->prefix}{$user_id}_step1_sunday";

        // Check if the Step 1 table exists
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $step_1_table
        ));

        if (!$table_exists) {
            return ['success' => false, 'message' => 'Step 1 data not found for Sunday.'];
        }

        // Dynamically create or overwrite Step 2 table for Sunday
        $step_2_table = "{$wpdb->prefix}{$user_id}_step2_sunday";

        // Drop the existing table if it exists
        $wpdb->query("DROP TABLE IF EXISTS $step_2_table");

        // Create a new Step 2 table
        $wpdb->query("
            CREATE TABLE $step_2_table (
                meal_number INT(10) NOT NULL,
                error_id INT(10) NOT NULL,
                meal_combo_id INT(10) NOT NULL,
                pro1_n DECIMAL(9,3),
                pro2_n DECIMAL(9,3),
                carbs1_n DECIMAL(9,3),
                carbs2_n DECIMAL(9,3),
                fats1_n DECIMAL(9,3),
                fats2_n DECIMAL(9,3),
                PRIMARY KEY (meal_number, error_id)
            );
        ");

        // Get meal data for Sunday
        $day_meals = $meal_data['sunday']['meals'];

        // Process each meal for Sunday
        for ($meal_num = 1; $meal_num <= $day_meals; $meal_num++) {
            // Fetch the meal combo ID from the global wp_meal_combos table
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

            // Fetch combo data from the global combos table
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

            // Perform calculations and insert into Step 2 table
            $wpdb->query($wpdb->prepare("
                INSERT INTO $step_2_table (meal_number, error_id, meal_combo_id, pro1_n, pro2_n, carbs1_n, carbs2_n, fats1_n, fats2_n)
                SELECT %d AS meal_number, error_id, %d AS meal_combo_id,
                    GREATEST(0, pro_negative * %f) AS pro1_n,
                    GREATEST(0, pro_negative * %f) AS pro2_n,
                    GREATEST(0, carbs_negative * %f) AS carbs1_n,
                    GREATEST(0, carbs_negative * %f) AS carbs2_n,
                    GREATEST(0, fats_negative * %f) AS fats1_n,
                    GREATEST(0, fats_negative * %f) AS fats2_n
                FROM $step_1_table
                WHERE meal_number = %d
                ON DUPLICATE KEY UPDATE
                    pro1_n = VALUES(pro1_n),
                    pro2_n = VALUES(pro2_n),
                    carbs1_n = VALUES(carbs1_n),
                    carbs2_n = VALUES(carbs2_n),
                    fats1_n = VALUES(fats1_n),
                    fats2_n = VALUES(fats2_n)
            ",
            $meal_num, $meal_combo_id,
            $combo_data->p1, $combo_data->p2,
            $combo_data->c1, $combo_data->c2,
            $combo_data->f1, $combo_data->f2,
            $meal_num));
        }

        return ['success' => true, 'message' => 'Step 2 calculations completed for Sunday.'];
    }
}
