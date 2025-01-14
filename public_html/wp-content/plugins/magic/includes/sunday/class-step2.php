<?php
if (!defined('ABSPATH')) {
    exit;
}

class Step2 {

    public static function init() {
        // Register the shortcode for Step 2
        add_shortcode('run_step2', [__CLASS__, 'run_step2_shortcode']);
    }

    // Shortcode callback
    public static function run_step2_shortcode() {
        ob_start();

        // Perform Step 2 calculations
        $result = self::run('sunday'); // Hardcoding 'sunday' for now

        // Display the result
        if ($result['success']) {
            echo '<div class="step2-success">';
            echo '<p>' . esc_html($result['message']) . '</p>';
            echo '</div>';
        } else {
            echo '<div class="step2-error">';
            echo '<p>' . esc_html($result['message']) . '</p>';
            echo '</div>';
        }

        return ob_get_clean();
    }

    // Main Step 2 logic
    public static function run($day) {
        global $wpdb;

        // Ensure this runs only for the specified day
        if ($day !== 'sunday') {
            return ['success' => false, 'message' => 'Step 2 is configured to run only for Sunday.'];
        }

        // Get the current logged-in user ID
        $user_id = get_current_user_id();
        if (!$user_id) {
            return ['success' => false, 'message' => 'You must be logged in to run this step.'];
        }

        // Step 1 and Step 2 table names
        $step_1_table = "{$wpdb->prefix}{$user_id}_step1_{$day}";
        $step_2_table = "{$wpdb->prefix}{$user_id}_step2_{$day}";

        // Check if Step 1 table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$step_1_table'") !== $step_1_table) {
            return ['success' => false, 'message' => 'Step 1 table for Sunday does not exist.'];
        }

        // Recreate Step 2 table (drop and create)
        $wpdb->query("DROP TABLE IF EXISTS $step_2_table");
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
        if (json_last_error() !== JSON_ERROR_NONE || !isset($meal_data['sunday'])) {
            return ['success' => false, 'message' => 'Invalid or missing meal data for Sunday.'];
        }

        $day_meals = $meal_data['sunday']['meals'];

        // Process each meal for Sunday
        for ($meal_num = 1; $meal_num <= $day_meals; $meal_num++) {
            // Fetch the meal combo ID from the global wp_meal_combos table
            $meal_combo_id = $wpdb->get_var($wpdb->prepare(
                "SELECT meal_combo_id 
                 FROM {$wpdb->prefix}meal_combos 
                 WHERE user_id = %d AND day_of_week = %s AND meal_number = %d",
                $user_id, $day, $meal_num
            ));

            if (!$meal_combo_id) {
                error_log("No meal_combo_id found for user $user_id, day $day, meal $meal_num");
                continue;
            }

            // Fetch combo data from the global combos table
            $combo_data = $wpdb->get_row($wpdb->prepare(
                "SELECT p1, p2, c1, c2, f1, f2 
                 FROM {$wpdb->prefix}combos 
                 WHERE c1_id = %d",
                $meal_combo_id
            ));

            if (!$combo_data) {
                error_log("No combo data found for meal_combo_id $meal_combo_id");
                continue;
            }

            // Insert Step 2 data by joining with Step 1 data
            $wpdb->query($wpdb->prepare(
                "INSERT INTO $step_2_table (meal_number, error_id, meal_combo_id, pro1_n, pro2_n, carbs1_n, carbs2_n, fats1_n, fats2_n)
                 SELECT meal_number, error_id, %d AS meal_combo_id,
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
                        fats2_n = VALUES(fats2_n)",
                $meal_combo_id,
                $combo_data->p1, $combo_data->p2,
                $combo_data->c1, $combo_data->c2,
                $combo_data->f1, $combo_data->f2,
                $meal_num
            ));
        }

        return ['success' => true, 'message' => 'Step 2 completed successfully for Sunday.'];
    }
}

// Initialize the Step 2 class
Step2::init();
