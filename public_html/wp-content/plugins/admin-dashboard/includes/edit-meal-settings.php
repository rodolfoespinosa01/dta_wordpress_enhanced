<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class EditMealSettings {

    // Initialize the EditMealSettings class
    public static function init() {
        add_shortcode('edit_meal_settings', [__CLASS__, 'display_meal_settings_form']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);
        add_action('wp_ajax_save_meal_settings', [__CLASS__, 'save_meal_settings']);
    }

    // Enqueue JavaScript for handling form submissions
    public static function enqueue_scripts() {
        if (is_page('edit-meal-settings')) { // Adjust to the actual page slug
            wp_enqueue_script('edit-meal-settings-script', plugins_url('edit-meal-settings.js', __FILE__), ['jquery'], null, true);
            wp_localize_script('edit-meal-settings-script', 'mealSettings', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('meal_settings_nonce'),
            ]);
        }
    }

    // Display the meal settings edit form
    public static function display_meal_settings_form() {
        $user_id = get_current_user_id();
        $meal_settings = self::get_meal_settings($user_id);

        ob_start();
        echo '<h2>Edit Meal Settings</h2>';
        echo '<form id="meal-settings-form">';

        foreach ($meal_settings as $setting) {
            $title = ucwords($setting['approach']) . " - {$setting['meals_per_day']} Meals - Day Type: {$setting['day_type']}";
            if (!empty($setting['carb_type'])) {
                $title .= " ({$setting['carb_type']})";
            }

            echo '<h3>' . esc_html($title) . '</h3>';
            foreach (['protein', 'carbs', 'fats'] as $nutrient) {
                echo "<label>" . ucfirst($nutrient) . " for Meal " . esc_attr($setting['meal_number']) . ":";
                echo '<input type="number" name="' . esc_attr("{$nutrient}[{$setting['id']}]") . '" value="' . esc_attr($setting[$nutrient]) . '" step="any"></label><br>';
            }
            echo '<br>';
        }

        echo '<button type="submit" class="button">Save Changes</button>';
        echo '</form>';

        return ob_get_clean();
    }

    // Retrieve meal settings from the database for the current admin
    private static function get_meal_settings($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'meal_settings';

        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table_name WHERE admin_id = %d", $user_id),
            ARRAY_A
        );
    }

    // Handle AJAX request to save meal settings
    public static function save_meal_settings() {
        check_ajax_referer('meal_settings_nonce', 'nonce');

        $user_id = get_current_user_id();
        if (!self::is_admin_user($user_id)) {
            wp_send_json_error(['message' => 'Unauthorized']);
            exit;
        }

        if (empty($_POST['settings'])) {
            wp_send_json_error(['message' => 'No settings data provided']);
            exit;
        }

        parse_str($_POST['settings'], $settings);
        global $wpdb;
        $table_name = $wpdb->prefix . 'meal_settings';

        foreach ($settings['protein'] as $id => $protein) {
            $result = $wpdb->update(
                $table_name,
                [
                    'protein' => $protein,
                    'carbs'   => $settings['carbs'][$id],
                    'fats'    => $settings['fats'][$id],
                ],
                [
                    'id' => $id,
                    'admin_id' => $user_id,
                ],
                ['%f', '%f', '%f'],
                ['%d', '%d']
            );

            if ($result === false) {
                error_log('Failed to update meal setting ID ' . $id . ': ' . $wpdb->last_error);
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

// Initialize the EditMealSettings class
EditMealSettings::init();
