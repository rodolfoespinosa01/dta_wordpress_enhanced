<?php
function fetch_meal_settings($admin_id, $meal_plan_type, $meals_per_day, $training_before_meal, $carb_type = null) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'meal_settings';

    // Prepare query parameters with conditions based on carb cycling or non-carb cycling plans
    $carb_type_query = $carb_type ? ' AND carb_type = %s' : '';
    $query = "
        SELECT meal_number, protein, carbs, fats
        FROM $table_name
        WHERE admin_id = %d
        AND approach = %s
        AND meals_per_day = %d
        AND day_type = %d" . $carb_type_query;

    // Prepare parameters for query
    $params = [$admin_id, $meal_plan_type, $meals_per_day, $training_before_meal];
    if ($carb_type) {
        $params[] = $carb_type;
    }

    // Execute query and get results
    $results = $wpdb->get_results($wpdb->prepare($query, ...$params), ARRAY_A);

    // Structure the results into an associative array with each meal's settings
    $meal_settings = [];
    if ($results) {
        foreach ($results as $row) {
            $meal_number = 'meal' . $row['meal_number'];
            $meal_settings[$meal_number] = [
                'protein' => $row['protein'],
                'carbs' => $row['carbs'],
                'fats' => $row['fats'],
            ];
        }
    }

    return $meal_settings;
}

