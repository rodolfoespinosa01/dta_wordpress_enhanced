<?php
if (!defined('ABSPATH')) {
    exit;
}

class Step6 {

    public static function init() {
        add_shortcode('run_step6', [__CLASS__, 'run_step6_shortcode']);
    }

    public static function run_step6_shortcode() {
        $result = self::run('sunday');
        if ($result['success']) {
            return '<div class="success">' . esc_html($result['message']) . '</div>';
        } else {
            return '<div class="error">' . esc_html($result['message']) . '</div>';
        }
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
            return ['success' => false, 'message' => 'You must be logged in to run Step 6.'];
        }

        // Fetch user data from `wp_user_info`
        $user = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}user_info WHERE user_id = %d",
            $user_id
        ));

        if (!$user) {
            return ['success' => false, 'message' => 'No user data found.'];
        }

        // Decode user meal data
        $meal_data = json_decode($user->meal_data, true);
        $carbCycling_data = json_decode($user->carbCycling_data, true);
        $total_meals = $meal_data[$day]['meals'] ?? 0;
        $training_time = $meal_data[$day]['training_time'] ?? 'none';
        $is_workout_day = ($training_time !== 'none');

        if ($total_meals < 1) {
            return ['success' => false, 'message' => "No meals found for $day."];
        }

        // Determine training_before_meal
        $training_before_meal = $is_workout_day ? intval(str_replace('before_meal_', '', $training_time)) : 0;

        // Fetch admin settings for this user
        $admin_id = UserDashboardUtils::get_associated_admin($user_id);
        if (!$admin_id) {
            return ['success' => false, 'message' => 'No associated admin found for this user.'];
        }

        // Fetch macros for the day
        $protein_intake = $user->protein_intake;
        $carbs_grams = $is_workout_day ? $user->workout_carbs : $user->off_day_carbs;
        $fats_grams = $is_workout_day ? $user->workout_fats : $user->off_day_fats;

        if ($user->meal_plan_type === 'carbCycling' && isset($carbCycling_data[$day])) {
            $carb_type = $carbCycling_data[$day];
            $protein_intake = $carb_type === 'highCarb' ? $user->protein_intake_highCarb : $user->protein_intake_lowCarb;
            $carbs_grams = $carb_type === 'highCarb' ?
                ($is_workout_day ? $user->workout_carbs_highCarb : $user->off_day_carbs_highCarb) :
                ($is_workout_day ? $user->workout_carbs_lowCarb : $user->off_day_carbs_lowCarb);
            $fats_grams = $carb_type === 'highCarb' ?
                ($is_workout_day ? $user->workout_fats_highCarb : $user->off_day_fats_highCarb) :
                ($is_workout_day ? $user->workout_fats_lowCarb : $user->off_day_fats_lowCarb);
        }

        // Step 6 table
        $step_6_table = "{$wpdb->prefix}{$user_id}_step6_{$day}";
        $wpdb->query("DROP TABLE IF EXISTS $step_6_table");
        $wpdb->query("
            CREATE TABLE $step_6_table (
                meal_number INT(10) NOT NULL,
                error_id INT(10) NOT NULL,
                meal_combo_id INT(10) NOT NULL,
                pro_goal DECIMAL(12,6),
                carb_goal DECIMAL(12,6),
                fats_goal DECIMAL(12,6),
                PRIMARY KEY (meal_number, error_id)
            );
        ");

        // Fetch Step 5 data
        $step_5_table = "{$wpdb->prefix}{$user_id}_step5_{$day}";
        $step_5_data = $wpdb->get_results("SELECT * FROM $step_5_table", ARRAY_A);

        if (empty($step_5_data)) {
            return ['success' => false, 'message' => 'No data available for Step 6 insertion.'];
        }

        // Insert data into Step 6
        $query_values = [];
        foreach ($step_5_data as $row) {
            $meal_number = $row['meal_number'];
            $meal_combo_id = $row['meal_combo_id'];
            $error_id = $row['error_id'];

            // Fetch macros for this meal from meal_settings
            $meal_settings = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}meal_settings
                 WHERE admin_id = %d AND meals_per_day = %d AND day_type = %d AND approach = %s AND meal_number = %s",
                $admin_id, $total_meals, $training_before_meal, $user->meal_plan_type, 'meal' . $meal_number
            ));

            if (!$meal_settings) {
                error_log("No macros found for Meal $meal_number in Step 6. Admin ID: $admin_id, Meals Per Day: $total_meals, Day Type: $training_before_meal, Meal Number: meal$meal_number");
                continue;
            }

            // Calculate goals
            $pro_goal = ($protein_intake * $meal_settings->protein) - $row['complete_protein'];
            $carb_goal = ($carbs_grams * $meal_settings->carbs) - $row['complete_carbs'];
            $fats_goal = ($fats_grams * $meal_settings->fats) - $row['complete_fats'];

            // Prepare query values
            $query_values[] = $wpdb->prepare(
                "(%d, %d, %d, %f, %f, %f)",
                $meal_number, $error_id, $meal_combo_id, $pro_goal, $carb_goal, $fats_goal
            );
        }

        // Execute batch insert query
        if (!empty($query_values)) {
            $query = "
                INSERT INTO $step_6_table (meal_number, error_id, meal_combo_id, pro_goal, carb_goal, fats_goal)
                VALUES " . implode(", ", $query_values) . "
                ON DUPLICATE KEY UPDATE
                    pro_goal = VALUES(pro_goal),
                    carb_goal = VALUES(carb_goal),
                    fats_goal = VALUES(fats_goal)
            ";
            $wpdb->query($query);
        }

        return ['success' => true, 'message' => "Step 6 completed successfully for $day!"];
    }
}

Step6::init();
