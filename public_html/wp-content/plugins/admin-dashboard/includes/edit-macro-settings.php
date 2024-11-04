<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class EditMacroSettings {

    // Initialize the EditMacroSettings class
    public static function init() {
        add_shortcode('edit_macro_settings', [__CLASS__, 'display_macro_settings_form']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);
        add_action('wp_ajax_save_macro_settings', [__CLASS__, 'save_macro_settings']);
    }

    // Enqueue JavaScript for handling form submissions
    public static function enqueue_scripts() {
        if (is_page('edit-macro-settings')) {
            wp_enqueue_script('edit-macro-settings-script', plugins_url('edit-macro-settings.js', __FILE__), ['jquery'], null, true);
            wp_localize_script('edit-macro-settings-script', 'macroSettings', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('macro_settings_nonce'),
            ]);
        }
    }

    // Display the macro settings edit form with carb cycling indicators
    public static function display_macro_settings_form() {
        $user_id = get_current_user_id();
        $macro_settings = self::get_macro_settings($user_id);

        ob_start();
        echo '<h2>Edit Macro Settings</h2>';
        echo '<form id="macro-settings-form">';

        foreach ($macro_settings as $setting) {
            // Determine if this is a low or high-carb day based on the 'variation' field
            $carb_type = '';
            if ($setting['approach'] === 'carbCycling') {
                if ($setting['variation'] === 'highCarb') {
                    $carb_type = 'High-Carb Day';
                } elseif ($setting['variation'] === 'lowCarb') {
                    $carb_type = 'Low-Carb Day';
                }
            }

            // Add carb type to the title if applicable
            $title = ucwords($setting['approach']) . ' - ' . ucwords($setting['goal']);
            if ($carb_type) {
                $title .= " ({$carb_type})";
            }

            // Display section with the title and inputs
            echo '<h3>' . esc_html($title) . '</h3>';
            echo '<label>Calorie Percentage: <input type="number" name="calorie_percentage[' . esc_attr($setting['id']) . ']" value="' . esc_attr($setting['calorie_percentage']) . '" step="0.01"></label><br>';
            echo '<label>Protein per lb: <input type="number" name="protein_per_lb[' . esc_attr($setting['id']) . ']" value="' . esc_attr($setting['protein_per_lb']) . '" step="0.01"></label><br>';
            echo '<label>Carbs Leftover: <input type="number" name="carbs_leftover[' . esc_attr($setting['id']) . ']" value="' . esc_attr($setting['carbs_leftover']) . '" step="0.01"></label><br>';
            echo '<label>Fats Leftover: <input type="number" name="fats_leftover[' . esc_attr($setting['id']) . ']" value="' . esc_attr($setting['fats_leftover']) . '" step="0.01"></label><br><br>';
        }

        echo '<button type="submit" class="button">Save Changes</button>';
        echo '</form>';

        return ob_get_clean();
    }

    // Retrieve macro settings from the database
    private static function get_macro_settings($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'macros_settings';

        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table_name WHERE admin_id = %d", $user_id),
            ARRAY_A
        );
    }

    // Handle AJAX request to save macro settings
    public static function save_macro_settings() {
        check_ajax_referer('macro_settings_nonce', 'nonce');

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
        $table_name = $wpdb->prefix . 'macros_settings';

        foreach ($settings['calorie_percentage'] as $id => $calorie_percentage) {
            $result = $wpdb->update(
                $table_name,
                [
                    'calorie_percentage' => $calorie_percentage,
                    'protein_per_lb'     => $settings['protein_per_lb'][$id],
                    'carbs_leftover'     => $settings['carbs_leftover'][$id],
                    'fats_leftover'      => $settings['fats_leftover'][$id],
                ],
                [
                    'id' => $id,
                    'admin_id' => $user_id,
                ],
                ['%f', '%f', '%f', '%f'],
                ['%d', '%d']
            );

            if ($result === false) {
                error_log('Failed to update record ID ' . $id . ': ' . $wpdb->last_error);
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

// Initialize the EditMacroSettings class
EditMacroSettings::init();
