<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ListClients {

    public static function init() {
        add_shortcode('list_clients', [__CLASS__, 'display_client_list']);
    }

    public static function display_client_list() {
        $admin_id = get_current_user_id();
        if (!self::is_admin_user($admin_id)) {
            return '<p>You do not have permission to view this page.</p>';
        }

        $clients = self::get_clients_by_admin($admin_id);
        ob_start();
        echo '<h2>Client List</h2>';

        if (!empty($clients)) {
            echo '<ul>';
            foreach ($clients as $client) {
                echo '<li>' . esc_html($client->display_name) . ' (' . esc_html($client->user_email) . ')</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>No clients found.</p>';
        }

        return ob_get_clean();
    }

    private static function get_clients_by_admin($admin_id) {
        $args = [
            'meta_key'    => 'associated_admin',
            'meta_value'  => $admin_id,
            'role'        => 'user',
        ];

        return get_users($args);
    }

    private static function is_admin_user($user_id) {
        $user = get_userdata($user_id);
        return in_array('admin', (array) $user->roles) || in_array('master_admin', (array) $user->roles);
    }
}

ListClients::init();
