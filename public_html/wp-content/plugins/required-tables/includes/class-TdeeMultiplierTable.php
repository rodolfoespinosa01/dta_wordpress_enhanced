<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class TdeeMultiplierTable {

    // Method to create the tdee_multipliers table
    public static function create_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'tdee_multipliers';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id BIGINT(20) UNSIGNED NOT NULL,
            level VARCHAR(10) NOT NULL,
            day INT NOT NULL,
            workout_day DECIMAL(5,4) NOT NULL,
            off_day DECIMAL(5,4) DEFAULT NULL,
            FOREIGN KEY (admin_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
