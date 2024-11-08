<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Function to fetch meal settings from the database
function fetch_meal_settings($admin_id, $meal_plan_type, $meals_per_day, $training_before_meal, $carb_type = null) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'meal_settings';

    // Construct the query
    $carb_type_query = $carb_type ? ' AND carb_type = %s' : '';
    $query = "
        SELECT meal_number, protein, carbs, fats
        FROM $table_name
        WHERE admin_id = %d
        AND approach = %s
        AND meals_per_day = %d
        AND day_type = %d" . $carb_type_query;

    // Prepare the query parameters
    $params = [$admin_id, $meal_plan_type, $meals_per_day, $training_before_meal];
    if ($carb_type) {
        $params[] = $carb_type;
    }

    // Execute the query
    $results = $wpdb->get_results($wpdb->prepare($query, ...$params), ARRAY_A);

    // Structure the results
    $meal_settings = [];
    if ($results) {
        foreach ($results as $row) {
            $meal_number = 'meal' . $row['meal_number'];
            $meal_settings[$meal_number] = [
                'protein' => $row['protein'],
                'carbs'   => $row['carbs'],
                'fats'    => $row['fats'],
            ];
        }
    }

    return $meal_settings;
}

