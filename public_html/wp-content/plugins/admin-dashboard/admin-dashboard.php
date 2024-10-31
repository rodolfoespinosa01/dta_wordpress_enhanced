<?php
/*
Plugin Name: Admin Dashboard - DTA
Description: A custom plugin to handle the Admin dashboard functionalities.
Version: 1.0
Author: Rodolfo EN
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin directory path
define('AD_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Include necessary files
include_once AD_PLUGIN_DIR . 'includes/class-Dashboard.php';

// Initialize the plugin
function ad_initialize_admin_dashboard() {
    Dashboard::init();
}
add_action('plugins_loaded', 'ad_initialize_admin_dashboard');
