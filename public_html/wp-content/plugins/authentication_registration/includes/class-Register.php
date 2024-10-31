<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Register {

    // Initialize the Register class
    public static function init() {
        add_shortcode('cap_auth_register_admin', [__CLASS__, 'admin_register_form']);
        add_shortcode('cap_auth_register_user', [__CLASS__, 'user_register_form']);
    }

    // Admin registration form shortcode handler
    public static function admin_register_form() {
        // Check if user is already logged in
        if (is_user_logged_in()) {
            return '<p>You are already registered and logged in.</p>';
        }

        // Check for a valid admin token in the URL
        if (!isset($_GET['admin_token']) || $_GET['admin_token'] !== '1') {
            wp_safe_redirect(home_url());
            exit;
        }

        // Form processing
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['password'])) {
            // Verify nonce for CSRF protection
            if (!isset($_POST['cap_register_nonce']) || !wp_verify_nonce($_POST['cap_register_nonce'], 'cap_register_action')) {
                return '<p>Nonce verification failed. Please try again.</p>';
            }

            $email = sanitize_email($_POST['email']);
            $password = sanitize_text_field($_POST['password']);

            // Basic validation
            if (!is_email($email) || empty($password) || strlen($password) < 8) {
                return '<p>Invalid email or password (must be at least 8 characters).</p>';
            }

            // Check if email is already registered
            if (email_exists($email)) {
                return '<p>Email is already registered.</p>';
            }

            // Register new Admin user
            $user_id = wp_create_user($email, $password, $email);
            if (is_wp_error($user_id)) {
                return '<p>Registration failed: ' . esc_html($user_id->get_error_message()) . '</p>';
            }

            // Assign 'admin' role
            $user = get_user_by('ID', $user_id);
            $user->set_role('admin');

            // Log the user in
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);

            // Redirect to Admin Dashboard
            wp_safe_redirect(site_url('/admin-dashboard'));
            exit;
        }

        // Display the registration form with a nonce
        ob_start();
        ?>
        <form method="POST">
            <?php wp_nonce_field('cap_register_action', 'cap_register_nonce'); ?>
            <label for="email">Email:</label>
            <input type="email" name="email" required>
            <br>
            <label for="password">Password:</label>
            <input type="password" name="password" required>
            <br>
            <button type="submit">Register</button>
        </form>
        <?php
        return ob_get_clean();
    }

    // User registration form shortcode handler
    public static function user_register_form() {
        // Check if user is already logged in
        if (is_user_logged_in()) {
            return '<p>You are already registered and logged in.</p>';
        }

        // Check for a valid `admin_id` in the URL
        $admin_id = isset($_GET['admin_id']) ? intval($_GET['admin_id']) : null;
        if (!$admin_id || !user_can($admin_id, 'create_users')) {
            return '<p>Registration is only allowed through a valid admin link.</p>';
        }

        // Form processing
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['password'])) {
            // Verify nonce for CSRF protection
            if (!isset($_POST['cap_register_nonce']) || !wp_verify_nonce($_POST['cap_register_nonce'], 'cap_register_action')) {
                return '<p>Nonce verification failed. Please try again.</p>';
            }

            $email = sanitize_email($_POST['email']);
            $password = sanitize_text_field($_POST['password']);

            // Basic validation
            if (!is_email($email) || empty($password) || strlen($password) < 8) {
                return '<p>Invalid email or password (must be at least 8 characters).</p>';
            }

            // Check if email is already registered
            if (email_exists($email)) {
                return '<p>Email is already registered.</p>';
            }

            // Register new User
            $user_id = wp_create_user($email, $password, $email);
            if (is_wp_error($user_id)) {
                return '<p>Registration failed: ' . esc_html($user_id->get_error_message()) . '</p>';
            }

            // Assign 'user' role and link to the admin
            $user = get_user_by('ID', $user_id);
            $user->set_role('user');
            update_user_meta($user_id, 'associated_admin', $admin_id);

            // Log the user in
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);

            // Redirect to User Dashboard
            wp_safe_redirect(site_url('/user-dashboard'));
            exit;
        }

        // Display the registration form with a nonce
        ob_start();
        ?>
        <form method="POST">
            <?php wp_nonce_field('cap_register_action', 'cap_register_nonce'); ?>
            <label for="email">Email:</label>
            <input type="email" name="email" required>
            <br>
            <label for="password">Password:</label>
            <input type="password" name="password" required>
            <br>
            <button type="submit">Register</button>
        </form>
        <?php
        return ob_get_clean();
    }
}
