<?php
/*
Plugin Name: User Dashboard - DTA
Description: A custom plugin to handle the User dashboard functionalities.
Version: 1.0
Author: Rodolfo EN
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin directory path
define('UD_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Include necessary files
include_once UD_PLUGIN_DIR . 'includes/class-UserDashboard.php';

// Initialize the plugin
function ud_initialize_user_dashboard() {
    UserDashboard::init();
}
add_action('plugins_loaded', 'ud_initialize_user_dashboard');
