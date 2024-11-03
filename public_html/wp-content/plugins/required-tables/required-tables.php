<?php
/*
Plugin Name: Required Tables - DTA
Description: Creates necessary tables for Admin and User settings.
Version: 1.0
Author: Rodolfo EN
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin directory path
define('RT_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Include necessary table classes
include_once RT_PLUGIN_DIR . 'includes/class-MacroSettingsTable.php';
include_once RT_PLUGIN_DIR . 'includes/class-MealSettingsTable.php';
include_once RT_PLUGIN_DIR . 'includes/class-TdeeMultiplierTable.php';
include_once RT_PLUGIN_DIR . 'includes/class-UserInfoTable.php'; // Updated path to use RT_PLUGIN_DIR

// Activation hook to create tables
function rt_activate_required_tables() {
    MacroSettingsTable::create_table();
    MealSettingsTable::create_table();
    TdeeMultiplierTable::create_table();
    UserInfoTable::create_table();
}

register_activation_hook(__FILE__, 'rt_activate_required_tables');
