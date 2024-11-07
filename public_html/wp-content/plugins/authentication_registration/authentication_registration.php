<?php
/*
Plugin Name: Authentication & Registration - DTA
Description: A custom authentication plugin for managing user roles and authentication.
Version: 1.0
Author: Rodolfo EN
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define constants
define('CAP_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Include core classes
include_once CAP_PLUGIN_DIR . 'includes/class-Login.php';
include_once CAP_PLUGIN_DIR . 'includes/class-Register.php';
include_once CAP_PLUGIN_DIR . 'includes/class-Roles.php';
include_once CAP_PLUGIN_DIR . 'includes/class-Redirects.php';
include_once CAP_PLUGIN_DIR . 'includes/class-AccessControl.php';
include_once CAP_PLUGIN_DIR . 'includes/class-Menus.php';

// Activation and Deactivation Hooks
register_activation_hook(__FILE__, ['Roles', 'activate']);
register_deactivation_hook(__FILE__, ['Roles', 'deactivate']);

// Initialize plugin functionality
Login::init();
Register::init();
Roles::init();
Redirects::init();
AccessControl::init();
Menus::init();

