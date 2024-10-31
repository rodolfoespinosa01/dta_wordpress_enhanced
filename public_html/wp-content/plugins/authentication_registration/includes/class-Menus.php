<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Menus {

    // Initialize menu-related hooks
    public static function init() {
        add_filter('wp_nav_menu_objects', [__CLASS__, 'dynamic_menu_items'], 10, 2);
    }

    // Modify menu items based on user roles and login status
    public static function dynamic_menu_items($items, $args) {
        // Only modify the primary menu; adjust as needed if you have other menus
        if ($args->theme_location == 'primary') {
            // Get the current user and their roles
            $user = wp_get_current_user();
            $is_logged_in = is_user_logged_in();
            $is_admin = in_array('admin', (array) $user->roles);
            $is_user = in_array('user', (array) $user->roles);
            $is_master_admin = in_array('master_admin', (array) $user->roles);

            foreach ($items as $key => $item) {
                // Hide "Admin Dashboard" menu item unless logged in as Admin
                if ($item->title == 'Admin Dashboard' && (!$is_logged_in || !$is_admin)) {
                    unset($items[$key]);
                }

                // Hide "User Dashboard" menu item unless logged in as User
                if ($item->title == 'User Dashboard' && (!$is_logged_in || !$is_user)) {
                    unset($items[$key]);
                }

                // Hide "Master Admin Dashboard" menu item unless logged in as Master Admin
                if ($item->title == 'Master Admin Dashboard' && (!$is_logged_in || !$is_master_admin)) {
                    unset($items[$key]);
                }

                // Hide "User Login" and "User Registration" if any user is logged in
                if (($item->title == 'User Login' || $item->title == 'User Registration') && $is_logged_in) {
                    unset($items[$key]);
                }
            }

            // Add a "Log Out" link if the user is logged in
            if ($is_logged_in) {
                $logout_url = wp_logout_url(home_url()); // Redirect to homepage after logout
                $logout_item = (object) [
                    'title' => 'Log Out',
                    'url' => $logout_url,
                    'menu_item_parent' => 0,
                    'ID' => 'logout',
                    'db_id' => 'logout',
                    'classes' => ['menu-item', 'menu-item-logout'],
                ];
                $items[] = $logout_item; // Add the logout item to the menu
            }
        }

        return $items;
    }
}
