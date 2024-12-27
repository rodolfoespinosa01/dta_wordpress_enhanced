<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class UserDashboard {

    // Initialize User Dashboard
    public static function init() {
        // Include other component classes
        include_once plugin_dir_path(__FILE__) . 'class-ProfileOverview.php';
include_once plugin_dir_path(__FILE__) . 'class-DailyMacros.php';
require_once plugin_dir_path(__FILE__) . 'class-UserDashboardUtils.php';
require_once plugin_dir_path(__FILE__) . 'class-MealSettings.php';
require_once plugin_dir_path(__FILE__) . 'class-MacroSettings.php';
require_once plugin_dir_path(__FILE__) . 'class-MealPlanDisplay.php';
require_once plugin_dir_path(__FILE__) . 'class-TDEEMultipliers.php';
require_once plugin_dir_path(__FILE__) . 'class-MealComboForm.php';



        
          
        // Initialize the Profile Overview, Daily Macros, and Meal Plan Macros components
        ProfileOverview::init();
        DailyMacros::init();


        // Register main dashboard shortcode
        add_shortcode('ud_dashboard', [__CLASS__, 'display_dashboard']);
    }

    // Display the main User Dashboard content
    public static function display_dashboard() {
        if (!is_user_logged_in()) {
            return '<p>You must be logged in to access the User Dashboard.</p>';
        }

        $user = wp_get_current_user();

        // Placeholder content for the landing page
        $content = '<h2>Welcome to Your Dashboard, ' . esc_html($user->display_name) . '!</h2>';
        $content .= '<p>Use the buttons below to navigate to different sections of your dashboard.</p>';

        // Note: The buttons/links to other pages will be added through the WordPress front-end tools

        return $content;
    }
}

// Initialize User Dashboard
UserDashboard::init();



