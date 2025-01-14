<?php
/*
Plugin Name: Magic Meal Plan Plugin
Description: A plugin to calculate and manage custom meal plans, now with Sunday automation.
Version: 1.2
Author: Rodolfo EN
*/

if (!defined('ABSPATH')) {
    exit;
}

// Define the plugin directory and assets URL
define('MAGIC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MAGIC_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include necessary files for meal plan steps
require_once MAGIC_PLUGIN_DIR . 'includes/sunday/class-step1.php';
require_once MAGIC_PLUGIN_DIR . 'includes/sunday/class-step2.php';
require_once MAGIC_PLUGIN_DIR . 'includes/sunday/class-step3.php';
require_once MAGIC_PLUGIN_DIR . 'includes/sunday/class-step4.php';
require_once MAGIC_PLUGIN_DIR . 'includes/sunday/class-step5.php';
require_once MAGIC_PLUGIN_DIR . 'includes/sunday/class-step6.php';
require_once MAGIC_PLUGIN_DIR . 'includes/sunday/class-step7.php';
require_once MAGIC_PLUGIN_DIR . 'includes/sunday/class-step8.php';
require_once MAGIC_PLUGIN_DIR . 'includes/sunday/class-step9.php';
require_once MAGIC_PLUGIN_DIR . 'includes/sunday/class-step10.php';
require_once MAGIC_PLUGIN_DIR . 'includes/sunday/class-sunday_detailed.php';
require_once MAGIC_PLUGIN_DIR . 'includes/sunday/class-sundayImages.php';

// Include the automation handler
require_once MAGIC_PLUGIN_DIR . 'includes/sunday/automation_sunday.php';

// Initialize the plugin
function magic_initialize_plugin() {
    // Initialize each step's class
    Step1::init();
    Step2::init();
    Step3::init();
    Step4::init();
    Step5::init();
    Step6::init();
    Step7::init();
    Step8::init();
    Step9::init();
    Step10::init();
    SundayDetailedMealPlan::init();

    // Initialize the automation handler
    AutomationSunday::init();
}
add_action('plugins_loaded', 'magic_initialize_plugin');

// Enqueue JavaScript file from the includes/sunday directory
function magic_enqueue_scripts() {
    wp_enqueue_script(
        'automation-sunday-script',
        MAGIC_PLUGIN_URL . 'includes/sunday/automation_sunday.js', // Path to the JS file
        ['jquery'], // Dependencies
        null, // No specific version
        true // Load in the footer
    );

    // Pass the admin AJAX URL to the script
    wp_localize_script('automation-sunday-script', 'ajaxParams', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
    ]);
}
add_action('wp_enqueue_scripts', 'magic_enqueue_scripts');
