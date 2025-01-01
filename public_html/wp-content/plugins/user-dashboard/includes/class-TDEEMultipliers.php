<?php
if (!defined('ABSPATH')) {
    exit;
}

class TDEEMultipliers {

    /**
     * Fetch TDEE multipliers for a specific admin, activity level, and training days.
     *
     * @param int $admin_id The admin's ID.
     * @param string $activity_level The activity level ('low', 'mid', 'high').
     * @param int $training_days The number of training days per week.
     * @return object|null The TDEE multipliers for workout and off days, or null if not found.
     */
    public static function get_tdee_multiplier($admin_id, $activity_level, $training_days) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'tdee_multipliers';

        // Query to fetch the TDEE multipliers
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT workout_day, off_day 
                 FROM $table_name 
                 WHERE admin_id = %d AND level = %s AND day = %d",
                $admin_id,
                $activity_level,
                $training_days
            )
        );

        return $result; // Returns null if no result is found
    }
}
