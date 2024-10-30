<?php
// Shortcode for Admin Dashboard
function ad_admin_dashboard_shortcode() {
    // Allow access if in the WordPress admin area (for editing purposes)
    if (is_admin()) {
        return '<p>Editing Admin Dashboard content in admin mode.</p>';
    }

    // Ensure only 'admin' or 'master_admin' roles have access
    $user = wp_get_current_user();
    if (!is_user_logged_in() || (!in_array('admin', (array) $user->roles) && !in_array('master_admin', (array) $user->roles))) {
        wp_safe_redirect(home_url()); // Redirect unauthorized users
        exit;
    }

    // Admin Dashboard content
    return '<h2>Welcome to the Admin Dashboard</h2><p>Admin-only content here.</p>';
}
add_shortcode('ad_dashboard', 'ad_admin_dashboard_shortcode');
