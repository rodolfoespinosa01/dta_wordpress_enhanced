<?php
if (!defined('ABSPATH')) {
    exit;
}

class MealPlanDisplay {

    // Initialize the shortcode
    public static function init() {
        add_shortcode('user_meal_plan', [__CLASS__, 'render_meal_plan']);
    }

    // Render the meal plan
    public static function render_meal_plan() {
        if (!is_user_logged_in()) {
            return '<p>You must be logged in to view your meal plan.</p>';
        }

        // Fetch current user and admin info
        $user_id = get_current_user_id();
        global $wpdb;

        $user = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}user_info WHERE user_id = %d", 
            $user_id
        ));

        if (!$user) {
            return '<p>Error: Unable to load your user data.</p>';
        }

        $admin_id = UserDashboardUtils::get_associated_admin($user_id);
        $meal_plan_type = $user->meal_plan_type;
        $activity_level = $user->activity_level;
        $training_days = $user->training_days_per_week;
        // Safely decode JSON fields
        $meal_data = !empty($user->meal_data) ? json_decode($user->meal_data, true) : [];
        $carbCycling_data = !empty($user->carbCycling_data) ? json_decode($user->carbCycling_data, true) : [];

        // Fetch TDEE multipliers
        $tdee = TDEEMultipliers::get_tdee_multiplier($admin_id, $activity_level, $training_days);
        if (!$tdee) {
            return '<p>Error: Unable to fetch TDEE multipliers.</p>';
        }

        // Fetch macros settings for workout and off days
        $macros_high = MacrosSettings::get_macros_settings($admin_id, $user->goal, $meal_plan_type, 'highCarb');
        $macros_low = MacrosSettings::get_macros_settings($admin_id, $user->goal, $meal_plan_type, 'lowCarb');

        if (!$macros_high || !$macros_low) {
            return '<p>Error: Unable to fetch macros settings.</p>';
        }

        // Define days of the week
        $days_of_week = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        ob_start();
        ?>
        <h3>Your Weekly Meal Plan</h3>
       <?php
foreach ($days_of_week as $day): ?>
    <h4><?php echo esc_html($day); ?></h4>

    <?php
    $day_lower = strtolower($day);

    // Check if meal data exists for the day
    if (isset($meal_data[$day_lower])) {
        // Get user-specific meal data
        $meals_per_day = $meal_data[$day_lower]['meals']; // Number of meals per day
        $training_time = $meal_data[$day_lower]['training_time']; // Training time for the day

        // Map training_time to day_type
        $day_type_map = [
            'none' => 0,
            'before_meal_1' => 1,
            'before_meal_2' => 2,
            'before_meal_3' => 3,
            'before_meal_4' => 4,
            'before_meal_5' => 5
        ];

        $day_type = $day_type_map[$training_time] ?? 0; // Default to 0 if training_time is invalid

        // Get the user's meal plan type
        $meal_plan_type = $user->meal_plan_type;

        // Debugging: Log the calculated day_type, meals_per_day, and meal_plan_type
        error_log("Day: $day_lower, Day Type: $day_type, Meals Per Day: $meals_per_day, Meal Plan Type: $meal_plan_type");

        // Query to fetch meal settings for the specific day_type, meals_per_day, and meal_plan_type
        $meal_settings = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}meal_settings 
                 WHERE admin_id = %d 
                   AND meals_per_day = %d 
                   AND day_type = %d
                   AND approach = %s",
                $admin_id,
                $meals_per_day,
                $day_type,
                $meal_plan_type
            ),
            ARRAY_A
        );

        // Debugging: Log the query results
        error_log("Meal Settings for $day_lower: " . print_r($meal_settings, true));

        // Adjust macros for carb cycling if applicable
        if ($meal_plan_type === 'carbCycling' && isset($carbCycling_data[$day_lower])) {
            $carb_type = $carbCycling_data[$day_lower]; // 'lowCarb' or 'highCarb'
            $protein_intake = $carb_type === 'highCarb' ? $user->protein_intake_highCarb : $user->protein_intake_lowCarb;
            $carbs_grams = ($training_time !== 'none')
                ? ($carb_type === 'highCarb' ? $user->workout_carbs_highCarb : $user->workout_carbs_lowCarb)
                : ($carb_type === 'highCarb' ? $user->off_day_carbs_highCarb : $user->off_day_carbs_lowCarb);
            $fats_grams = ($training_time !== 'none')
                ? ($carb_type === 'highCarb' ? $user->workout_fats_highCarb : $user->workout_fats_lowCarb)
                : ($carb_type === 'highCarb' ? $user->off_day_fats_highCarb : $user->off_day_fats_lowCarb);
        } else {
            $protein_intake = $user->protein_intake;
            $carbs_grams = ($training_time !== 'none') ? $user->workout_carbs : $user->off_day_carbs;
            $fats_grams = ($training_time !== 'none') ? $user->workout_fats : $user->off_day_fats;
        }

        // Display macros if meal settings exist
        if (!empty($meal_settings)) {
            echo "<table border='1' style='width:100%; border-collapse:collapse;'>";
            echo "<thead>
                    <tr>
                        <th>Meal</th>
                        <th>Training Time</th>
                        <th>Protein (grams)</th>
                        <th>Carbs (grams)</th>
                        <th>Fats (grams)</th>
                    </tr>
                  </thead>
                  <tbody>";

            foreach ($meal_settings as $meal) {
                $meal_number = $meal['meal_number'];
                $meal_protein = $protein_intake * $meal['protein'];
                $meal_carbs = $carbs_grams * $meal['carbs'];
                $meal_fats = $fats_grams * $meal['fats'];

                echo "<tr>
                        <td>Meal $meal_number</td>
                        <td>" . ($training_time !== 'none' ? esc_html($training_time) : 'None') . "</td>
                        <td>" . number_format($meal_protein, 2) . " g</td>
                        <td>" . number_format($meal_carbs, 2) . " g</td>
                        <td>" . number_format($meal_fats, 2) . " g</td>
                      </tr>";
            }

            echo "</tbody></table>";
        } else {
            echo "<p>No meal settings available for this day.</p>";
        }
    } else {
        echo "<p>No meal data available for $day.</p>";
    }
endforeach;
?>

        <?php
        return ob_get_clean();
    }
}

// Initialize the class
MealPlanDisplay::init();
