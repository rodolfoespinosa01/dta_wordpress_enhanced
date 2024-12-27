<?php
if (!defined('ABSPATH')) {
    exit;
}

class MacrosSettings {

    /**
     * Fetch macros settings for a specific admin and user configuration.
     *
     * @param int $admin_id The admin ID.
     * @param string $goal The user's goal (e.g., 'lose_weight', 'maintain_weight', 'gain_weight').
     * @param string $approach The meal plan approach (e.g., 'standard', 'carbCycling').
     * @param string|null $variation The carb cycling variation ('lowCarb', 'highCarb'), or null for others.
     * @return object|null The row from the macros settings table, or null if not found.
     */
    public static function get_macros_settings($admin_id, $goal, $approach, $variation = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'macros_settings';

        // Base query for macros settings
        $query = "SELECT * FROM $table_name WHERE admin_id = %d AND goal = %s AND approach = %s";

        // Add variation conditionally
        if ($approach === 'carbCycling' && $variation) {
            $query .= " AND variation = %s";
            $sql = $wpdb->prepare($query, $admin_id, $goal, $approach, $variation);
        } else {
            $query .= " AND variation IS NULL";
            $sql = $wpdb->prepare($query, $admin_id, $goal, $approach);
        }

        // Fetch the result
        return $wpdb->get_row($sql);
    }
}
