<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class MacroSettingsTable {

    // Method to create the macros_settings table
    public static function create_table() {
        global $wpdb;

        // Define the table name
        $table_name = $wpdb->prefix . 'macros_settings';

        // Define the charset and collation for the table
        $charset_collate = $wpdb->get_charset_collate();

        // SQL query to create the macros_settings table
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id BIGINT(20) UNSIGNED NOT NULL,
            approach VARCHAR(20) NOT NULL,
            goal VARCHAR(20) NOT NULL,
            variation VARCHAR(20) DEFAULT NULL,
            calorie_percentage DECIMAL(4,2) NOT NULL,
            protein_per_lb DECIMAL(4,2) NOT NULL,
            carbs_leftover DECIMAL(4,2) NOT NULL,
            fats_leftover DECIMAL(4,2) NOT NULL,
            FOREIGN KEY (admin_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
