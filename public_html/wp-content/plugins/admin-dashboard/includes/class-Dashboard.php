<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Dashboard {

    // Initialize the Dashboard class
    public static function init() {
        add_shortcode('ad_dashboard', [__CLASS__, 'display_dashboard']);
    }

    // Method to handle the display of the Admin Dashboard content
    public static function display_dashboard() {
        // Allow access if in the WordPress admin area (for editing purposes)
        if (is_admin()) {
            return '<p>Editing Admin Dashboard content in admin mode.</p>';
        }

        // Ensure only 'admin' or 'master_admin' roles have access
        $user = wp_get_current_user();
        if (!is_user_logged_in() || (!in_array('admin', (array) $user->roles) && !in_array('master_admin', (array) $user->roles))) {
            wp_safe_redirect(home_url()); // Redirect unauthorized users
            exit;
        }

        // Dashboard content start
        $content = '<h2>Welcome to the Admin Dashboard</h2><p>Admin-only content here.</p>';

        // Check if macro settings have been initialized for the current admin
        $user_id = get_current_user_id();
        if (!self::macro_settings_initialized($user_id)) {
            $setup_link = esc_url(add_query_arg('setup_macros', '1')); 
            $content .= '<p style="color:white;"><a href="' . $setup_link . '">Setup Macro Settings</a></p>';
        }

        // Check if the setup action was triggered
        if (isset($_GET['setup_macros']) && $_GET['setup_macros'] === '1') {
            self::insert_default_macro_settings($user_id);
            $content .= '<p>Macro settings have been initialized.</p>';
        }

        return $content;
    }

    // Check if macro settings are initialized for the admin
    private static function macro_settings_initialized($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'macros_settings';
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE admin_id = %d", $user_id)) > 0;
    }

    // Insert default macro settings for the admin
    private static function insert_default_macro_settings($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'macros_settings';

        $default_values = [
    // Standard approach
    ['approach' => 'standard', 'goal' => 'lose_weight', 'variation' => NULL, 'calorie_percentage' => 0.85, 'protein_per_lb' => 1.0, 'carbs_leftover' => 0.6, 'fats_leftover' => 0.4],
    ['approach' => 'standard', 'goal' => 'maintain_weight', 'variation' => NULL, 'calorie_percentage' => 1.0, 'protein_per_lb' => 0.8, 'carbs_leftover' => 0.5, 'fats_leftover' => 0.5],
    ['approach' => 'standard', 'goal' => 'gain_weight', 'variation' => NULL, 'calorie_percentage' => 1.2, 'protein_per_lb' => 1.2, 'carbs_leftover' => 0.6, 'fats_leftover' => 0.4],

    // Carb Cycling approach
    ['approach' => 'carbCycling', 'goal' => 'lose_weight', 'variation' => 'lowCarb', 'calorie_percentage' => 0.85, 'protein_per_lb' => 1.0, 'carbs_leftover' => 0.2, 'fats_leftover' => 0.8],
    ['approach' => 'carbCycling', 'goal' => 'lose_weight', 'variation' => 'highCarb', 'calorie_percentage' => 0.85, 'protein_per_lb' => 1.0, 'carbs_leftover' => 0.8, 'fats_leftover' => 0.2],
    ['approach' => 'carbCycling', 'goal' => 'maintain_weight', 'variation' => 'lowCarb', 'calorie_percentage' => 1.0, 'protein_per_lb' => 0.85, 'carbs_leftover' => 0.2, 'fats_leftover' => 0.8],
    ['approach' => 'carbCycling', 'goal' => 'maintain_weight', 'variation' => 'highCarb', 'calorie_percentage' => 1.0, 'protein_per_lb' => 0.85, 'carbs_leftover' => 0.8, 'fats_leftover' => 0.2],
    ['approach' => 'carbCycling', 'goal' => 'gain_weight', 'variation' => 'lowCarb', 'calorie_percentage' => 1.2, 'protein_per_lb' => 0.75, 'carbs_leftover' => 0.2, 'fats_leftover' => 0.8],
    ['approach' => 'carbCycling', 'goal' => 'gain_weight', 'variation' => 'highCarb', 'calorie_percentage' => 1.2, 'protein_per_lb' => 0.75, 'carbs_leftover' => 0.8, 'fats_leftover' => 0.2],

    // Keto approach
    ['approach' => 'keto', 'goal' => 'lose_weight', 'variation' => NULL, 'calorie_percentage' => 0.85, 'protein_per_lb' => 1.0, 'carbs_leftover' => 0.2, 'fats_leftover' => 0.8],
    ['approach' => 'keto', 'goal' => 'maintain_weight', 'variation' => NULL, 'calorie_percentage' => 1.0, 'protein_per_lb' => 1.0, 'carbs_leftover' => 0.2, 'fats_leftover' => 0.8],
    ['approach' => 'keto', 'goal' => 'gain_weight', 'variation' => NULL, 'calorie_percentage' => 1.2, 'protein_per_lb' => 1.0, 'carbs_leftover' => 0.2, 'fats_leftover' => 0.8],
];

        foreach ($default_values as $default) {
            $wpdb->insert($table_name, [
                'admin_id' => $user_id,
                'approach' => $default['approach'],
                'goal' => $default['goal'],
                'variation' => $default['variation'],
                'calorie_percentage' => $default['calorie_percentage'],
                'protein_per_lb' => $default['protein_per_lb'],
                'carbs_leftover' => $default['carbs_leftover'],
                'fats_leftover' => $default['fats_leftover'],
            ]);
        }
    }
}
