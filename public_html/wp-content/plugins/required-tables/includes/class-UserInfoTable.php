<?php
if (!defined('ABSPATH')) {
    exit;
}

class UserInfoTable {

    // Method to create the user_info table
    public static function create_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'user_info';
        $charset_collate = $wpdb->get_charset_collate();

        // SQL query to create the table
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            admin_id BIGINT(20) UNSIGNED NOT NULL,
            age INT(3) NOT NULL,
            gender VARCHAR(10) NOT NULL,
            weight_kg DECIMAL(10,6) NOT NULL,
            weight_lbs DECIMAL(10,6) NOT NULL,
            height_cm DECIMAL(10,6) DEFAULT 0,
            height_ft_in DECIMAL(10,6) DEFAULT 0,
            activity_level VARCHAR(10) NOT NULL,
            goal VARCHAR(20) NOT NULL,
            meal_plan_type VARCHAR(20) NOT NULL DEFAULT 'standard',
            meal_data JSON NOT NULL,
            training_days_per_week INT(1),
            bmr DECIMAL(10,6),
            workout_day_tdee DECIMAL(10,6),
            off_day_tdee DECIMAL(10,6),
            
            -- Handle Carb Cycling Schedule
            carbCycling_data JSON,
             
            -- Calculated fields based on user meal type and macro admin settings
            calories_workoutDay DECIMAL(10,6),
            calories_offDay DECIMAL(10,6),
        
            -- Standard and keto protein intake
            protein_intake DECIMAL(10,6),
            workout_carbs DECIMAL(10,6),
            workout_fats DECIMAL(10,6),
            off_day_carbs DECIMAL(10,6),
            off_day_fats DECIMAL(10,6),
        
            -- Separate fields for carb cycling
            protein_intake_lowCarb DECIMAL(10,6),
            protein_intake_highCarb DECIMAL(10,6),
            workout_carbs_lowCarb DECIMAL(10,6),
            workout_carbs_highCarb DECIMAL(10,6),
            workout_fats_lowCarb DECIMAL(10,6),
            workout_fats_highCarb DECIMAL(10,6),
            off_day_carbs_lowCarb DECIMAL(10,6),
            off_day_carbs_highCarb DECIMAL(10,6),
            off_day_fats_lowCarb DECIMAL(10,6),
            off_day_fats_highCarb DECIMAL(10,6),
        
            PRIMARY KEY (id),
            FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE,
            FOREIGN KEY (admin_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE
        ) $charset_collate;";

        // Include the necessary WordPress function for table creation
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
