<?php
/*
Plugin Name: Admin Dashboard Plugin
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

// Include necessary files (for moving over code shortly)
include_once AD_PLUGIN_DIR . 'includes/dashboard.php';
