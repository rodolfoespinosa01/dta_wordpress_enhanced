<?php
/*
Plugin Name: Magic Plugin
Description: Automates meal plan calculations for each day of the week.
Version: 1.0
Author: Rodolfo EN
*/

if (!defined('ABSPATH')) {
    exit;
}

// Define paths
define('MAGIC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MAGIC_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include Sunday-specific functionality
include_once MAGIC_PLUGIN_DIR . 'includes/sunday/automation.php';

// Enqueue scripts and styles
add_action('wp_enqueue_scripts', function () {
    if (is_page('sunday')) { // Ensure scripts only load on the Sunday page
        wp_enqueue_script('sunday-automation', MAGIC_PLUGIN_URL . 'includes/sunday/sunday.js', ['jquery'], null, true);
        wp_localize_script('sunday-automation', 'magic_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('magic_nonce'),
        ]);
    }
});
