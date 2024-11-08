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
        if ((!is_user_logged_in() || (!in_array('admin', (array) $user->roles) && !in_array('master_admin', (array) $user->roles))) && !(defined('REST_REQUEST') && REST_REQUEST) && !wp_doing_ajax()) {
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

        if (!self::macro_settings_initialized($user_id)) {
            self::insert_default_macro_settings($user_id);
            $response['macros_initialized'] = true;
        }

        if (!self::meal_settings_initialized($user_id)) {
            self::insert_default_meal_settings($user_id);
            $response['meals_initialized'] = true;
        }

        if (!self::tdee_multipliers_initialized($user_id)) {
            self::insert_default_tdee_multipliers($user_id);
            $response['tdee_initialized'] = true;
        }

        error_log(print_r($response, true));
        wp_send_json_success($response);
        exit;
    }

    private static function macro_settings_initialized($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'macros_settings';
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE admin_id = %d", $user_id)) > 0;
    }

    private static function insert_default_macro_settings($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'macros_settings';
        $default_values = include plugin_dir_path(__FILE__) . 'data-default-macro-settings.php';

        foreach ($default_values as $default) {
            $result = $wpdb->insert($table_name, [
                'admin_id'          => $user_id,
                'approach'          => $default['approach'],
                'goal'              => $default['goal'],
                'variation'         => $default['variation'],
                'calorie_percentage'=> $default['calorie_percentage'],
                'protein_per_lb'    => $default['protein_per_lb'],
                'carbs_leftover'    => $default['carbs_leftover'],
                'fats_leftover'     => $default['fats_leftover'],
            ]);

            if (false === $result) {
                error_log('Database insert error in macro settings: ' . $wpdb->last_error);
            }
        }
    }
    
    private static function meal_settings_initialized($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'meal_settings';
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE admin_id = %d", $user_id)) > 0;
    }

   private static function insert_default_meal_settings($user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'meal_settings';
    $meal_settings = include plugin_dir_path(__FILE__) . 'data-default-meal-settings.php';

    foreach ($meal_settings as $approach => $meals_per_day) {
        foreach ($meals_per_day as $meals => $days) {
            if (in_array($approach, ['standard', 'keto'])) {
                // Handle 'standard' and 'keto' meal plans
                foreach ($days as $day_type => $meals_array) {
                    foreach ($meals_array as $meal_number => $nutrients) {
                        $result = $wpdb->insert($table_name, [
                            'admin_id'      => $user_id,
                            'approach'      => $approach,
                            'meals_per_day' => $meals,
                            'carb_type'     => NULL, // No carb type for standard or keto
                            'day_type'      => $day_type,
                            'meal_number'   => $meal_number,
                            'protein'       => $nutrients['protein'],
                            'carbs'         => $nutrients['carbs'],
                            'fats'          => $nutrients['fats'],
                        ]);

                        if (false === $result) {
                            error_log('Database insert error in meal settings: ' . $wpdb->last_error);
                        }
                    }
                }
            } elseif ($approach === 'carbCycling') {
                // Handle 'carbCycling' meal plan
                foreach ($days as $carb_type => $day_settings) {
                    foreach ($day_settings as $day_type => $meals_array) {
                        foreach ($meals_array as $meal_number => $nutrients) {
                            $result = $wpdb->insert($table_name, [
                                'admin_id'      => $user_id,
                                'approach'      => $approach,
                                'meals_per_day' => $meals,
                                'carb_type'     => $carb_type, // 'lowCarb' or 'highCarb'
                                'day_type'      => $day_type,
                                'meal_number'   => $meal_number,
                                'protein'       => $nutrients['protein'],
                                'carbs'         => $nutrients['carbs'],
                                'fats'          => $nutrients['fats'],
                            ]);

                            if (false === $result) {
                                error_log('Database insert error in carbCycling settings: ' . $wpdb->last_error);
                            }
                        }
                    }
                }
            }
        }
    }
}


    private static function tdee_multipliers_initialized($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tdee_multipliers';
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE admin_id = %d", $user_id)) > 0;
    }

    private static function insert_default_tdee_multipliers($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tdee_multipliers';

        $tdee_multipliers = include plugin_dir_path(__FILE__) . 'data-default-tdee-multipliers.php';

        foreach ($tdee_multipliers as $level => $days) {
            foreach ($days as $day => $multipliers) {
                $result = $wpdb->insert($table_name, [
                    'admin_id'    => $user_id,
                    'level'       => $level,
                    'day'         => $day,
                    'workout_day' => $multipliers['workoutDay'],
                    'off_day'     => $multipliers['offDay'],
                ]);

                if (false === $result) {
                    error_log('Database insert error in TDEE multipliers: ' . $wpdb->last_error);
                }
            }
        }
    }
}
