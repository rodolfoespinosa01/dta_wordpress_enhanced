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

        // Handle form submission for meal food selections
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['c1_protein_1'])) {
            // Define the single, shared table name
            $table_name = "{$wpdb->prefix}meal_combos";

            // Ensure the shared table exists
            $create_table_query = "
                CREATE TABLE IF NOT EXISTS $table_name (
                    id mediumint(9) NOT NULL AUTO_INCREMENT,
                    user_id mediumint(9) NOT NULL,
                    day_of_week varchar(10) NOT NULL,
                    meal_number int(2) NOT NULL,
                    meal_combo_id int(11) NOT NULL,
                    PRIMARY KEY (id)
                )";

            $wpdb->query($create_table_query);

            if ($wpdb->last_error) {
                return '<p>Error creating the table: ' . $wpdb->last_error . '</p>';
            }

            // Delete existing data for the selected day for this user to overwrite
            $selected_day = sanitize_text_field($_POST['selected_day']);
            $wpdb->delete(
                $table_name,
                array('day_of_week' => $selected_day, 'user_id' => $user_id),
                array('%s', '%d')
            );

            // Insert the meal combo data into the shared table
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
                } else {
                    echo "<p>No matching combo found for Meal {$meal_num} on " . ucfirst($selected_day) . ".</p>";
                }
            }
            echo "<p>Meal combos for " . ucfirst($selected_day) . " saved successfully!</p>";
        }

        // Start the form
        ob_start();
        ?>
        <form id="meal_combo_form" method="post">
            <label for="selected_day">Select Day of the Week:</label>
            <select name="selected_day" id="selected_day" onchange="this.form.submit()">
                <?php 
                $allowed_days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
                $selected_day = isset($_POST['selected_day']) ? sanitize_text_field($_POST['selected_day']) : 'sunday';

                foreach ($allowed_days as $day): ?>
                    <option value="<?php echo $day; ?>" <?php echo ($day === $selected_day) ? 'selected' : ''; ?>>
                        <?php echo ucfirst($day); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <form method="post">
            <input type="hidden" name="selected_day" value="<?php echo $selected_day; ?>">

            <h3><?php echo ucfirst($selected_day); ?></h3>
            <?php 
            $meal_info = isset($meal_data[$selected_day]) ? $meal_data[$selected_day] : null;

            if ($meal_info):
                for ($meal_num = 1; $meal_num <= $meal_info['meals']; $meal_num++): ?>
                    <h4>Customize Meal <?php echo $meal_num; ?></h4>
                    <!-- Protein 1 -->
                    <label for="c1_protein_1_<?php echo $meal_num; ?>">Protein 1:</label>
                    <select name="c1_protein_1[<?php echo $meal_num; ?>]" id="c1_protein_1_<?php echo $meal_num; ?>" required>
                        <option value="">Select Protein 1</option>
                       <option value="-">-</option>
                    <option value="Bison">Bison</option>
                    <option value="Chicken Breast">Chicken Breast</option>
                    <option value="Egg Whites">Egg Whites</option>
                    <option value="Eggs">Eggs</option>
                    <option value="Ground Beef STANDARD">Ground Beef STANDARD</option>
                    <option value="Ground Turkey STANDARD">Ground Turkey STANDARD</option>
                    <option value="Lamb">Lamb</option>
                    <option value="Pork Tenderloin">Pork Tenderloin</option>
                    <option value="Salmon">Salmon</option>
                    <option value="Steak STANDARD">Steak STANDARD</option>
                    <option value="Tilapia">Tilapia</option>
                    <option value="Tuna STANDARD">Tuna STANDARD</option>
                    </select><br>

                    <!-- Protein 2 -->
                    <label for="c1_protein_2_<?php echo $meal_num; ?>">Protein 2:</label>
                    <select name="c1_protein_2[<?php echo $meal_num; ?>]" id="c1_protein_2_<?php echo $meal_num; ?>" required>
                        <option value="">Select Protein 2</option>
                        <option value="-">-</option>
                    <option value="Chicken Breast">Chicken Breast</option>
                    <option value="Ground Turkey STANDARD">Ground Turkey STANDARD</option>
                    <option value="Ground Beef STANDARD">Ground Beef STANDARD</option>
                    <option value="Lamb">Lamb</option>
                    <option value="Steak STANDARD">Steak STANDARD</option>
                    <option value="Pork Tenderloin">Pork Tenderloin</option>
                    <option value="Salmon">Salmon</option>
                    <option value="Tilapia">Tilapia</option>
                    <option value="Tuna STANDARD">Tuna STANDARD</option>
                    <option value="Eggs">Eggs</option>
                    <option value="Egg Whites">Egg Whites</option>
                    </select><br>

                    <!-- Carbs 1 -->
                    <label for="c1_carbs_1_<?php echo $meal_num; ?>">Carbs 1:</label>
                    <select name="c1_carbs_1[<?php echo $meal_num; ?>]" id="c1_carbs_1_<?php echo $meal_num; ?>" required>
                        <option value="">Select Carbs 1</option>
                        <option value="-">-</option>
                    <option value="Quinoa">Quinoa</option>
                    <option value="White Rice">White Rice</option>
                    <option value="Brown Rice">Brown Rice</option>
                    <option value="Sweet Potatoe">Sweet Potatoe</option>
                    <option value="White Potatoe">White Potatoe</option>
                    <option value="Beans STANDARD">Beans STANDARD</option>
                    <option value="Lentils">Lentils</option>
                    <option value="Whole Wheat Pasta">Whole Wheat Pasta</option>
                    <option value="Plain Pasta">Plain Pasta</option>
                    <option value="Banana">Banana</option>
                    </select><br>

                    <!-- Carbs 2 -->
                    <label for="c1_carbs_2_<?php echo $meal_num; ?>">Carbs 2:</label>
                    <select name="c1_carbs_2[<?php echo $meal_num; ?>]" id="c1_carbs_2_<?php echo $meal_num; ?>" required>
                        <option value="">Select Carbs 2</option>
                        <option value="-">-</option>
                    <option value="Beans STANDARD">Beans STANDARD</option>
                    <option value="Lentils">Lentils</option>
                    <option value="Banana">Banana</option>
                    <option value="Sweet Potatoe">Sweet Potatoe</option>
                    <option value="White Potatoe">White Potatoe</option>
                    </select><br>

                    <!-- Fats 1 -->
                    <label for="c1_fats_1_<?php echo $meal_num; ?>">Fats 1:</label>
                    <select name="c1_fats_1[<?php echo $meal_num; ?>]" id="c1_fats_1_<?php echo $meal_num; ?>" required>
                        <option value="">Select Fats 1</option>
                        <option value="-">-</option>
                    <option value="Avocado">Avocado</option>
                    <option value="Nuts STANDARD">Nuts STANDARD</option>
                    </select><br>

                    <!-- Fats 2 -->
                    <label for="c1_fats_2_<?php echo $meal_num; ?>">Fats 2:</label>
                    <select name="c1_fats_2[<?php echo $meal_num; ?>]" id="c1_fats_2_<?php echo $meal_num; ?>" required>
                        <option value="">Select Fats 2</option>
                        <option value="-">-</option>
                    <option value="Oil STANDARD">Oil STANDARD</option>
                    </select><br><br>
                <?php endfor;
            endif; ?>

            <input type="submit" value="Save Custom Meal Plan">
        </form>
        <?php

        return ob_get_clean();  // Return the buffered output
    }
}

// Initialize the class
MealComboForm::init();
