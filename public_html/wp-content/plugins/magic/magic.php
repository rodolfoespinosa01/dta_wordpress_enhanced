<?php
/*
Plugin Name: Magic Automation Plugin
Description: A plugin to automate calculations and workflows for meal plans, including Sunday-specific logic.
Version: 1.0
Author: Rodolfo EN
*/

if (!defined('ABSPATH')) {
    exit;
}

// Define the plugin directory
define('MAGIC_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Include the necessary files for Sunday Automation
require_once MAGIC_PLUGIN_DIR . 'includes/sunday/automation.php';
require_once MAGIC_PLUGIN_DIR . 'includes/sunday/class-step1.php';
require_once MAGIC_PLUGIN_DIR . 'includes/sunday/class-step2.php';

// Initialize the plugin
function magic_initialize_plugin() {
    // Sunday Automation
    SundayAutomation::init();
}
add_action('plugins_loaded', 'magic_initialize_plugin');

// Enqueue Scripts and Styles
function magic_enqueue_assets() {
    // Only enqueue on specific pages where automation runs
    if (is_page('sunday')) { // Replace 'sunday' with the slug of your Sunday page
        wp_enqueue_script(
            'magic-sunday-js',
            plugins_url('includes/sunday/sunday.js', __FILE__),
            ['jquery'],
            null,
            true
        );

        // Localize script for AJAX
        wp_localize_script('magic-sunday-js', 'magicAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('magic_nonce'),
        ]);

        wp_enqueue_style(
            'magic-sunday-css',
            plugins_url('includes/sunday/sunday.css', __FILE__)
        );
    }
}
add_action('wp_enqueue_scripts', 'magic_enqueue_assets');
