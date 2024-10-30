<?php
// Shortcode for Admin Dashboard
function cap_auth_admin_dashboard_shortcode() {
    // Ensure only users with 'admin' or 'master_admin' role have access
    $user = wp_get_current_user();
    if (!is_user_logged_in() || (!in_array('admin', (array) $user->roles) && !in_array('master_admin', (array) $user->roles))) {
        wp_safe_redirect(home_url()); // Redirect unauthorized users
        exit;
    }

    // Admin Dashboard content
    return '<h2>Welcome to the Admin Dashboard</h2><p>Admin-only content here.</p>';
}
add_shortcode('cap_auth_dashboard', 'cap_auth_admin_dashboard_shortcode');


// Shortcode for User Dashboard
function cap_auth_user_dashboard_shortcode() {
    // Allow access for 'user' or 'master_admin' roles only
    $user = wp_get_current_user();
    if (!is_user_logged_in() || (!in_array('user', (array) $user->roles) && !in_array('master_admin', (array) $user->roles))) {
        wp_safe_redirect(home_url()); // Redirect unauthorized users
        exit;
    }

    // Get the current user's associated admin and status
    $user_id = get_current_user_id();
    $associated_admin_id = get_user_meta($user_id, 'associated_admin', true);
    $status = get_user_meta($user_id, 'status', true);

    // Prepare dashboard message based on association status
    if ($status === 'Unassigned' || empty($associated_admin_id)) {
        $dashboard_message = "<p>You are currently unassigned to any specific admin. Please contact support for further assistance.</p>";
    } else {
        $admin_user = get_user_by('ID', $associated_admin_id);
        $admin_name = $admin_user ? esc_html($admin_user->display_name) : 'Your Admin';

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

