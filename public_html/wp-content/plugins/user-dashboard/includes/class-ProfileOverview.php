<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ProfileOverview {

    // Initialize Profile Overview
    public static function init() {
        // Register shortcode for Profile Overview
        add_shortcode('user_profile_overview', [__CLASS__, 'display_profile_overview']);
    }

    // Display the profile overview data
    public static function display_profile_overview() {
        if (!is_user_logged_in()) {
            return '<p>You must be logged in to view your profile overview.</p>';
        }

        // Fetch current user ID and data
        $user_id = get_current_user_id();
        global $wpdb;
        $user = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}user_info WHERE user_id = %d", 
            $user_id
        ));

        // Handle errors if user data is not found
        if (!$user) {
            return '<p>Error: Unable to load your data.</p>';
        }
        
        // Decode JSON data for meals and carb cycling
        $meal_data = json_decode($user->meal_data, true);
        $carbCycling_data = json_decode($user->carbCycling_data, true);

        // Define days of the week
        $days_of_week = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        // Build the profile overview content
        ob_start();
        ?>
        <section style="border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td><strong>Age:</strong></td>
                <td><?php echo esc_html($user->age); ?></td>
            </tr>
            <tr>
                <td><strong>Gender:</strong></td>
                <td><?php echo esc_html($user->gender); ?></td>
            </tr>
            <tr>
                <td><strong>Weight (kg):</strong></td>
                <td><?php echo esc_html(number_format($user->weight_kg, 4)); ?> kg</td>
            </tr>
            <tr>
                <td><strong>Weight (lbs):</strong></td>
                <td><?php echo esc_html(number_format($user->weight_lbs, 4)); ?> lbs</td>
            </tr>
            <tr>
                <td><strong>Height (cm):</strong></td>
                <td><?php echo esc_html(number_format($user->height_cm, 2)); ?> cm</td>
            </tr>
            <tr>
                <td><strong>Height (ft/in):</strong></td>
                <td>
                    <?php 
                    $feet = floor($user->height_ft_in / 12);
                    $inches = $user->height_ft_in % 12;
                    echo esc_html($feet . "' " . $inches . '"'); 
                    ?>
                </td>
            </tr>
            <tr>
                <td><strong>Activity Level:</strong></td>
                <td><?php echo esc_html($user->activity_level); ?></td>
            </tr>
            <tr>
                <td><strong>Goal:</strong></td>
                <td><?php echo esc_html($user->goal); ?></td>
            </tr>
            <tr>
                <td><strong>Meal Plan Type:</strong></td>
                <td><?php echo esc_html($user->meal_plan_type); ?></td>
            </tr>
        </table>
    </section>

    <!-- Carb Cycling Section -->
            <?php if ($user->meal_plan_type === 'carbCycling'): ?>
                <section class="carb-cycling-schedule">
                    <h3>Your Carb Cycling Schedule</h3>
                    <table>
                        <thead><tr><th>Day</th><th>Carb Type</th></tr></thead>
                        <tbody>
                            <?php foreach ($days_of_week as $day): ?>
                                <tr>
                                    <td><?php echo esc_html($day); ?></td>
                                    <td><?php echo esc_html(ucfirst($carbCycling_data[strtolower($day)] ?? 'No Data')); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>
            <?php endif; ?>
        <?php
        return ob_get_clean();
    }
}
