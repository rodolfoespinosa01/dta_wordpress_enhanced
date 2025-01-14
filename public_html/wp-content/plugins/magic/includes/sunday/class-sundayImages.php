<?php
if (!defined('ABSPATH')) {
    exit;
}

class SundayDetailedMealPlanWithImages {

    public static function init() {
        add_shortcode('sunday_detailed_meal_plan_with_images', [__CLASS__, 'render_sunday_detailed_meal_plan_with_images_shortcode']);
    }

    public static function render_sunday_detailed_meal_plan_with_images_shortcode() {
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

        // Helper function to get image URL by food name
        function get_food_image_url($food_name) {
            $args = [
                'post_type'      => 'attachment',
                'post_status'    => 'inherit',
                'posts_per_page' => 1,
                'meta_query'     => [
                    [
                        'key'   => 'food_name', // Custom field name
                        'value' => $food_name,
                        'compare' => '=',
                    ],
                ],
            ];

            $query = new WP_Query($args);

            if ($query->have_posts()) {
                $attachment_id = $query->posts[0]->ID;
                return wp_get_attachment_url($attachment_id);
            }

            return false; // Return false if no image is found
        }

        // Start output buffering
        ob_start();
        ?>
        <div id="toggle-container" style="text-align: center; margin-bottom: 20px;">
            <label for="unit-toggle">Show amounts in:</label>
            <input type="checkbox" id="unit-toggle" />
            <span id="unit-label">Ounces</span>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const toggle = document.getElementById('unit-toggle');
                const unitLabel = document.getElementById('unit-label');
                const amountCells = document.querySelectorAll('.amount-cell');

                toggle.addEventListener('change', function () {
                    const isGrams = toggle.checked;
                    unitLabel.textContent = isGrams ? 'Grams' : 'Ounces';

                    amountCells.forEach(cell => {
                        const ounces = parseFloat(cell.getAttribute('data-ounces'));
                        const value = isGrams ? (ounces * 28.3495).toFixed(2) : ounces.toFixed(2);
                        cell.textContent = value + (isGrams ? ' g' : ' oz');
                    });
                });
            });
        </script>

        <?php
        // Loop through each meal to create separate tables
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
                <h3>Meal <?php echo esc_html($meal_number); ?></h3>
                <table border="1" style="width:100%; border-collapse:collapse; margin-bottom: 20px;">
                    <thead>
                        <tr>
                            <th>Food Name</th>
                            <th>Food Image</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    // Create rows for each food type (Protein, Carbs, Fats)
                    $food_data = [
                        ['name' => $detailed_meal_data->c1_protein_1, 'amount' => $detailed_meal_data->protein1_total],
                        ['name' => $detailed_meal_data->c1_protein_2, 'amount' => $detailed_meal_data->protein2_total],
                        ['name' => $detailed_meal_data->c1_carbs_1, 'amount' => $detailed_meal_data->carbs1_total],
                        ['name' => $detailed_meal_data->c1_carbs_2, 'amount' => $detailed_meal_data->carbs2_total],
                        ['name' => $detailed_meal_data->c1_fats_1, 'amount' => $detailed_meal_data->fats1_total],
                        ['name' => $detailed_meal_data->c1_fats_2, 'amount' => $detailed_meal_data->fats2_total],
                    ];

                    foreach ($food_data as $food) {
                        if ($food['name'] === '-') {
                            continue; // Skip rows where the food name is "-"
                        }
                        ?>
                        <tr>
                            <td><?php echo esc_html($food['name']); ?></td>
                            <td>
                                <?php
                                $image_url = get_food_image_url($food['name']);
                                if ($image_url) {
                                    echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($food['name']) . '" style="max-width: 100px; height: auto;">';
                                } else {
                                    echo 'No image found';
                                }
                                ?>
                            </td>
                            <td class="amount-cell" data-ounces="<?php echo esc_attr($food['amount']); ?>">
                                <?php echo esc_html(number_format($food['amount'], 2)) . ' oz'; ?>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                    </tbody>
                </table>
                <?php
            }
        }
        return ob_get_clean();
    }
}

SundayDetailedMealPlanWithImages::init();
