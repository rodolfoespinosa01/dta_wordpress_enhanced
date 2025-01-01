<?php
function display_user_meal_combos() {
    global $wpdb;

    $user_id = get_current_user_id();
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT day_of_week, meal_number, c1_protein_1, c1_protein_2, c1_carbs_1, c1_carbs_2, c1_fats_1, c1_fats_2
         FROM {$wpdb->prefix}user_meal_combos umc
         JOIN {$wpdb->prefix}combos c ON umc.meal_combo_id = c.c1_id
         WHERE umc.user_id = %d",
        $user_id
    ));

    if ($results) {
        echo "<table><thead><tr><th>Day</th><th>Meal</th><th>Proteins</th><th>Carbs</th><th>Fats</th></tr></thead><tbody>";
        foreach ($results as $row) {
            echo "<tr>
                    <td>{$row->day_of_week}</td>
                    <td>Meal {$row->meal_number}</td>
                    <td>{$row->c1_protein_1}, {$row->c1_protein_2}</td>
                    <td>{$row->c1_carbs_1}, {$row->c1_carbs_2}</td>
                    <td>{$row->c1_fats_1}, {$row->c1_fats_2}</td>
                  </tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<p>No meal combos found.</p>";
    }
}

// Register a shortcode
add_shortcode('display_meal_combos', 'display_user_meal_combos');
