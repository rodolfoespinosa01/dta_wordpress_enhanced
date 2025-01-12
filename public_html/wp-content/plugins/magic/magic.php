<?php
/*
Plugin Name: Magic Meal Plan Plugin
Description: A plugin to calculate and manage custom meal plans, now with step-specific shortcodes.
Version: 1.1
Author: Rodolfo EN
*/

if (!defined('ABSPATH')) {
    exit;
}

// Define the plugin directory
define('MAGIC_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Include the necessary files for meal plan steps
require_once MAGIC_PLUGIN_DIR . 'includes/sunday/class-step1.php';
require_once MAGIC_PLUGIN_DIR . 'includes/sunday/class-step2.php';
require_once MAGIC_PLUGIN_DIR . 'includes/sunday/class-step3.php';
require_once MAGIC_PLUGIN_DIR . 'includes/sunday/class-step4.php';
require_once MAGIC_PLUGIN_DIR . 'includes/sunday/class-step5.php';




// Initialize the plugin
function magic_initialize_plugin() {
    // Initialize each step's class
    Step1::init();
    Step2::init();
    Step3::init();
    Step4::init();
    Step5::init();
   
}
add_action('plugins_loaded', 'magic_initialize_plugin');
