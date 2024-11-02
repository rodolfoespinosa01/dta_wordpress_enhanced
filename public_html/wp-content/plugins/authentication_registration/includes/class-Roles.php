<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Roles {

    // Activation Hook - Set up roles
    public static function activate() {
        self::create_roles();
        flush_rewrite_rules(); // Ensure new rules take effect
    }

    // Deactivation Hook - Clean up roles
    public static function deactivate() {
        self::remove_roles();
        flush_rewrite_rules();
    }

    // Initialize Hooks for Role-Based Restrictions
    public static function init() {
        // Restrict access to WP dashboard for certain roles
        add_action('admin_init', [__CLASS__, 'restrict_dashboard_access']);
    }

    // Method to create custom roles
    private static function create_roles() {
        // Define capabilities for each role
        $master_admin_capabilities = [
            'read'          => true,
            'edit_users'    => true,
            'list_users'    => true,
            'create_users'  => true,
            'delete_users'  => true,
            'manage_options' => true, // Needed to access WP dashboard
        ];

        $admin_capabilities = [
            'read'          => true,
            'create_users'  => true,
            'list_users'    => true,
        ];

        $user_capabilities = [
            'read'          => true,
        ];

        // Add roles with defined capabilities
        if (!get_role('master_admin')) {
            add_role('master_admin', 'Master Admin', $master_admin_capabilities);
        }

        if (!get_role('admin')) {
            add_role('admin', 'Admin', $admin_capabilities);
        }

        if (!get_role('user')) {
            add_role('user', 'User', $user_capabilities);
        }
    }

    // Method to remove custom roles
    private static function remove_roles() {
        remove_role('master_admin');
        remove_role('admin');
        remove_role('user');
    }

    // Restrict Dashboard Access for Non-Master Admins
    public static function restrict_dashboard_access() {
    if (is_admin() && !current_user_can('manage_options') && !wp_doing_ajax() && !defined('REST_REQUEST')) {
        wp_redirect(home_url()); // Redirect to homepage or other page
        exit;
    }
}

    // Optional: Utility Method to Check Roles
    public static function user_has_role($user, $role) {
        return in_array($role, (array) $user->roles);
    }
}
