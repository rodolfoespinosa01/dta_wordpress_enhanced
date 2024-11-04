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