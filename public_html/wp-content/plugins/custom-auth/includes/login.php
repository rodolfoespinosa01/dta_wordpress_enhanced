<?php
// Shortcode for Admin Login Form
function cap_auth_admin_login_shortcode() {
    // Check if user is already logged in
    if (is_user_logged_in()) {
        return '<p>You are already logged in.</p>';
    }

    // Form processing
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['password'])) {
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
            wp_logout(); // Log out the user immediately if they aren't Admin
            return '<p>Access denied: This login form is for Admins only.</p>';
        }

        // Redirect to Admin Dashboard if login is successful and user role is valid
        wp_safe_redirect(site_url('/admin-dashboard'));
        exit;
    }

    // Display the login form
    ob_start();
    ?>
    <form method="POST">
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
add_shortcode('cap_auth_admin_login', 'cap_auth_admin_login_shortcode');


// Shortcode for User Login Form
function cap_auth_user_login_shortcode() {
    // Check if user is already logged in
    if (is_user_logged_in()) {
        return '<p>You are already logged in.</p>';
    }

    // Form processing
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['password'])) {
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
            wp_logout(); // Log out the user immediately if they aren't a User
            return '<p>Access denied: This login form is for Users only.</p>';
        }

        // Redirect to User Dashboard if login is successful and user role is valid
        wp_safe_redirect(site_url('/user-dashboard'));
        exit;
    }

    // Display the login form
    ob_start();
    ?>
    <form method="POST">
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
add_shortcode('cap_auth_user_login', 'cap_auth_user_login_shortcode');
