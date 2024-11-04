<?php
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




