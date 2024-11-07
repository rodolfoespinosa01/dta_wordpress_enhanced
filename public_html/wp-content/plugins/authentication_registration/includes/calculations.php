<?php

function calculate_age($birth_date) {
    $birthDate = new DateTime($birth_date);
    $today = new DateTime("today");
    $age = $birthDate->diff($today)->y; // Calculate the age in years
    return $age;
}


function calculate_training_days($meal_data) {
    $training_days = 0;

    // Loop through each day's training time and count the number of training days
    foreach ($meal_data as $day => $data) {
        if (isset($data['training_time']) && $data['training_time'] !== 'none') {
            $training_days++;
        }
    }

    return $training_days;
}

function calculate_bmr($gender, $weight_kg, $height_cm, $age) {
    if ($gender === 'male') {
        // BMR formula for men
        return (10 * $weight_kg) + (6.25 * $height_cm) - (5 * $age) + 5;
    } elseif ($gender === 'female') {
        // BMR formula for women
        return (10 * $weight_kg) + (6.25 * $height_cm) - (5 * $age) - 160;
    } else {
        // Default to 0 if gender is not recognized
        return 0;
    }
}

function fetch_tdee_multipliers($admin_id, $activity_level, $training_days_per_week) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'tdee_multipliers';

    // Query to retrieve the TDEE multipliers for the specified admin, activity level, and training days
    $result = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT workout_day, off_day 
             FROM $table_name 
             WHERE admin_id = %d AND level = %s AND day = %d",
            $admin_id,
            $activity_level,
            $training_days_per_week
        ),
        ARRAY_A
    );

    // Return the values, or defaults if not found
    if ($result) {
        return [
            'workout_day_tdee' => (float) $result['workout_day'],
            'off_day_tdee' => (float) $result['off_day']
        ];
    } else {
        return [
            'workout_day_tdee' => 0,
            'off_day_tdee' => 0
        ];
    }
}

function fetch_admin_macro_settings($admin_id, $goal, $meal_plan_type, $carb_day_type = null) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'macros_settings'; // Replace with your actual table name

    // Base query for macro settings
    $query = "SELECT calorie_percentage, protein_per_lb, carbs_leftover, fats_leftover 
              FROM $table_name 
              WHERE admin_id = %d AND goal = %s AND approach = %s";

    // Add variation filter if the meal plan type is 'carbCycling'
    if ($meal_plan_type === 'carbCycling' && $carb_day_type) {
        $query .= " AND variation = %s";
        $result = $wpdb->get_row($wpdb->prepare($query, $admin_id, $goal, $meal_plan_type, $carb_day_type), ARRAY_A);
    } else {
        $result = $wpdb->get_row($wpdb->prepare($query, $admin_id, $goal, $meal_plan_type), ARRAY_A);
    }

    if (!$result) {
        throw new Exception("Macro settings not found for admin ID $admin_id, goal $goal, and approach $meal_plan_type.");
    }

    return [
        'calorie_percentage' => (float) $result['calorie_percentage'],
        'protein_per_lb' => (float) $result['protein_per_lb'],
        'carbs_leftover' => (float) $result['carbs_leftover'],
        'fats_leftover' => (float) $result['fats_leftover']
    ];
}

function calculate_calories($bmr, $workout_day_tdee, $off_day_tdee, $meal_plan_type, $goal, $admin_id, $carb_day_type = null) {
    // Fetch the admin's macro settings
    $macro_settings = fetch_admin_macro_settings($admin_id, $goal, $meal_plan_type, $carb_day_type);

    // Extract calorie percentage
    if (!isset($macro_settings['calorie_percentage'])) {
        throw new Exception("Calorie percentage not found in macro settings.");
    }
    $calorie_percentage = $macro_settings['calorie_percentage'];

    // Calculate calories
    $calories_workoutDay = ($bmr * $workout_day_tdee) * $calorie_percentage;
    $calories_offDay = ($bmr * $off_day_tdee) * $calorie_percentage;

    // Return the calculated calorie values
    return [
        'calories_workoutDay' => round($calories_workoutDay, 2),
        'calories_offDay' => round($calories_offDay, 2)
    ];
}

// Function to calculate protein intake based on user's weight, meal plan, goal, and carb day type (if applicable)
function calculate_protein($weight_lbs, $meal_plan_type, $goal, $admin_id, $carb_day_type = null) {
    // Fetch the admin's macro settings using the new function
    $macro_settings = fetch_admin_macro_settings($admin_id, $goal, $meal_plan_type, $carb_day_type);

    // Check if the meal plan is carb cycling and handle accordingly
    if ($meal_plan_type === 'carbCycling') {
        if ($carb_day_type === 'lowCarb' || $carb_day_type === 'highCarb') {
            // Use high-carb or low-carb day settings for carb cycling
            $protein_per_lb = isset($macro_settings['protein_per_lb']) ? (float) $macro_settings['protein_per_lb'] : 0;
        } else {
            // If carb day type is not provided or incorrect, throw an error
            throw new Exception("Carb day type must be provided for carb cycling and should be either 'lowCarb' or 'highCarb'.");
        }
    } else {
        // For standard and keto plans, use the regular protein per pound value
        $protein_per_lb = isset($macro_settings['protein_per_lb']) ? (float) $macro_settings['protein_per_lb'] : 0;
    }

    // Calculate the total protein intake based on the user's weight
    $protein_intake = $weight_lbs * $protein_per_lb;

    return round($protein_intake, 2);  // Return the protein intake rounded to 2 decimal places
}

function calculate_carbs_fats($total_calories, $protein_grams, $meal_plan_type, $goal, $admin_id, $carb_day_type = null) {
    // Fetch the admin's macro settings using your existing function
    $macro_settings = fetch_admin_macro_settings($admin_id, $goal, $meal_plan_type, $carb_day_type);

    // Ensure that carbs and fats percentages are available in the fetched settings
    if (isset($macro_settings['carbs_leftover']) && isset($macro_settings['fats_leftover'])) {
        $carbs_percentage = (float) $macro_settings['carbs_leftover'];
        $fats_percentage = (float) $macro_settings['fats_leftover'];
    } else {
        // Return 0 if the settings are not found or incomplete
        return [
            'carbs_grams' => 0,
            'fats_grams' => 0
        ];
    }

    // Calculate protein calories (4 calories per gram of protein)
    $protein_calories = $protein_grams * 4;

    // Calculate the remaining calories for carbs and fats
    $remaining_calories = $total_calories - $protein_calories;

    // Calculate calories for carbs and fats
    $carbs_calories = $remaining_calories * $carbs_percentage;
    $fats_calories = $remaining_calories * $fats_percentage;

    // Convert carbs and fats from calories to grams and round to 6 decimal places
    $carbs_grams = number_format((float)($carbs_calories / 4), 6, '.', '');  // 1g of carbs = 4 calories
    $fats_grams = number_format((float)($fats_calories / 9), 6, '.', '');    // 1g of fats = 9 calories

    return [
        'carbs_grams' => $carbs_grams,
        'fats_grams' => $fats_grams
    ];
}
