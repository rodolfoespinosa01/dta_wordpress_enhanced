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
include_once AD_PLUGIN_DIR . 'includes/edit-macro-settings.php'; 
include_once AD_PLUGIN_DIR . 'includes/edit-meal-settings.php'; 
include_once AD_PLUGIN_DIR . 'includes/edit-tdee-multipliers.php'; 
include_once AD_PLUGIN_DIR . 'includes/list-clients.php';




// Initialize the plugin
function ad_initialize_admin_dashboard() {
    if (defined('REST_REQUEST') && REST_REQUEST) {
        error_log('Skipping Dashboard initialization during REST request.');
        return;
    }

    error_log('Initializing Dashboard plugin.');
    Dashboard::init();
}


add_action('plugins_loaded', 'ad_initialize_admin_dashboard');
add_action('template_redirect', ['Redirects', 'restrict_role_dashboard_access']);

