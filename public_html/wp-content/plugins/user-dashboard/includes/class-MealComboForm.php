<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class MealComboForm {

    public static function init() {
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

        // Retrieve the user's meal data
        $user_meal_data = $wpdb->get_var($wpdb->prepare(
            "SELECT meal_data FROM {$wpdb->prefix}user_info WHERE user_id = %d",
            $user_id
        ));

        if (!$user_meal_data) {
            return '<p>No meal data found for this user.</p>';
        }

        $meal_data = json_decode($user_meal_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return '<p>Error decoding meal data: ' . json_last_error_msg() . '</p>';
        }

        // Get the selected day or default to Sunday
        $allowed_days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        $selected_day = isset($_POST['selected_day']) ? sanitize_text_field($_POST['selected_day']) : 'sunday';

        // Retrieve saved combos for the selected day
        $table_name = "{$wpdb->prefix}meal_combos";
        $saved_combos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d AND day_of_week = %s ORDER BY meal_number",
            $user_id,
            $selected_day
        ));

        // Check if a specific meal is being edited
        $edit_meal_number = isset($_POST['edit_meal_number']) ? intval($_POST['edit_meal_number']) : null;

        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['c1_protein_1'])) {
            if ($edit_meal_number !== null) {
                // Update a single meal
                $protein_1 = sanitize_text_field($_POST['c1_protein_1']);
                $protein_2 = sanitize_text_field($_POST['c1_protein_2']);
                $carbs_1 = sanitize_text_field($_POST['c1_carbs_1']);
                $carbs_2 = sanitize_text_field($_POST['c1_carbs_2']);
                $fats_1 = sanitize_text_field($_POST['c1_fats_1']);
                $fats_2 = sanitize_text_field($_POST['c1_fats_2']);

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

                if ($meal_combo_id) {
                    $wpdb->update(
                        $table_name,
                        ['meal_combo_id' => $meal_combo_id],
                        ['user_id' => $user_id, 'day_of_week' => $selected_day, 'meal_number' => $edit_meal_number],
                        ['%d'],
                        ['%d', '%s', '%d']
                    );

                    // Confirm update success
                    if ($wpdb->last_error) {
                        echo "<p>Error updating Meal $edit_meal_number: " . $wpdb->last_error . "</p>";
                    } else {
                        echo "<p>Meal $edit_meal_number updated successfully!</p>";
                    }
                } else {
                    echo "<p>No matching meal combo found for the given inputs.</p>";
                }

                // Refresh saved combos after update
                $saved_combos = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM $table_name WHERE user_id = %d AND day_of_week = %s ORDER BY meal_number",
                    $user_id,
                    $selected_day
                ));

                // Exit editing mode after save
                $edit_meal_number = null;
            }
        }

        // Render form
        ob_start();
        ?>
        <form method="post">
            <label for="selected_day">Select Day of the Week:</label>
            <select name="selected_day" onchange="this.form.submit()">
                <?php foreach ($allowed_days as $day): ?>
                    <option value="<?php echo $day; ?>" <?php echo $selected_day === $day ? 'selected' : ''; ?>>
                        <?php echo ucfirst($day); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <h3><?php echo ucfirst($selected_day); ?> Meal Plan</h3>

        <?php if ($saved_combos && $edit_meal_number === null): ?>
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
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($saved_combos as $combo): ?>
                        <?php
                        $meal_combo_details = $wpdb->get_row($wpdb->prepare(
                            "SELECT * FROM {$wpdb->prefix}combos WHERE c1_id = %d",
                            $combo->meal_combo_id
                        ));
                        ?>
                        <tr>
                            <td>Meal <?php echo $combo->meal_number; ?></td>
                            <td><?php echo esc_html($meal_combo_details->c1_protein_1); ?></td>
                            <td><?php echo esc_html($meal_combo_details->c1_protein_2); ?></td>
                            <td><?php echo esc_html($meal_combo_details->c1_carbs_1); ?></td>
                            <td><?php echo esc_html($meal_combo_details->c1_carbs_2); ?></td>
                            <td><?php echo esc_html($meal_combo_details->c1_fats_1); ?></td>
                            <td><?php echo esc_html($meal_combo_details->c1_fats_2); ?></td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="selected_day" value="<?php echo esc_attr($selected_day); ?>">
                                    <button type="submit" name="edit_meal_number" value="<?php echo $combo->meal_number; ?>">Edit</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($edit_meal_number !== null): ?>
            <?php
            $meal_combo_details = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE user_id = %d AND day_of_week = %s AND meal_number = %d",
                $user_id, $selected_day, $edit_meal_number
            ));
            ?>
            <h4>Edit Meal <?php echo $edit_meal_number; ?></h4>
            <form method="post">
                <input type="hidden" name="selected_day" value="<?php echo esc_attr($selected_day); ?>">
                <input type="hidden" name="edit_meal_number" value="<?php echo $edit_meal_number; ?>">

                <!-- Protein 1 -->
    <label for="c1_protein_1">Protein 1:</label>
    <select name="c1_protein_1" required>
        <option value="">Select Protein 1</option>
        <option value="-" <?php echo $meal_combo_details && $meal_combo_details->c1_protein_1 === "-" ? 'selected' : ''; ?>>-</option>
        <option value="Bison" <?php echo $meal_combo_details && $meal_combo_details->c1_protein_1 === "Bison" ? 'selected' : ''; ?>>Bison</option>
        <option value="Chicken Breast" <?php echo $meal_combo_details && $meal_combo_details->c1_protein_1 === "Chicken Breast" ? 'selected' : ''; ?>>Chicken Breast</option>
        <option value="Egg Whites" <?php echo $meal_combo_details && $meal_combo_details->c1_protein_1 === "Egg Whites" ? 'selected' : ''; ?>>Egg Whites</option>
        <option value="Eggs" <?php echo $meal_combo_details && $meal_combo_details->c1_protein_1 === "Eggs" ? 'selected' : ''; ?>>Eggs</option>
        <option value="Ground Beef STANDARD" <?php echo $meal_combo_details && $meal_combo_details->c1_protein_1 === "Ground Beef STANDARD" ? 'selected' : ''; ?>>Ground Beef STANDARD</option>
        <option value="Ground Turkey STANDARD" <?php echo $meal_combo_details && $meal_combo_details->c1_protein_1 === "Ground Turkey STANDARD" ? 'selected' : ''; ?>>Ground Turkey STANDARD</option>
        <option value="Lamb" <?php echo $meal_combo_details && $meal_combo_details->c1_protein_1 === "Lamb" ? 'selected' : ''; ?>>Lamb</option>
        <option value="Pork Tenderloin" <?php echo $meal_combo_details && $meal_combo_details->c1_protein_1 === "Pork Tenderloin" ? 'selected' : ''; ?>>Pork Tenderloin</option>
        <option value="Salmon" <?php echo $meal_combo_details && $meal_combo_details->c1_protein_1 === "Salmon" ? 'selected' : ''; ?>>Salmon</option>
        <option value="Steak STANDARD" <?php echo $meal_combo_details && $meal_combo_details->c1_protein_1 === "Steak STANDARD" ? 'selected' : ''; ?>>Steak STANDARD</option>
        <option value="Tilapia" <?php echo $meal_combo_details && $meal_combo_details->c1_protein_1 === "Tilapia" ? 'selected' : ''; ?>>Tilapia</option>
        <option value="Tuna STANDARD" <?php echo $meal_combo_details && $meal_combo_details->c1_protein_1 === "Tuna STANDARD" ? 'selected' : ''; ?>>Tuna STANDARD</option>
    </select>
    <br>

    <!-- Protein 2 -->
    <label for="c1_protein_2">Protein 2:</label>
    <select name="c1_protein_2" required>
        <option value="">Select Protein 2</option>
        <option value="-" <?php echo $meal_combo_details && $meal_combo_details->c1_protein_2 === "-" ? 'selected' : ''; ?>>-</option>
        <option value="Chicken Breast" <?php echo $meal_combo_details && $meal_combo_details->c1_protein_2 === "Chicken Breast" ? 'selected' : ''; ?>>Chicken Breast</option>
        <option value="Ground Turkey STANDARD" <?php echo $meal_combo_details && $meal_combo_details->c1_protein_2 === "Ground Turkey STANDARD" ? 'selected' : ''; ?>>Ground Turkey STANDARD</option>
        <option value="Ground Beef STANDARD" <?php echo $meal_combo_details && $meal_combo_details->c1_protein_2 === "Ground Beef STANDARD" ? 'selected' : ''; ?>>Ground Beef STANDARD</option>
        <option value="Lamb" <?php echo $meal_combo_details && $meal_combo_details->c1_protein_2 === "Lamb" ? 'selected' : ''; ?>>Lamb</option>
        <option value="Steak STANDARD" <?php echo $meal_combo_details && $meal_combo_details->c1_protein_2 === "Steak STANDARD" ? 'selected' : ''; ?>>Steak STANDARD</option>
        <option value="Pork Tenderloin" <?php echo $meal_combo_details && $meal_combo_details->c1_protein_2 === "Pork Tenderloin" ? 'selected' : ''; ?>>Pork Tenderloin</option>
        <option value="Salmon" <?php echo $meal_combo_details && $meal_combo_details->c1_protein_2 === "Salmon" ? 'selected' : ''; ?>>Salmon</option>
        <option value="Tilapia" <?php echo $meal_combo_details && $meal_combo_details->c1_protein_2 === "Tilapia" ? 'selected' : ''; ?>>Tilapia</option>
        <option value="Tuna STANDARD" <?php echo $meal_combo_details && $meal_combo_details->c1_protein_2 === "Tuna STANDARD" ? 'selected' : ''; ?>>Tuna STANDARD</option>
        <option value="Eggs" <?php echo $meal_combo_details && $meal_combo_details->c1_protein_2 === "Eggs" ? 'selected' : ''; ?>>Eggs</option>
        <option value="Egg Whites" <?php echo $meal_combo_details && $meal_combo_details->c1_protein_2 === "Egg Whites" ? 'selected' : ''; ?>>Egg Whites</option>
    </select>
    <br>

    <!-- Carbs 1 -->
    <label for="c1_carbs_1">Carbs 1:</label>
    <select name="c1_carbs_1" required>
        <option value="">Select Carbs 1</option>
        <option value="-" <?php echo $meal_combo_details && $meal_combo_details->c1_carbs_1 === "-" ? 'selected' : ''; ?>>-</option>
        <option value="Quinoa" <?php echo $meal_combo_details && $meal_combo_details->c1_carbs_1 === "Quinoa" ? 'selected' : ''; ?>>Quinoa</option>
        <option value="White Rice" <?php echo $meal_combo_details && $meal_combo_details->c1_carbs_1 === "White Rice" ? 'selected' : ''; ?>>White Rice</option>
        <option value="Brown Rice" <?php echo $meal_combo_details && $meal_combo_details->c1_carbs_1 === "Brown Rice" ? 'selected' : ''; ?>>Brown Rice</option>
        <option value="Sweet Potato" <?php echo $meal_combo_details && $meal_combo_details->c1_carbs_1 === "Sweet Potato" ? 'selected' : ''; ?>>Sweet Potato</option>
        <option value="White Potato" <?php echo $meal_combo_details && $meal_combo_details->c1_carbs_1 === "White Potato" ? 'selected' : ''; ?>>White Potato</option>
        <option value="Beans STANDARD" <?php echo $meal_combo_details && $meal_combo_details->c1_carbs_1 === "Beans STANDARD" ? 'selected' : ''; ?>>Beans STANDARD</option>
        <option value="Lentils" <?php echo $meal_combo_details && $meal_combo_details->c1_carbs_1 === "Lentils" ? 'selected' : ''; ?>>Lentils</option>
        <option value="Whole Wheat Pasta" <?php echo $meal_combo_details && $meal_combo_details->c1_carbs_1 === "Whole Wheat Pasta" ? 'selected' : ''; ?>>Whole Wheat Pasta</option>
        <option value="Plain Pasta" <?php echo $meal_combo_details && $meal_combo_details->c1_carbs_1 === "Plain Pasta" ? 'selected' : ''; ?>>Plain Pasta</option>
        <option value="Banana" <?php echo $meal_combo_details && $meal_combo_details->c1_carbs_1 === "Banana" ? 'selected' : ''; ?>>Banana</option>
    </select>
    <br>

    <!-- Carbs 2 -->
    <label for="c1_carbs_2">Carbs 2:</label>
    <select name="c1_carbs_2" required>
        <option value="">Select Carbs 2</option>
        <option value="-" <?php echo $meal_combo_details && $meal_combo_details->c1_carbs_2 === "-" ? 'selected' : ''; ?>>-</option>
        <option value="Beans STANDARD" <?php echo $meal_combo_details && $meal_combo_details->c1_carbs_2 === "Beans STANDARD" ? 'selected' : ''; ?>>Beans STANDARD</option>
        <option value="Lentils" <?php echo $meal_combo_details && $meal_combo_details->c1_carbs_2 === "Lentils" ? 'selected' : ''; ?>>Lentils</option>
        <option value="Banana" <?php echo $meal_combo_details && $meal_combo_details->c1_carbs_2 === "Banana" ? 'selected' : ''; ?>>Banana</option>
        <option value="Sweet Potato" <?php echo $meal_combo_details && $meal_combo_details->c1_carbs_2 === "Sweet Potato" ? 'selected' : ''; ?>>Sweet Potato</option>
        <option value="White Potato" <?php echo $meal_combo_details && $meal_combo_details->c1_carbs_2 === "White Potato" ? 'selected' : ''; ?>>White Potato</option>
    </select>
    <br>

    <!-- Fats 1 -->
    <label for="c1_fats_1">Fats 1:</label>
    <select name="c1_fats_1" required>
        <option value="">Select Fats 1</option>
        <option value="-" <?php echo $meal_combo_details && $meal_combo_details->c1_fats_1 === "-" ? 'selected' : ''; ?>>-</option>
        <option value="Avocado" <?php echo $meal_combo_details && $meal_combo_details->c1_fats_1 === "Avocado" ? 'selected' : ''; ?>>Avocado</option>
        <option value="Nuts STANDARD" <?php echo $meal_combo_details && $meal_combo_details->c1_fats_1 === "Nuts STANDARD" ? 'selected' : ''; ?>>Nuts STANDARD</option>
    </select>
    <br>

    <!-- Fats 2 -->
    <label for="c1_fats_2">Fats 2:</label>
    <select name="c1_fats_2" required>
        <option value="">Select Fats 2</option>
        <option value="-" <?php echo $meal_combo_details && $meal_combo_details->c1_fats_2 === "-" ? 'selected' : ''; ?>>-</option>
        <option value="Oil STANDARD" <?php echo $meal_combo_details && $meal_combo_details->c1_fats_2 === "Oil STANDARD" ? 'selected' : ''; ?>>Oil STANDARD</option>
    </select>
    <br><br>

    <button type="submit">Save Meal</button>
</form>

        <?php endif; ?>

        <?php
        return ob_get_clean();
    }
}

// Initialize the class
MealComboForm::init();
