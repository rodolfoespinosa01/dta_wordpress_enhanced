<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Dashboard {

    // Initialize the Dashboard class
    public static function init() {
        add_shortcode('ad_dashboard', [__CLASS__, 'display_dashboard']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_dashboard_scripts']);
        add_action('wp_ajax_initialize_defaults', [__CLASS__, 'initialize_defaults']);
    }

    // Enqueue JavaScript for the dashboard
    public static function enqueue_dashboard_scripts() {
        if (is_page('admin-dashboard')) { // Adjust to the correct page slug or ID
            wp_enqueue_script('dashboard-init-script', plugins_url('dashboard-init.js', __FILE__), ['jquery'], null, true);
            wp_localize_script('dashboard-init-script', 'dashboardInit', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('dashboard_init_nonce'),
            ]);
        }
    }

    // Display the Admin Dashboard content
    public static function display_dashboard() {
        if (is_admin()) {
            return '<p>Editing Admin Dashboard content in admin mode.</p>';
        }

        // Ensure only 'admin' or 'master_admin' roles have access
        $user = wp_get_current_user();
        if (!is_user_logged_in() || (!in_array('admin', (array) $user->roles) && !in_array('master_admin', (array) $user->roles))) {
            wp_safe_redirect(home_url());
            exit;
        }

        $content = '<h2>Welcome to the Admin Dashboard</h2><p>Admin-only content here.</p>';
        $user_id = get_current_user_id();

        // Hidden input to trigger AJAX initialization if needed
        $content .= '<input type="hidden" id="init_defaults" data-user-id="' . esc_attr($user_id) . '">';

        return $content;
    }

    // AJAX handler to initialize default settings
    public static function initialize_defaults() {
        check_ajax_referer('dashboard_init_nonce', 'nonce');

        $user_id = get_current_user_id();
        $response = [];

        // Initialize macros if not done
        if (!self::macro_settings_initialized($user_id)) {
            self::insert_default_macro_settings($user_id);
            $response['macros_initialized'] = true;
        }

        // Initialize meals if not done
        if (!self::meal_settings_initialized($user_id)) {
            self::insert_default_meal_settings($user_id);
            $response['meals_initialized'] = true;
        }

        // Initialize TDEE multipliers if not done
        if (!self::tdee_multipliers_initialized($user_id)) {
            self::insert_default_tdee_multipliers($user_id);
            $response['tdee_initialized'] = true;
        }

        wp_send_json_success($response);
    }

    // Check if macro settings are initialized for the admin
    private static function macro_settings_initialized($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'macros_settings';
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE admin_id = %d", $user_id)) > 0;
    }

    private static function insert_default_macro_settings($user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'macros_settings';

    // Load default values from the data file
    $default_values = include plugin_dir_path(__FILE__) . 'data-default-macro-settings.php';

    foreach ($default_values as $default) {
        $wpdb->insert($table_name, [
            'admin_id'          => $user_id,
            'approach'          => $default['approach'],
            'goal'              => $default['goal'],
            'variation'         => $default['variation'],
            'calorie_percentage'=> $default['calorie_percentage'],
            'protein_per_lb'    => $default['protein_per_lb'],
            'carbs_leftover'    => $default['carbs_leftover'],
            'fats_leftover'     => $default['fats_leftover'],
        ]);
    }
}
    
    // Check if meal settings are initialized for the admin
private static function meal_settings_initialized($user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'meal_settings';
    return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE admin_id = %d", $user_id)) > 0;
}

// Insert default meal settings for the admin
private static function insert_default_meal_settings($user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'meal_settings';

    // Include the meal settings data from the data file
    $meal_settings = include plugin_dir_path(__FILE__) . 'data-default-meal-settings.php';

    foreach ($meal_settings as $approach => $meals_per_day) {
        foreach ($meals_per_day as $meals => $days) {
            if (in_array($approach, ['standard', 'keto'])) {
                foreach ($days as $day_type => $meals_array) {
                    foreach ($meals_array as $meal_number => $nutrients) {
                        $wpdb->insert($table_name, [
                            'admin_id'      => $user_id,
                            'approach'      => $approach,
                            'meals_per_day' => $meals,
                            'carb_type'     => NULL,
                            'day_type'      => $day_type,
                            'meal_number'   => $meal_number,
                            'protein'       => $nutrients['protein'],
                            'carbs'         => $nutrients['carbs'],
                            'fats'          => $nutrients['fats'],
                        ]);
                    }
                }
            } elseif ($approach === 'carbCycling') {
                foreach ($days as $carb_type => $day_types) {
                    foreach ($day_types as $day_type => $meals_array) {
                        foreach ($meals_array as $meal_number => $nutrients) {
                            $wpdb->insert($table_name, [
                                'admin_id'      => $user_id,
                                'approach'      => $approach,
                                'meals_per_day' => $meals,
                                'carb_type'     => $carb_type,
                                'day_type'      => $day_type,
                                'meal_number'   => $meal_number,
                                'protein'       => $nutrients['protein'],
                                'carbs'         => $nutrients['carbs'],
                                'fats'          => $nutrients['fats'],
                            ]);
                        }
                    }
                }
            }
        }
    }
}
// Check if TDEE multipliers are initialized for the admin
    private static function tdee_multipliers_initialized($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tdee_multipliers';
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE admin_id = %d", $user_id)) > 0;
    }

    // Insert default TDEE multipliers for the admin
    private static function insert_default_tdee_multipliers($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tdee_multipliers';

        // Load default TDEE multipliers from the data file
        $tdee_multipliers = include plugin_dir_path(__FILE__) . 'data-default-tdee-multipliers.php';

        foreach ($tdee_multipliers as $level => $days) {
            foreach ($days as $day => $multipliers) {
                $wpdb->insert($table_name, [
                    'admin_id'    => $user_id,
                    'level'       => $level,
                    'day'         => $day,
                    'workout_day' => $multipliers['workoutDay'],
                    'off_day'     => $multipliers['offDay'],
                ]);
            }
        }
    }
}