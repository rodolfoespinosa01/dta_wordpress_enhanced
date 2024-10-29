<?php
/*
Plugin Name: Custom Authentication Plugin
Description: A custom authentication plugin for managing user roles and authentication.
Version: 1.0
Author: Rodolfo EN
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define plugin directory path
define('CAP_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Include additional files
include_once CAP_PLUGIN_DIR . 'includes/register.php';
include_once CAP_PLUGIN_DIR . 'includes/login.php';
include_once CAP_PLUGIN_DIR . 'includes/dashboard.php';

// Activation Hook: Set up custom roles
function cap_auth_create_roles() {
    // Add Master Admin role
    add_role('master_admin', 'Master Admin', [
        'read' => true,
        'edit_users' => true,
        'list_users' => true,
        'create_users' => true,
        'delete_users' => true,
    ]);

    // Add Admin role
    add_role('admin', 'Admin', [
        'read' => true,
        'create_users' => true,
        'list_users' => true,
    ]);

    // Add User role
    add_role('user', 'User', [
        'read' => true,
    ]);
}
register_activation_hook(__FILE__, 'cap_auth_create_roles');

// Deactivation Hook: Remove custom roles
function cap_auth_remove_roles() {
    remove_role('master_admin');
    remove_role('admin');
    remove_role('user');
}
register_deactivation_hook(__FILE__, 'cap_auth_remove_roles');

// Add Logout Link to the Menu for Logged-in Users
function cap_auth_add_logout_link_to_menu($items, $args) {
    // Check if the user is logged in and if this is the primary menu
    if (is_user_logged_in() && $args->theme_location == 'primary') {
        $logout_url = wp_logout_url(home_url()); // Redirect to homepage on logout
        $items .= '<li class="menu-item"><a href="' . esc_url($logout_url) . '">Logout</a></li>';
    }
    return $items;
}
add_filter('wp_nav_menu_items', 'cap_auth_add_logout_link_to_menu', 10, 2);

