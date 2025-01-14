<?php
if (!defined('ABSPATH')) {
    exit;
}

class SundayDetailedMealPlan {

    public static function init() {
        add_shortcode('sunday_detailed_meal_plan', [__CLASS__, 'render_sunday_detailed_meal_plan_shortcode']);
    }

    public static function render_sunday_detailed_meal_plan_shortcode() {
        $day = 'sunday'; // Hardcoded to Sunday
        $result = self::render($day);
        return $result;
    }

    public static function render($day) {
        global $wpdb;

        // Validate the day of the week
        if (!in_array($day, ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'])) {
            return "<p>Invalid day of the week.</p>";
        }

        // Get the current logged-in user ID
        $user_id = get_current_user_id();
        if (!$user_id) {
            return "<p>You must be logged in to view this page.</p>";
        }

        // Fetch meal data
        $meal_data = $wpdb->get_var($wpdb->prepare(
            "SELECT meal_data FROM {$wpdb->prefix}user_info WHERE user_id = %d",
            $user_id
        ));

        if (!$meal_data) {
            return "<p>No meal data found for this user.</p>";
        }

        $meal_data = json_decode($meal_data, true);
        if (!isset($meal_data[$day])) {
            return "<p>No meal information available for $day.</p>";
        }

        // Fetch meal combos for the specific user and day from the global `wp_meal_combos` table
        $meal_combos_table = "{$wpdb->prefix}meal_combos";
        $meal_combos = $wpdb->get_results($wpdb->prepare(
            "SELECT meal_number, meal_combo_id 
             FROM $meal_combos_table 
             WHERE user_id = %d AND day_of_week = %s 
             ORDER BY meal_number",
            $user_id, $day
        ));

        if (empty($meal_combos)) {
            return "<p>No meal combos found for $day.</p>";
        }

        // Fetch detailed meal results from the user-specific `step10` table
        $detailed_results_table = "{$wpdb->prefix}{$user_id}_step10_{$day}";

        ob_start(); // Start output buffering
        ?>
        <h2><?php echo ucfirst($day); ?>'s Detailed Meal Plan</h2>
        
        <!-- Toggle Button for Units -->
        <div style="text-align: center; margin: 20px 0;">
            <label for="unit-toggle" style="font-size: 16px; margin-right: 10px;">Show in:</label>
            <input type="checkbox" id="unit-toggle" style="transform: scale(1.5);" />
            <span id="unit-label" style="font-size: 16px; margin-left: 10px;">Ounces</span>
        </div>

        <table border="1" style="width:100%; border-collapse:collapse;" id="detailed-meal-plan-table">
            <thead>
                <tr>
                    <th>Meal Number</th>
                    <th>Training Time</th>
                    <th>Protein 1 (Food)</th>
                    <th>Protein 1 (oz)</th>
                    <th>Protein 2 (Food)</th>
                    <th>Protein 2 (oz)</th>
                    <th>Carbs 1 (Food)</th>
                    <th>Carbs 1 (oz)</th>
                    <th>Carbs 2 (Food)</th>
                    <th>Carbs 2 (oz)</th>
                    <th>Fats 1 (Food)</th>
                    <th>Fats 1 (oz)</th>
                    <th>Fats 2 (Food)</th>
                    <th>Fats 2 (oz)</th>
                </tr>
            </thead>
            <tbody>
            <?php
            foreach ($meal_combos as $meal) {
                $meal_number = $meal->meal_number;
                $meal_combo_id = $meal->meal_combo_id;

                // Fetch detailed meal data for each combo ID
                $detailed_meal_data = $wpdb->get_row($wpdb->prepare(
                    "SELECT 
                        c.c1_protein_1, c.c1_protein_2, c.c1_carbs_1, c.c1_carbs_2, c.c1_fats_1, c.c1_fats_2,
                        d.protein1_total, d.protein2_total, d.carbs1_total, d.carbs2_total, d.fats1_total, d.fats2_total
                     FROM $detailed_results_table d
                     JOIN {$wpdb->prefix}combos c ON d.meal_combo_id = c.c1_id
                     WHERE d.meal_number = %d AND d.meal_combo_id = %d",
                    $meal_number, $meal_combo_id
                ));

                if ($detailed_meal_data) {
                    ?>
                    <tr>
                        <td>Meal <?php echo esc_html($meal_number); ?></td>
                        <td><?php echo esc_html($meal_data[$day]['training_time'] ?? 'None'); ?></td>
                        <!-- Protein -->
                        <td><?php echo esc_html($detailed_meal_data->c1_protein_1); ?></td>
                        <td class="toggle-unit" data-oz="<?php echo esc_attr($detailed_meal_data->protein1_total); ?>">
                            <?php echo number_format($detailed_meal_data->protein1_total, 2); ?> oz
                        </td>
                        <td><?php echo esc_html($detailed_meal_data->c1_protein_2); ?></td>
                        <td class="toggle-unit" data-oz="<?php echo esc_attr($detailed_meal_data->protein2_total); ?>">
                            <?php echo number_format($detailed_meal_data->protein2_total, 2); ?> oz
                        </td>
                        <!-- Carbs -->
                        <td><?php echo esc_html($detailed_meal_data->c1_carbs_1); ?></td>
                        <td class="toggle-unit" data-oz="<?php echo esc_attr($detailed_meal_data->carbs1_total); ?>">
                            <?php echo number_format($detailed_meal_data->carbs1_total, 2); ?> oz
                        </td>
                        <td><?php echo esc_html($detailed_meal_data->c1_carbs_2); ?></td>
                        <td class="toggle-unit" data-oz="<?php echo esc_attr($detailed_meal_data->carbs2_total); ?>">
                            <?php echo number_format($detailed_meal_data->carbs2_total, 2); ?> oz
                        </td>
                        <!-- Fats -->
                        <td><?php echo esc_html($detailed_meal_data->c1_fats_1); ?></td>
                        <td class="toggle-unit" data-oz="<?php echo esc_attr($detailed_meal_data->fats1_total); ?>">
                            <?php echo number_format($detailed_meal_data->fats1_total, 2); ?> oz
                        </td>
                        <td><?php echo esc_html($detailed_meal_data->c1_fats_2); ?></td>
                        <td class="toggle-unit" data-oz="<?php echo esc_attr($detailed_meal_data->fats2_total); ?>">
                            <?php echo number_format($detailed_meal_data->fats2_total, 2); ?> oz
                        </td>
                    </tr>
                    <?php
                } else {
                    ?>
                    <tr>
                        <td colspan="14">No detailed data available for Meal <?php echo esc_html($meal_number); ?></td>
                    </tr>
                    <?php
                }
            }
            ?>
            </tbody>
        </table>

        <!-- JavaScript for Toggle -->
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const toggleSwitch = document.getElementById('unit-toggle');
                const unitLabel = document.getElementById('unit-label');
                const elements = document.querySelectorAll('.toggle-unit');

                toggleSwitch.addEventListener('change', function () {
                    const toGrams = toggleSwitch.checked;
                    unitLabel.textContent = toGrams ? "Grams" : "Ounces";

                    elements.forEach(function (el) {
                        const ozValue = parseFloat(el.getAttribute('data-oz'));
                        if (!isNaN(ozValue)) {
                            if (toGrams) {
                                // Convert to grams
                                el.textContent = (ozValue * 28.3495).toFixed(2) + " g";
                            } else {
                                // Display in ounces
                                el.textContent = ozValue.toFixed(2) + " oz";
                            }
                        }
                    });
                });
            });
        </script>
        <?php
        return ob_get_clean();
    }
}

// Initialize the SundayDetailedMealPlan class
SundayDetailedMealPlan::init();
