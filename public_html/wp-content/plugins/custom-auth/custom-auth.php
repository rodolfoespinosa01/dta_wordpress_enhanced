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

function cap_auth_dynamic_menu_items($items, $args) {
    // Check if this is the 'main' menu location
    if ($args->theme_location == 'primary') {
        foreach ($items as $key => $item) {
            // Get the current user and their role
            $user = wp_get_current_user();
            $is_logged_in = is_user_logged_in();
            $is_admin = in_array('admin', (array) $user->roles);
            $is_user = in_array('user', (array) $user->roles);

            // Hide Admin Dashboard unless logged in as Admin
            if ($item->title == 'Admin Dashboard' && (!$is_logged_in || !$is_admin)) {
                unset($items[$key]);
            }

            // Hide User Dashboard unless logged in as User
            if ($item->title == 'User Dashboard' && (!$is_logged_in || !$is_user)) {
                unset($items[$key]);
            }

            // Hide User Login and User Registration if any user is logged in
            if (($item->title == 'User Login' || $item->title == 'User Registration') && $is_logged_in) {
                unset($items[$key]);
            }

            // Add a custom "Log Out" link if the user is logged in
            if ($is_logged_in && $item->title == 'User Registration') {
                $logout_url = wp_logout_url(home_url()); // Redirect to homepage after logout
                $logout_item = (object) [
                    'title' => 'Log Out',
                    'url' => $logout_url,
                    'menu_item_parent' => 0,
                    'ID' => 'logout',
                    'db_id' => 'logout',
                    'classes' => ['menu-item', 'menu-item-logout'],
                ];
                $items[] = $logout_item; // Add the logout item
            }
        }
    }

    return $items;
}
add_filter('wp_nav_menu_objects', 'cap_auth_dynamic_menu_items', 10, 2);

function cap_auth_restrict_admin_access() {
    // Get current user
    $user = wp_get_current_user();

    // Check if user has 'admin' role and is accessing wp-admin
    if (in_array('admin', (array) $user->roles) && is_admin() && !defined('DOING_AJAX')) {
        wp_redirect(home_url('/admin-dashboard')); // Redirect to custom admin dashboard
        exit;
    }
}
add_action('admin_init', 'cap_auth_restrict_admin_access');


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
