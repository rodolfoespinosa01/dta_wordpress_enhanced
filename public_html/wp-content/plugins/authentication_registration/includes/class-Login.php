<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Login {

    // Initialize the Login class
    public static function init() {
        add_shortcode('cap_auth_admin_login', [__CLASS__, 'admin_login_form']);
        add_shortcode('cap_auth_user_login', [__CLASS__, 'user_login_form']);
    }

    // Admin login form shortcode handler
    public static function admin_login_form() {
        // Check if the user is already logged in
        if (is_user_logged_in()) {
            return '<p>You are already logged in.</p>';
        }

        // Form processing
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['password'])) {
            // Verify nonce for CSRF protection
            if (!isset($_POST['cap_login_nonce']) || !wp_verify_nonce($_POST['cap_login_nonce'], 'cap_login_action')) {
                return '<p>Nonce verification failed. Please try again.</p>';
            }

            $email = sanitize_email($_POST['email']);
            $password = sanitize_text_field($_POST['password']);

            // Attempt to log the user in
            $user = wp_signon([
                'user_login' => $email,
                'user_password' => $password,
                'remember' => true,
            ]);

            // Check for login errors
            if (is_wp_error($user)) {
                return '<p>Login failed: ' . esc_html($user->get_error_message()) . '</p>';
            }

            // Verify user role
            if (!in_array('admin', (array) $user->roles) && !in_array('master_admin', (array) $user->roles)) {
                wp_logout(); // Log out the user if they aren't an Admin or Master Admin
                return '<p>Access denied: This login form is for Admins only.</p>';
            }

            // Redirect to Admin Dashboard if login is successful and user role is valid
            wp_safe_redirect(site_url('/admin-dashboard'));
            exit;
        }

        // Display the admin login form with a nonce
        ob_start();
        ?>
        <form method="POST">
            <?php wp_nonce_field('cap_login_action', 'cap_login_nonce'); ?>
            <label for="email">Email:</label>
            <input type="email" name="email" required>
            <br>
            <label for="password">Password:</label>
            <input type="password" name="password" required>
            <br>
            <button type="submit">Login</button>
        </form>
        <?php
        return ob_get_clean();
    }

    // User login form shortcode handler
    public static function user_login_form() {
        // Check if the user is already logged in
        if (is_user_logged_in()) {
            return '<p>You are already logged in.</p>';
        }

        // Form processing
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['password'])) {
            // Verify nonce for CSRF protection
            if (!isset($_POST['cap_login_nonce']) || !wp_verify_nonce($_POST['cap_login_nonce'], 'cap_login_action')) {
                return '<p>Nonce verification failed. Please try again.</p>';
            }

            $email = sanitize_email($_POST['email']);
            $password = sanitize_text_field($_POST['password']);

            // Attempt to log the user in
            $user = wp_signon([
                'user_login' => $email,
                'user_password' => $password,
                'remember' => true,
            ]);

            // Check for login errors
            if (is_wp_error($user)) {
                return '<p>Login failed: ' . esc_html($user->get_error_message()) . '</p>';
            }

            // Verify user role
            if (!in_array('user', (array) $user->roles)) {
                wp_logout(); // Log out the user if they aren't a standard User
                return '<p>Access denied: This login form is for Users only.</p>';
            }

            // Redirect to User Dashboard if login is successful and user role is valid
            wp_safe_redirect(site_url('/user-dashboard'));
            exit;
        }

        // Display the user login form with a nonce
        ob_start();
        ?>
        <form method="POST">
            <?php wp_nonce_field('cap_login_action', 'cap_login_nonce'); ?>
            <label for="email">Email:</label>
            <input type="email" name="email" required>
            <br>
            <label for="password">Password:</label>
            <input type="password" name="password" required>
            <br>
            <button type="submit">Login</button>
        </form>
        <?php
        return ob_get_clean();
    }
}
