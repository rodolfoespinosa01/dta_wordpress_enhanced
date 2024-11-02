<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Redirects {

    // Initialize the redirection hooks
    public static function init() {
        // Redirect users after login based on their role
        add_filter('login_redirect', [__CLASS__, 'redirect_after_login'], 10, 3);

        // Redirect users trying to access WordPress admin dashboard if they are not master_admin
        add_action('admin_init', [__CLASS__, 'restrict_admin_dashboard_access']);

        // Redirect users based on role-specific frontend dashboard pages
        add_action('template_redirect', [__CLASS__, 'restrict_role_dashboard_access']);
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
        return $redirect_to; // Default redirect if no role matches
    }

    // Restrict access to WP Admin for non-master_admin roles
    public static function restrict_admin_dashboard_access() {
        if (is_admin() && !current_user_can('manage_options') && !wp_doing_ajax() && !defined('REST_REQUEST')) {
            wp_redirect(home_url()); // Redirect to homepage or another accessible page
            exit;
        }
    }

    // Restrict access to role-specific frontend dashboard pages
    public static function restrict_role_dashboard_access() {
        global $post;

        if ((defined('REST_REQUEST') && REST_REQUEST) || wp_doing_ajax()) {
            return; // Skip this function during REST or AJAX requests
        }

        $restricted_pages = [
            'admin-dashboard' => 'admin',       // Only Admins can access /admin-dashboard
            'user-dashboard'  => 'user',        // Only Users can access /user-dashboard
            'master-admin-dashboard' => 'master_admin' // Only Master Admins can access /master-admin-dashboard
        ];

        if (is_page() && isset($restricted_pages[$post->post_name])) {
            $required_role = $restricted_pages[$post->post_name];
            if (!current_user_can($required_role)) {
                wp_redirect(home_url()); // Redirect to homepage or another accessible page
                exit;
            }
        }
    }
}
