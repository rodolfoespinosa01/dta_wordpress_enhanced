<?php
// Shortcode for Admin Dashboard
function cap_auth_admin_dashboard_shortcode() {
    // Restrict access to Admin role only
    if (!is_user_logged_in() || !current_user_can('admin')) {
        wp_redirect(home_url()); // Redirect non-admin users to the homepage
        exit;
    }

    // Dashboard content for admins
    return '<h2>Welcome to the Admin Dashboard</h2><p>Admin-only content here.</p>';
}
add_shortcode('cap_auth_dashboard', 'cap_auth_admin_dashboard_shortcode');

function cap_auth_user_dashboard_shortcode() {
    // Allow access if in the WordPress admin area (to allow editing)
    if (is_admin()) {
        return '<p>Editing User Dashboard content in admin mode.</p>';
    }

    // Get the current user info
    $user_id = get_current_user_id();
    $user = wp_get_current_user();

    // Restrict access: allow only Users or Master Admin
    if (!is_user_logged_in() || (!in_array('user', $user->roles) && !in_array('master_admin', $user->roles))) {
        wp_redirect(home_url()); // Redirect non-authorized roles to the homepage
        exit;
    }

    // Get the associated admin for the current user
    $associated_admin_id = get_user_meta($user_id, 'associated_admin', true);
    $status = get_user_meta($user_id, 'status', true);

    // Prepare dashboard message based on association status
    if ($status === 'Unassigned' || empty($associated_admin_id)) {
        $dashboard_message = "<p>You are currently unassigned to any specific admin. Please contact support for further assistance.</p>";
    } else {
        $admin_user = get_user_by('ID', $associated_admin_id);
        $admin_name = $admin_user ? $admin_user->display_name : 'Your Admin';

        $dashboard_message = "<p>Welcome to your dashboard! You are currently associated with admin: <strong>$admin_name</strong>.</p>";
    }

    // Display the dashboard content
    ob_start();
    ?>
    <div class="user-dashboard">
        <h2>User Dashboard</h2>
        <?php echo $dashboard_message; ?>
        <p>Here you can manage your profile, see your admin's updates, and more!</p>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('cap_auth_user_dashboard', 'cap_auth_user_dashboard_shortcode');

