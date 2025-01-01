<?php
if (!defined('ABSPATH')) {
    exit;
}

class UserDashboardUtils {

    /**
     * Fetch the associated admin ID for a user.
     *
     * @param int $user_id The ID of the user.
     * @return int|null The admin ID, or null if not found.
     */
    public static function get_associated_admin($user_id) {
        // Fetch the admin ID from usermeta
        $admin_id = get_user_meta($user_id, 'associated_admin', true);

        // Return as integer or null if not found
        return $admin_id ? intval($admin_id) : null;
    }
}
