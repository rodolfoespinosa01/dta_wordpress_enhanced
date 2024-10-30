<?php
// Shortcode for Admin Registration Form
function cap_auth_admin_register_shortcode() {
    // Check if user is already logged in
    if (is_user_logged_in()) {
        return '<p>You are already registered and logged in.</p>';
    }

    // Check if an admin link with a valid token is used (e.g., ?admin_token=1)
    if (!isset($_GET['admin_token']) || $_GET['admin_token'] !== '1') {
        // Redirect to homepage if token is missing or invalid
        wp_safe_redirect(home_url());
        exit;
    }

    // Form processing
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['password'])) {
        $email = sanitize_email($_POST['email']);
        $password = sanitize_text_field($_POST['password']);

        // Basic validation
        if (!is_email($email) || empty($password)) {
            return '<p>Invalid email or password.</p>';
        }

        // Check if email is already registered
        if (email_exists($email)) {
            return '<p>Email is already registered.</p>';
        }

        // Register new Admin
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

    // Display the registration form
    ob_start();
    ?>
    <form method="POST">
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
add_shortcode('cap_auth_register', 'cap_auth_admin_register_shortcode');

// Shortcode for User Registration Form
function cap_auth_user_register_shortcode() {
    // Check if user is already logged in
    if (is_user_logged_in()) {
        return '<p>You are already registered and logged in.</p>';
    }

    // Capture admin_id from the URL if available
    $admin_id = isset($_GET['admin_id']) ? intval($_GET['admin_id']) : null;

    // Form processing
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['password'])) {
        $email = sanitize_email($_POST['email']);
        $password = sanitize_text_field($_POST['password']);

        // Basic validation
        if (!is_email($email) || empty($password)) {
            return '<p>Invalid email or password.</p>';
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

        // Assign 'user' role
        $user = get_user_by('ID', $user_id);
        $user->set_role('user');

        // Attach user to the Master Admin if no admin_id is provided, otherwise attach to specified admin
        if ($admin_id) {
            // Link the user to a specific admin
            update_user_meta($user_id, 'associated_admin', $admin_id);
        } else {
            // Link the user to the Master Admin as "Unassigned"
            $master_admin_id = 1; // Replace this with your Master Admin ID if different
            update_user_meta($user_id, 'associated_admin', $master_admin_id);
            update_user_meta($user_id, 'status', 'Unassigned');
        }

        // Log the user in
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);

        // Redirect to User Dashboard
        wp_safe_redirect(site_url('/user-dashboard'));
        exit;
    }

    // Display the registration form
    ob_start();
    ?>
    <form method="POST">
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
add_shortcode('cap_auth_user_register', 'cap_auth_user_register_shortcode');
