<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class MealPlanMacros {

    // Initialize shortcodes for each day
    public static function init() {
        $days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        foreach ($days as $day) {
            add_shortcode("meal_plan_macros_$day", function() use ($day) {
                return self::display_macros_for_day($day);
            });
        }
    }

    // Display macros for a specific day
    public static function display_macros_for_day($day) {
        if (!is_user_logged_in()) {
            return '<p>Please log in to view your meal plan.</p>';
        }

        // Get current user ID and associated admin ID
        $user_id = get_current_user_id();
        $admin_id = get_user_meta($user_id, 'associated_admin', true); // Fetch admin ID using 'associated_admin' key

        if (empty($admin_id)) {
            return '<p>Admin association not found. Please contact support.</p>';
        }

        // Fetch user profile data
        $user_profile = get_user_meta($user_id, 'user_profile_settings', true);
        if (!$user_profile || empty($user_profile)) {
            return '<p>Profile settings not found. Please update your profile.</p>';
        }

        // Ensure total macros are available in the profile settings
        $total_protein = isset($user_profile['total_protein']) ? floatval($user_profile['total_protein']) : null;
        $total_carbs = isset($user_profile['total_carbs']) ? floatval($user_profile['total_carbs']) : null;
        $total_fats = isset($user_profile['total_fats']) ? floatval($user_profile['total_fats']) : null;

        if (is_null($total_protein) || is_null($total_carbs) || is_null($total_fats)) {
            return '<p>Macro values (protein, carbs, or fats) are missing. Please update your profile settings.</p>';
        }

        // Fetch meal plan type and meal data
        $meal_plan_type = isset($user_profile['meal_plan_type']) ? $user_profile['meal_plan_type'] : 'standard'; // Default to 'standard'
        $meal_data = get_user_meta($user_id, 'meal_data', true);
        if (!$meal_data || !isset($meal_data[$day])) {
            return "<p>No meal data available for " . ucfirst($day) . ".</p>";
        }

        // Extract the number of meals and training time
        $meals_per_day = intval($meal_data[$day]['meals']);
        $training_time = $meal_data[$day]['training_time'];
        $is_workout_day = $training_time !== 'none';
        $training_before_meal = $is_workout_day ? intval(str_replace('before_meal_', '', $training_time)) : 0;

        // Handle carb cycling specifics
        $carb_type = null;
        if ($meal_plan_type === 'carbCycling') {
            $carb_type = isset($user_profile[$day . '_carb_type']) ? $user_profile[$day . '_carb_type'] : 'lowCarb'; // Default to 'lowCarb'
        }

        // Fetch meal settings from the meal_settings table
        $meal_settings = fetch_meal_settings($admin_id, $meal_plan_type, $meals_per_day, $training_before_meal, $carb_type);

        if (empty($meal_settings)) {
            return '<p>No meal settings found. Please check your profile or contact your admin.</p>';
        }

        // Display the meal macros
        ob_start();
        echo "<h3>Macros for " . ucfirst($day) . "</h3>";
        echo "<table border='1' style='width:100%; border-collapse:collapse;'>";
        echo "<thead>
                <tr>
                    <th>Meal</th>
                    <th>Protein (grams)</th>
                    <th>Carbs (grams)</th>
                    <th>Fats (grams)</th>
                </tr>
              </thead>
              <tbody>";

        // Loop through each meal and calculate the macros
        for ($meal_num = 1; $meal_num <= $meals_per_day; $meal_num++) {
            $meal_key = 'meal' . $meal_num;

            // Check if meal settings exist for this meal
            if (!isset($meal_settings[$meal_key])) {
                continue;
            }

            $meal_protein = $total_protein * $meal_settings[$meal_key]['protein'];
            $meal_carbs = $total_carbs * $meal_settings[$meal_key]['carbs'];
            $meal_fats = $total_fats * $meal_settings[$meal_key]['fats'];

            echo "<tr>
                    <td>Meal $meal_num</td>
                    <td>" . number_format($meal_protein, 2) . "</td>
                    <td>" . number_format($meal_carbs, 2) . "</td>
                    <td>" . number_format($meal_fats, 2) . "</td>
                  </tr>";
        }

        echo "</tbody></table>";
        return ob_get_clean();
    }
}

// Initialize the MealPlanMacros class
MealPlanMacros::init();
