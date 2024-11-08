<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AccessControl {

    // Initialize Access Control hooks
    public static function init() {
        // Restrict access to WordPress admin area for non-master_admins
        add_action('admin_init', [__CLASS__, 'restrict_admin_dashboard_access']);
        
        // Redirect users after login based on their role
        add_filter('login_redirect', [__CLASS__, 'redirect_after_login'], 10, 3);

        // Restrict access to role-specific frontend dashboard pages
        add_action('template_redirect', [__CLASS__, 'restrict_role_dashboard_access']);
    }

    // Restrict access to WP Admin for non-master_admin roles
    public static function restrict_admin_dashboard_access() {
        if (is_admin() && !current_user_can('manage_options') && !wp_doing_ajax() && !defined('REST_REQUEST')) {
            wp_redirect(home_url()); // Redirect to homepage or another appropriate page
            exit;
        }
    }

    // Redirect users after login based on their role
    public static function redirect_after_login($redirect_to, $request, $user) {
        if (isset($user->roles) && is_array($user->roles)) {
            if (in_array('master_admin', $user->roles)) {
                return home_url('/master-admin-dashboard');
            } elseif (in_array('admin', $user->roles)) {
                return home_url('/admin-dashboard');
            } elseif (in_array('user', $user->roles)) {
                return home_url('/user-dashboard');
            }
        }
        return home_url(); // Default redirect to home if no role matches
    }

    // Restrict access to role-specific frontend dashboard pages
    public static function restrict_role_dashboard_access() {
        global $post;

        // Define role-based page restrictions
        $restricted_pages = [
            // Admin-only pages
            'admin-dashboard'               => 'admin',
            'admin-dashboard/client-list'   => 'admin',
            'admin-dashboard/edit-meal-settings' => 'admin',
            'admin-dashboard/edit-macro-settings' => 'admin',
            'admin-dashboard/edit-tdee-multipliers' => 'admin',

            // User-only pages
            'user-dashboard'    => 'user',
            'profile-overview'  => 'user',
            'daily-macros'      => 'user',
            'carb-cycling'      => 'user', // Only Users can access /carb-cycling (if applicable)
        ];

        // If the page is restricted by role, check if the user has the required role
        if (is_page() && isset($restricted_pages[$post->post_name]) && !wp_doing_ajax() && !defined('REST_REQUEST')) {
            $required_role = $restricted_pages[$post->post_name];
            if (!current_user_can($required_role)) {
                wp_redirect(home_url()); // Redirect to homepage or another accessible page
                exit;
            }
        }
    }
}

// Initialize Access Control
AccessControl::init();
