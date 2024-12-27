<?php
if (!defined('ABSPATH')) {
    exit;
}

class MealSettings {

    /**
     * Fetch meal settings for a specific admin and user configuration.
     *
     * @param int $admin_id The admin ID.
     * @param string $approach The meal plan approach (e.g., 'standard', 'carbCycling').
     * @param int $day_type The type of day (e.g., 0 for day 1, 1 for day 2).
     * @param string|null $carb_type The carb cycling type ('lowCarb', 'highCarb'), or null for standard plans.
     * @return array|null An array of meal settings for the given configuration, or null if not found.
     */
    public static function get_meal_settings($admin_id, $approach, $day_type, $carb_type = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'meal_settings';

        // Base query
        $query = "SELECT * FROM $table_name WHERE admin_id = %d AND approach = %s AND day_type = %d";

        // Add carb type conditionally
        if ($carb_type) {
            $query .= " AND carb_type = %s";
            $sql = $wpdb->prepare($query, $admin_id, $approach, $day_type, $carb_type);
        } else {
            $query .= " AND carb_type IS NULL";
            $sql = $wpdb->prepare($query, $admin_id, $approach, $day_type);
        }

        // Fetch results
        return $wpdb->get_results($sql, ARRAY_A);
    }
}

