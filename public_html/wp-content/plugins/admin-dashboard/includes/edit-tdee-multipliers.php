<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class EditTDEEMultipliers {

    // Initialize the EditTDEEMultipliers class
    public static function init() {
        add_shortcode('edit_tdee_multipliers', [__CLASS__, 'display_tdee_multipliers_form']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);
        add_action('wp_ajax_save_tdee_multipliers', [__CLASS__, 'save_tdee_multipliers']);
    }

    // Enqueue JavaScript for handling form submissions
    public static function enqueue_scripts() {
        if (is_page('edit-tdee-multipliers')) { // Ensure script loads only on this page
            wp_enqueue_script('edit-tdee-multipliers-script', plugins_url('edit-tdee-multipliers.js', __FILE__), ['jquery'], null, true);
            wp_localize_script('edit-tdee-multipliers-script', 'tdeeMultipliers', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('tdee_multipliers_nonce'),
            ]);
        }
    }

    // Display the TDEE multipliers edit form
    public static function display_tdee_multipliers_form() {
        $user_id = get_current_user_id();
        $tdee_multipliers = self::get_tdee_multipliers($user_id);

        ob_start();
        echo '<h2>Edit TDEE Multipliers</h2>';
        echo '<form id="tdee-multipliers-form">';

        foreach ($tdee_multipliers as $level => $days) {
            echo "<h3>" . esc_html(ucfirst($level) . ' Activity Level') . "</h3>";
            foreach ($days as $day_number => $multipliers) {
                echo "<strong>Day {$day_number}</strong><br>";
                echo '<label>Workout Day: <input type="number" name="workoutDay[' . esc_attr($multipliers['id']) . ']" value="' . esc_attr($multipliers['workoutDay']) . '" step="0.01"></label><br>';
                echo '<label>Off Day: <input type="number" name="offDay[' . esc_attr($multipliers['id']) . ']" value="' . esc_attr($multipliers['offDay']) . '" step="0.01"></label><br><br>';
            }
        }

        echo '<button type="submit" class="button">Save Changes</button>';
        echo '</form>';

        return ob_get_clean();
    }

    // Retrieve TDEE multipliers from the database for the current admin
    private static function get_tdee_multipliers($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tdee_multipliers';

        $results = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table_name WHERE admin_id = %d", $user_id),
            ARRAY_A
        );

        $multipliers = [];
        foreach ($results as $row) {
            $multipliers[$row['level']][$row['day']] = [
                'id'         => $row['id'],
                'workoutDay' => $row['workout_day'],
                'offDay'     => $row['off_day'],
            ];
        }

        return $multipliers;
    }

    // Handle AJAX request to save TDEE multipliers
    public static function save_tdee_multipliers() {
        check_ajax_referer('tdee_multipliers_nonce', 'nonce');

        $user_id = get_current_user_id();
        if (!self::is_admin_user($user_id)) {
            wp_send_json_error(['message' => 'Unauthorized']);
            exit;
        }

        if (empty($_POST['multipliers'])) {
            wp_send_json_error(['message' => 'No multiplier data provided']);
            exit;
        }

        parse_str($_POST['multipliers'], $multipliers);
        global $wpdb;
        $table_name = $wpdb->prefix . 'tdee_multipliers';

        foreach ($multipliers['workoutDay'] as $id => $workoutDay) {
            $result = $wpdb->update(
                $table_name,
                [
                    'workout_day' => $workoutDay,
                    'off_day'     => $multipliers['offDay'][$id],
                ],
                [
                    'id'       => $id,
                    'admin_id' => $user_id,
                ],
                ['%f', '%f'],
                ['%d', '%d']
            );

            if ($result === false) {
                error_log('Failed to update TDEE multiplier ID ' . $id . ': ' . $wpdb->last_error);
                wp_send_json_error(['message' => 'Failed to save settings for one or more records']);
                exit;
            }
        }

        wp_send_json_success(['message' => 'Settings saved successfully']);
        exit;
    }

    // Helper function to check if user is admin
    private static function is_admin_user($user_id) {
        $user = get_userdata($user_id);
        return in_array('admin', (array) $user->roles) || in_array('master_admin', (array) $user->roles);
    }
}

// Initialize the EditTDEEMultipliers class
EditTDEEMultipliers::init();
