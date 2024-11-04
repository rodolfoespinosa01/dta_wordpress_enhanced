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



