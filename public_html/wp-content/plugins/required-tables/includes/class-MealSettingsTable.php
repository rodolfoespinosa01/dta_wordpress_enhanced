<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class MealSettingsTable {

    // Create the meal_settings table
    public static function create_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'meal_settings';
        $charset_collate = $wpdb->get_charset_collate();

       $sql = "CREATE TABLE IF NOT EXISTS $table_name (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id BIGINT(20) UNSIGNED NOT NULL,
    approach VARCHAR(20) NOT NULL,
    meals_per_day INT NOT NULL,
    carb_type VARCHAR(20) DEFAULT NULL,
    day_type VARCHAR(20) NOT NULL,
    meal_number VARCHAR(20) NOT NULL,
    protein DECIMAL(8,4) NOT NULL,  
    carbs DECIMAL(8,4) NOT NULL,    
    fats DECIMAL(8,4) NOT NULL,    
    FOREIGN KEY (admin_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE
) $charset_collate;";


        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
