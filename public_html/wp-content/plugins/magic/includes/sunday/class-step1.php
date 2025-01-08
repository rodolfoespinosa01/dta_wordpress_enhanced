<?php
if (!defined('ABSPATH')) {
    exit;
}

class Step1 {

    public static function init() {
        // No shortcode since this runs automatically via the automation process
    }

    public static function run($day) {
        if ($day !== 'sunday') {
            return ['success' => false, 'message' => 'Invalid day. This function only supports Sunday.'];
        }

        global $wpdb;

        // Get the current logged-in user ID
        $user_id = get_current_user_id();
        if (!$user_id) {
            return ['success' => false, 'message' => 'You must be logged in to execute this step.'];
        }

        // Fetch associated admin for the user
        $admin_id = UserDashboardUtils::get_associated_admin($user_id);
        if (!$admin_id) {
            return ['success' => false, 'message' => 'No associated admin found for this user.'];
        }

        // Fetch user data
        $user = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}user_info WHERE user_id = %d",
            $user_id
        ));
        if (!$user) {
            return ['success' => false, 'message' => 'No user data found.'];
        }

        // Decode JSON fields
        $meal_data = json_decode($user->meal_data, true);
        $carbCycling_data = json_decode($user->carbCycling_data, true);
        $meal_plan_type = $user->meal_plan_type;

        if (!isset($meal_data[$day])) {
            return ['success' => false, 'message' => "No meal data found for $day."];
        }

        // Day-specific data
        $day_meals = $meal_data[$day]['meals'];
        $training_time = $meal_data[$day]['training_time'];
        $is_workout_day = ($training_time !== 'none');

        // Determine day type
        $day_type_map = [
            'none' => 0,
            'before_meal_1' => 1,
            'before_meal_2' => 2,
            'before_meal_3' => 3,
            'before_meal_4' => 4,
            'before_meal_5' => 5
        ];
        $day_type = $day_type_map[$training_time] ?? 0;

        // Adjust macros for carb cycling if applicable
        if ($meal_plan_type === 'carbCycling' && isset($carbCycling_data[$day])) {
            $carb_type = $carbCycling_data[$day];
            $protein_intake = $carb_type === 'highCarb' ? $user->protein_intake_highCarb : $user->protein_intake_lowCarb;
            $carbs = $carb_type === 'highCarb' ?
                ($is_workout_day ? $user->workout_carbs_highCarb : $user->off_day_carbs_highCarb) :
                ($is_workout_day ? $user->workout_carbs_lowCarb : $user->off_day_carbs_lowCarb);
            $fats = $carb_type === 'highCarb' ?
                ($is_workout_day ? $user->workout_fats_highCarb : $user->off_day_fats_highCarb) :
                ($is_workout_day ? $user->workout_fats_lowCarb : $user->off_day_fats_lowCarb);
        } else {
            $protein_intake = $user->protein_intake;
            $carbs = ($is_workout_day) ? $user->workout_carbs : $user->off_day_carbs;
            $fats = ($is_workout_day) ? $user->workout_fats : $user->off_day_fats;
        }

        // Fetch meal settings
        $meal_settings = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}meal_settings 
                 WHERE admin_id = %d AND meals_per_day = %d AND day_type = %d AND approach = %s",
                $admin_id, $day_meals, $day_type, $meal_plan_type
            ),
            ARRAY_A
        );

        if (!$meal_settings) {
            return ['success' => false, 'message' => "Meal settings not found for admin $admin_id, day $day, meals_per_day $day_meals, day_type $day_type."];
        }

        // Create the Step 1 table dynamically
        $step_1_table = "{$wpdb->prefix}{$user_id}_step1_{$day}";
        $wpdb->query("DROP TABLE IF EXISTS $step_1_table"); // Overwrite existing table
        $wpdb->query("
            CREATE TABLE $step_1_table (
                meal_number INT(10) NOT NULL,
                error_id INT(10) NOT NULL,
                pro_negative DECIMAL(9,3),
                carbs_negative DECIMAL(9,3),
                fats_negative DECIMAL(9,3),
                PRIMARY KEY (meal_number, error_id)
            );
        ");

        // Process each meal for the day
        for ($meal_num = 1; $meal_num <= $day_meals; $meal_num++) {
            // Get meal-specific macros
            $meal_settings_row = $meal_settings[$meal_num - 1] ?? null;
            if (!$meal_settings_row) {
                continue; // Skip if no settings are found for this meal
            }

            $meal_protein_percentage = $meal_settings_row['protein'];
            $meal_carbs_percentage = $meal_settings_row['carbs'];
            $meal_fats_percentage = $meal_settings_row['fats'];

            // Calculate macros for the current meal
            $protein_per_meal = $protein_intake * $meal_protein_percentage;
            $carbs_per_meal = $carbs * $meal_carbs_percentage;
            $fats_per_meal = $fats * $meal_fats_percentage;

            // Insert or update errors in the table
            $wpdb->query($wpdb->prepare(
                "INSERT INTO $step_1_table (meal_number, error_id, pro_negative, carbs_negative, fats_negative)
                SELECT %d AS meal_number, error_id,
                    GREATEST(0, %f - e_protein) AS pro_negative,
                    GREATEST(0, %f - e_carbs) AS carbs_negative,
                    GREATEST(0, %f - e_fats) AS fats_negative
                FROM {$wpdb->prefix}error_453030
                ON DUPLICATE KEY UPDATE
                    pro_negative = VALUES(pro_negative),
                    carbs_negative = VALUES(carbs_negative),
                    fats_negative = VALUES(fats_negative)",
                $meal_num, $protein_per_meal, $carbs_per_meal, $fats_per_meal
            ));
        }

        return ['success' => true, 'message' => "Step 1 calculations completed for $day."];
    }
}
