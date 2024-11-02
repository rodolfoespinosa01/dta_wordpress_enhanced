<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class UserDashboard {

    // Initialize the UserDashboard class
    public static function init() {
        add_shortcode('ud_dashboard', [__CLASS__, 'display_dashboard']);
    }

    // Method to handle the display of the User Dashboard content
    public static function display_dashboard() {
        // Get the current user and check roles
        $user = wp_get_current_user();

        // Allow access if in the admin area or if the user is a Master Admin
        if (is_admin() || in_array('master_admin', (array) $user->roles)) {
            // Display edit mode message in the admin area
            if (is_admin()) {
                return '<p>Editing User Dashboard content in admin mode.</p>';
            }
        } else {
            // For frontend access, restrict to 'user' role only
            if ((!is_user_logged_in() || !in_array('user', (array) $user->roles)) && !(defined('REST_REQUEST') && REST_REQUEST) && !wp_doing_ajax()) {
                wp_safe_redirect(home_url()); // Redirect unauthorized users
                exit;
            }
        }

        // Fetch current user's associated admin and status
        $user_id = get_current_user_id();
        $associated_admin_id = get_user_meta($user_id, 'associated_admin', true);
        $status = get_user_meta($user_id, 'status', true);

        // Prepare a dashboard message based on association status
        if ($status === 'Unassigned' || empty($associated_admin_id)) {
            $dashboard_message = "<p>You are currently unassigned to any specific admin. Please contact support for further assistance.</p>";
        } else {
            if (!empty($associated_admin_id)) {
                $admin_user = get_user_by('ID', $associated_admin_id);
                $admin_name = $admin_user ? esc_html($admin_user->display_name) : 'Your Admin';
            } else {
                $admin_name = 'Your Admin';
            }
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
}
