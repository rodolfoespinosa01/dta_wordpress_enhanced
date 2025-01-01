<?php
function create_user_meal_combos_table() {
    global $wpdb;

    $table_name = "{$wpdb->prefix}user_meal_combos";
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        day_of_week VARCHAR(10) NOT NULL,
        meal_number INT(2) NOT NULL,
        meal_combo_id INT(11) NOT NULL,
        PRIMARY KEY (id),
        FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'create_user_meal_combos_table');
