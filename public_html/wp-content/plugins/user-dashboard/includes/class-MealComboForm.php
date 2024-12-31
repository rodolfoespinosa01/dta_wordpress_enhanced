<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class MealComboForm {

    public static function init() {
        // Register shortcode
        add_shortcode('meal_combo_form', [__CLASS__, 'render_meal_combo_form']);
    }

    public static function render_meal_combo_form() {
        if (is_admin()) {
            return; // Prevent execution in the admin area
        }

        global $wpdb;

        // Check if the user is logged in
        if (!is_user_logged_in()) {
            return '<p>You must be logged in to access this page.</p>';
        }

        // Get the current user's ID
        $user_id = get_current_user_id();

        // Retrieve the user's meal data from wp_user_info
        $user_meal_data = $wpdb->get_var($wpdb->prepare(
            "SELECT meal_data FROM {$wpdb->prefix}user_info WHERE user_id = %d",
            $user_id
        ));

        if (!$user_meal_data) {
            return '<p>No meal data found for this user.</p>';
        }

        // Decode the meal data JSON into an array
        $meal_data = json_decode($user_meal_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return '<p>Error decoding meal data: ' . json_last_error_msg() . '</p>';
        }

        // Get the selected day from the dropdown or default to Sunday
        $allowed_days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        $selected_day = isset($_POST['selected_day']) ? sanitize_text_field($_POST['selected_day']) : 'sunday';

        // Check if existing meal combos are saved for the selected day
        $table_name = "{$wpdb->prefix}meal_combos";
        $saved_combos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d AND day_of_week = %s ORDER BY meal_number",
            $user_id,
            $selected_day
        ));

        // Determine if the user wants to edit the meal plan
        $editing = isset($_POST['edit_meal_plan']);

        // Handle form submission for updates or new entries
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['c1_protein_1']) && !$editing) {
            // Delete existing data for the selected day
            $wpdb->delete(
                $table_name,
                array('day_of_week' => $selected_day, 'user_id' => $user_id),
                array('%s', '%d')
            );

            // Insert the new meal combo data
            foreach ($_POST['c1_protein_1'] as $meal_num => $protein_1) {
                $protein_2 = sanitize_text_field($_POST['c1_protein_2'][$meal_num]);
                $carbs_1 = sanitize_text_field($_POST['c1_carbs_1'][$meal_num]);
                $carbs_2 = sanitize_text_field($_POST['c1_carbs_2'][$meal_num]);
                $fats_1 = sanitize_text_field($_POST['c1_fats_1'][$meal_num]);
                $fats_2 = sanitize_text_field($_POST['c1_fats_2'][$meal_num]);

                // Find the matching combo in the wp_combos table
                $meal_combo_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT c1_id FROM {$wpdb->prefix}combos 
                     WHERE c1_protein_1 = %s 
                     AND c1_protein_2 = %s 
                     AND c1_carbs_1 = %s 
                     AND c1_carbs_2 = %s 
                     AND c1_fats_1 = %s 
                     AND c1_fats_2 = %s",
                    $protein_1, $protein_2, $carbs_1, $carbs_2, $fats_1, $fats_2
                ));

                // If a matching combo is found, save it
                if ($meal_combo_id) {
                    $wpdb->insert(
                        $table_name,
                        array(
                            'user_id' => $user_id,
                            'day_of_week' => $selected_day,
                            'meal_number' => intval($meal_num),
                            'meal_combo_id' => intval($meal_combo_id)
                        ),
                        array('%d', '%s', '%d', '%d')
                    );

                    if ($wpdb->last_error) {
                        return '<p>Error saving meal combo: ' . $wpdb->last_error . '</p>';
                    }
                }
            }
            echo "<p>Meal combos for " . ucfirst($selected_day) . " saved successfully!</p>";
            $saved_combos = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_name WHERE user_id = %d AND day_of_week = %s ORDER BY meal_number",
                $user_id,
                $selected_day
            ));
        }

        // Start the form
        ob_start();
        ?>
        <form id="meal_combo_form" method="post">
            <label for="selected_day">Select Day of the Week:</label>
            <select name="selected_day" id="selected_day" onchange="this.form.submit()">
                <?php foreach ($allowed_days as $day): ?>
                    <option value="<?php echo $day; ?>" <?php echo ($day === $selected_day) ? 'selected' : ''; ?>>
                        <?php echo ucfirst($day); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <h3><?php echo ucfirst($selected_day); ?></h3>

        <?php if (!empty($saved_combos) && !$editing): ?>
            <h4>Saved Meals for <?php echo ucfirst($selected_day); ?></h4>
            <table border="1">
                <thead>
                    <tr>
                        <th>Meal</th>
                        <th>Protein 1</th>
                        <th>Protein 2</th>
                        <th>Carbs 1</th>
                        <th>Carbs 2</th>
                        <th>Fats 1</th>
                        <th>Fats 2</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($saved_combos as $combo): ?>
                        <?php
                        $meal_combo = $wpdb->get_row($wpdb->prepare(
                            "SELECT * FROM {$wpdb->prefix}combos WHERE c1_id = %d",
                            $combo->meal_combo_id
                        ));
                        ?>
                        <tr>
                            <td>Meal <?php echo $combo->meal_number; ?></td>
                            <td><?php echo esc_html($meal_combo->c1_protein_1); ?></td>
                            <td><?php echo esc_html($meal_combo->c1_protein_2); ?></td>
                            <td><?php echo esc_html($meal_combo->c1_carbs_1); ?></td>
                            <td><?php echo esc_html($meal_combo->c1_carbs_2); ?></td>
                            <td><?php echo esc_html($meal_combo->c1_fats_1); ?></td>
                            <td><?php echo esc_html($meal_combo->c1_fats_2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <form method="post">
                <button type="submit" name="edit_meal_plan" value="1">Edit Meal Plan</button>
            </form>
        <?php else: ?>
            <h4>Customize Your Meals</h4>
            <form method="post">
    <input type="hidden" name="selected_day" value="<?php echo esc_attr($selected_day); ?>">
    <?php
    // Get meal information for the selected day
    $meal_info = $meal_data[$selected_day] ?? null;

    if ($meal_info):
        for ($meal_num = 1; $meal_num <= $meal_info['meals']; $meal_num++):
            // Retrieve saved combos for the current meal (if available)
            $saved_combo = $saved_combos[$meal_num - 1] ?? null;
            ?>
            <h4>Customize Meal <?php echo $meal_num; ?></h4>

            <!-- Protein 1 -->
            <label for="c1_protein_1_<?php echo $meal_num; ?>">Protein 1:</label>
            <select name="c1_protein_1[<?php echo $meal_num; ?>]" required>
                <option value="">Select Protein 1</option>
    <option value="-" <?php echo $saved_combo && $saved_combo->c1_protein_1 === "-" ? 'selected' : ''; ?>>-</option>
    <option value="Bison" <?php echo $saved_combo && $saved_combo->c1_protein_1 === "Bison" ? 'selected' : ''; ?>>Bison</option>
    <option value="Chicken Breast" <?php echo $saved_combo && $saved_combo->c1_protein_1 === "Chicken Breast" ? 'selected' : ''; ?>>Chicken Breast</option>
    <option value="Egg Whites" <?php echo $saved_combo && $saved_combo->c1_protein_1 === "Egg Whites" ? 'selected' : ''; ?>>Egg Whites</option>
    <option value="Eggs" <?php echo $saved_combo && $saved_combo->c1_protein_1 === "Eggs" ? 'selected' : ''; ?>>Eggs</option>
    <option value="Ground Beef STANDARD" <?php echo $saved_combo && $saved_combo->c1_protein_1 === "Ground Beef STANDARD" ? 'selected' : ''; ?>>Ground Beef STANDARD</option>
    <option value="Ground Turkey STANDARD" <?php echo $saved_combo && $saved_combo->c1_protein_1 === "Ground Turkey STANDARD" ? 'selected' : ''; ?>>Ground Turkey STANDARD</option>
    <option value="Lamb" <?php echo $saved_combo && $saved_combo->c1_protein_1 === "Lamb" ? 'selected' : ''; ?>>Lamb</option>
    <option value="Pork Tenderloin" <?php echo $saved_combo && $saved_combo->c1_protein_1 === "Pork Tenderloin" ? 'selected' : ''; ?>>Pork Tenderloin</option>
    <option value="Salmon" <?php echo $saved_combo && $saved_combo->c1_protein_1 === "Salmon" ? 'selected' : ''; ?>>Salmon</option>
    <option value="Steak STANDARD" <?php echo $saved_combo && $saved_combo->c1_protein_1 === "Steak STANDARD" ? 'selected' : ''; ?>>Steak STANDARD</option>
    <option value="Tilapia" <?php echo $saved_combo && $saved_combo->c1_protein_1 === "Tilapia" ? 'selected' : ''; ?>>Tilapia</option>
    <option value="Tuna STANDARD" <?php echo $saved_combo && $saved_combo->c1_protein_1 === "Tuna STANDARD" ? 'selected' : ''; ?>>Tuna STANDARD</option>
            </select><br>

            <!-- Protein 2 -->
            <label for="c1_protein_2_<?php echo $meal_num; ?>">Protein 2:</label>
            <select name="c1_protein_2[<?php echo $meal_num; ?>]" required>
                <option value="">Select Protein 2</option>
                <option value="-" <?php echo $saved_combo && $saved_combo->c1_protein_2 === "-" ? 'selected' : ''; ?>>-</option>
                <option value="Chicken Breast" <?php echo $saved_combo && $saved_combo->c1_protein_2 === "Chicken Breast" ? 'selected' : ''; ?>>Chicken Breast</option>
                <option value="Ground Turkey STANDARD" <?php echo $saved_combo && $saved_combo->c1_protein_2 === "Ground Turkey STANDARD" ? 'selected' : ''; ?>>Ground Turkey STANDARD</option>
                <option value="Ground Beef STANDARD" <?php echo $saved_combo && $saved_combo->c1_protein_2 === "Ground Beef STANDARD" ? 'selected' : ''; ?>>Ground Beef STANDARD</option>
                <option value="Lamb" <?php echo $saved_combo && $saved_combo->c1_protein_2 === "Lamb" ? 'selected' : ''; ?>>Lamb</option>
                <option value="Steak STANDARD" <?php echo $saved_combo && $saved_combo->c1_protein_2 === "Steak STANDARD" ? 'selected' : ''; ?>>Steak STANDARD</option>
                <option value="Pork Tenderloin" <?php echo $saved_combo && $saved_combo->c1_protein_2 === "Pork Tenderloin" ? 'selected' : ''; ?>>Pork Tenderloin</option>
                <option value="Salmon" <?php echo $saved_combo && $saved_combo->c1_protein_2 === "Salmon" ? 'selected' : ''; ?>>Salmon</option>
                <option value="Tilapia" <?php echo $saved_combo && $saved_combo->c1_protein_2 === "Tilapia" ? 'selected' : ''; ?>>Tilapia</option>
                <option value="Tuna STANDARD" <?php echo $saved_combo && $saved_combo->c1_protein_2 === "Tuna STANDARD" ? 'selected' : ''; ?>>Tuna STANDARD</option>
                <option value="Eggs" <?php echo $saved_combo && $saved_combo->c1_protein_2 === "Eggs" ? 'selected' : ''; ?>>Eggs</option>
                <option value="Egg Whites" <?php echo $saved_combo && $saved_combo->c1_protein_2 === "Egg Whites" ? 'selected' : ''; ?>>Egg Whites</option>
            </select><br>

            <!-- Carbs 1 -->
            <label for="c1_carbs_1_<?php echo $meal_num; ?>">Carbs 1:</label>
            <select name="c1_carbs_1[<?php echo $meal_num; ?>]" required>
                <option value="">Select Carbs 1</option>
                <option value="-" <?php echo $saved_combo && $saved_combo->c1_carbs_1 === "-" ? 'selected' : ''; ?>>-</option>
                <option value="Quinoa" <?php echo $saved_combo && $saved_combo->c1_carbs_1 === "Quinoa" ? 'selected' : ''; ?>>Quinoa</option>
                <option value="White Rice" <?php echo $saved_combo && $saved_combo->c1_carbs_1 === "White Rice" ? 'selected' : ''; ?>>White Rice</option>
                <option value="Brown Rice" <?php echo $saved_combo && $saved_combo->c1_carbs_1 === "Brown Rice" ? 'selected' : ''; ?>>Brown Rice</option>
                <option value="Sweet Potato" <?php echo $saved_combo && $saved_combo->c1_carbs_1 === "Sweet Potato" ? 'selected' : ''; ?>>Sweet Potato</option>
                <option value="White Potato" <?php echo $saved_combo && $saved_combo->c1_carbs_1 === "White Potato" ? 'selected' : ''; ?>>White Potato</option>
                <option value="Beans STANDARD" <?php echo $saved_combo && $saved_combo->c1_carbs_1 === "Beans STANDARD" ? 'selected' : ''; ?>>Beans STANDARD</option>
                <option value="Lentils" <?php echo $saved_combo && $saved_combo->c1_carbs_1 === "Lentils" ? 'selected' : ''; ?>>Lentils</option>
                <option value="Whole Wheat Pasta" <?php echo $saved_combo && $saved_combo->c1_carbs_1 === "Whole Wheat Pasta" ? 'selected' : ''; ?>>Whole Wheat Pasta</option>
                <option value="Plain Pasta" <?php echo $saved_combo && $saved_combo->c1_carbs_1 === "Plain Pasta" ? 'selected' : ''; ?>>Plain Pasta</option>
                <option value="Banana" <?php echo $saved_combo && $saved_combo->c1_carbs_1 === "Banana" ? 'selected' : ''; ?>>Banana</option>
            </select><br>

            <!-- Carbs 2 -->
            <label for="c1_carbs_2_<?php echo $meal_num; ?>">Carbs 2:</label>
            <select name="c1_carbs_2[<?php echo $meal_num; ?>]" required>
                <option value="">Select Carbs 2</option>
                <option value="-" <?php echo $saved_combo && $saved_combo->c1_carbs_2 === "-" ? 'selected' : ''; ?>>-</option>
                <option value="Beans STANDARD" <?php echo $saved_combo && $saved_combo->c1_carbs_2 === "Beans STANDARD" ? 'selected' : ''; ?>>Beans STANDARD</option>
                <option value="Lentils" <?php echo $saved_combo && $saved_combo->c1_carbs_2 === "Lentils" ? 'selected' : ''; ?>>Lentils</option>
                <option value="Banana" <?php echo $saved_combo && $saved_combo->c1_carbs_2 === "Banana" ? 'selected' : ''; ?>>Banana</option>
                <option value="Sweet Potato" <?php echo $saved_combo && $saved_combo->c1_carbs_2 === "Sweet Potato" ? 'selected' : ''; ?>>Sweet Potato</option>
                <option value="White Potato" <?php echo $saved_combo && $saved_combo->c1_carbs_2 === "White Potato" ? 'selected' : ''; ?>>White Potato</option>
            </select><br>

            <!-- Fats 1 -->
            <label for="c1_fats_1_<?php echo $meal_num; ?>">Fats 1:</label>
            <select name="c1_fats_1[<?php echo $meal_num; ?>]" required>
                <option value="">Select Fats 1</option>
                <option value="-" <?php echo $saved_combo && $saved_combo->c1_fats_1 === "-" ? 'selected' : ''; ?>>-</option>
                <option value="Avocado" <?php echo $saved_combo && $saved_combo->c1_fats_1 === "Avocado" ? 'selected' : ''; ?>>Avocado</option>
                <option value="Nuts STANDARD" <?php echo $saved_combo && $saved_combo->c1_fats_1 === "Nuts STANDARD" ? 'selected' : ''; ?>>Nuts STANDARD</option>
            </select><br>

            <!-- Fats 2 -->
            <label for="c1_fats_2_<?php echo $meal_num; ?>">Fats 2:</label>
            <select name="c1_fats_2[<?php echo $meal_num; ?>]" required>
                <option value="">Select Fats 2</option>
                <option value="-" <?php echo $saved_combo && $saved_combo->c1_fats_2 === "-" ? 'selected' : ''; ?>>-</option>
                <option value="Oil STANDARD" <?php echo $saved_combo && $saved_combo->c1_fats_2 === "Oil STANDARD" ? 'selected' : ''; ?>>Oil STANDARD</option>
            </select><br><br>
        <?php endfor; ?>
    <?php endif; ?>

    <button type="submit">Save Meals</button>
</form>

        <?php endif; ?>
        <?php

        return ob_get_clean();
    }
}

// Initialize the class
MealComboForm::init();
