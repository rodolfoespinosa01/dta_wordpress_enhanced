<?php

// Enqueue parent and child theme styles
function astra_child_enqueue_styles() {
    wp_enqueue_style('astra-parent-style', get_template_directory_uri() . '/style.css');
    wp_enqueue_style('astra-child-style', get_stylesheet_directory_uri() . '/style.css', array('astra-parent-style'));
}
add_action('wp_enqueue_scripts', 'astra_child_enqueue_styles');

// Redirect users from the WordPress dashboard if they are not Master Admin (manage_options capability)
function restrict_dashboard_access() {
    if (is_admin() && !current_user_can('manage_options') && !wp_doing_ajax()) {
        wp_redirect(site_url());
        exit;
    }
}
add_action('admin_init', 'restrict_dashboard_access');

// Redirect users after login based on role
function custom_login_redirect($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        if (in_array('admin', $user->roles)) {
            return site_url('/admin-dashboard');
        } elseif (in_array('user', $user->roles)) {
            return site_url('/user-dashboard');
        }
    }
    return $redirect_to;
}
add_filter('login_redirect', 'custom_login_redirect', 10, 3);

// Unified function to restrict access to role-specific dashboard pages
function restrict_role_dashboard_access() {
    global $post;
    $restricted_pages = [
        'admin-dashboard' => 'admin', // Only Admins can access /admin-dashboard
        'user-dashboard'  => 'user'   // Only Users can access /user-dashboard
    ];

    if (is_page() && isset($restricted_pages[$post->post_name])) {
        $required_role = $restricted_pages[$post->post_name];
        if (!current_user_can($required_role)) {
            wp_redirect(home_url());
            exit;
        }
    }
}
add_action('template_redirect', 'restrict_role_dashboard_access');
