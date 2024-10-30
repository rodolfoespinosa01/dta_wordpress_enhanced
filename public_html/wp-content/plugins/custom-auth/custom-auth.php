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

// Activation Hook: Set up custom roles and flush rewrite rules
function cap_auth_activate() {
    cap_auth_create_roles();
    flush_rewrite_rules(); // Flush rewrite rules if needed
}
register_activation_hook(__FILE__, 'cap_auth_activate');

// Deactivation Hook: Remove custom roles
function cap_auth_deactivate() {
    cap_auth_remove_roles();
    flush_rewrite_rules(); // Clean up rewrite rules on deactivation
}
register_deactivation_hook(__FILE__, 'cap_auth_deactivate');

// Create custom roles with specific capabilities
function cap_auth_create_roles() {
    // Add Master Admin role if it doesn’t exist
    if (!get_role('master_admin')) {
        add_role('master_admin', 'Master Admin', [
            'read' => true,
            'edit_users' => true,
            'list_users' => true,
            'create_users' => true,
            'delete_users' => true,
        ]);
    }

    // Add Admin role if it doesn’t exist
    if (!get_role('admin')) {
        add_role('admin', 'Admin', [
            'read' => true,
            'create_users' => true,
            'list_users' => true,
        ]);
    }

    // Add User role if it doesn’t exist
    if (!get_role('user')) {
        add_role('user', 'User', [
            'read' => true,
        ]);
    }
}

// Remove custom roles on deactivation
function cap_auth_remove_roles() {
    remove_role('master_admin');
    remove_role('admin');
    remove_role('user');
}

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

// Optional: Redirect users after login based on role
function cap_auth_redirect_after_login($redirect_to, $request, $user) {
    // Check if the user is an object and has roles assigned
    if (is_object($user) && is_array($user->roles)) {
        if (in_array('master_admin', $user->roles)) {
            return home_url('/master-admin-dashboard');
        } elseif (in_array('admin', $user->roles)) {
            return home_url('/admin-dashboard');
        } elseif (in_array('user', $user->roles)) {
            return home_url('/user-dashboard');
        }
    }
    return $redirect_to; // Default redirect if no role matches
}
add_filter('login_redirect', 'cap_auth_redirect_after_login', 10, 3);
