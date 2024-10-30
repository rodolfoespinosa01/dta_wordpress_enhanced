<?php
// Shortcode for User Dashboard
function ud_user_dashboard_shortcode() {
    // Get current user and check roles
    $user = wp_get_current_user();

    // Allow access if in the WordPress admin area or if user is Master Admin
    if (is_admin() || in_array('master_admin', (array) $user->roles)) {
        // Display edit mode message in admin area
        if (is_admin()) {
            return '<p>Editing User Dashboard content in admin mode.</p>';
        }
    } else {
        // For frontend access, restrict to 'user' role only
        if (!is_user_logged_in() || !in_array('user', (array) $user->roles)) {
            wp_safe_redirect(home_url()); // Redirect unauthorized users
            exit;
        }
    }

    // Fetch current user's associated admin and status
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
add_shortcode('ud_dashboard', 'ud_user_dashboard_shortcode');
