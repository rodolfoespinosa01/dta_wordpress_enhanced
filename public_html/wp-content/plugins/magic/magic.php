<?php
/*
Plugin Name: Magic
Plugin URI: https://example.com
Description: Plugin for calculating meal macros and errors.
Version: 1.0
Author: Your Name
Author URI: https://example.com
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('MAGIC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MAGIC_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once MAGIC_PLUGIN_DIR . 'includes/class-Step1.php';
require_once MAGIC_PLUGIN_DIR . 'includes/class-Step2.php'; // Add this line for Step 2

// Initialize classes
add_action('plugins_loaded', function () {
    Step1::init();
    Step2::init(); // Initialize Step 2
});
