<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class UserDashboard {

    // Initialize the UserDashboard class
    public static function init() {
        add_shortcode('ud_dashboard', [__CLASS__, 'display_dashboard']);
        add_action('plugins_loaded', [__CLASS__, 'start_session_for_dashboard']);
    }

    // Start session for dashboard
    public static function start_session_for_dashboard() {
        if (!session_id()) {
            session_start();
        }
    }

    // Method to handle the display of the User Dashboard content
    public static function display_dashboard() {
        // Check if the user is logged in using WordPress functions
        if (!is_user_logged_in()) {
            return '<p>You must be logged in to view this page.</p>';
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
        
       $user_email_row = $wpdb->get_row($wpdb->prepare(
            "SELECT user_email FROM {$wpdb->prefix}users WHERE ID = %d", // Use 'ID' instead of 'user_id'
            $user_id
        ));
        
        // Extract the email from the row object
        $user_email = $user_email_row ? $user_email_row->user_email : 'Unknown User';

        // Prepare the dashboard content
        ob_start();
        ?>
        <div class="user-dashboard">
            <h2>Welcome, <?php echo esc_html($user_email); ?>!</h2>

            <!-- Profile Section -->
            <section class="profile-overview">
                <h3>Your Profile Overview</h3>
                <table>
                    <tr><td>Age:</td><td><?php echo esc_html($user->age); ?></td></tr>
                    <tr><td>Gender:</td><td><?php echo esc_html($user->gender); ?></td></tr>
                    <tr><td>Weight (kg):</td><td><?php echo esc_html(number_format($user->weight_kg, 2)); ?> kg</td></tr>
                    <tr><td>Weight (lbs):</td><td><?php echo esc_html(number_format($user->weight_lbs, 2)); ?> lbs</td></tr>
                    <tr><td>Height (cm):</td><td><?php echo esc_html(number_format($user->height_cm, 2)); ?> cm</td></tr>
                    <tr><td>Activity Level:</td><td><?php echo esc_html($user->activity_level); ?></td></tr>
                    <tr><td>Goal:</td><td><?php echo esc_html($user->goal); ?></td></tr>
                    <tr><td>Meal Plan Type:</td><td><?php echo esc_html($user->meal_plan_type); ?></td></tr>
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

            <!-- Daily Macro Plan Section -->
            <section class="daily-macros" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
                <h3>Your Daily Macros</h3>
                <?php if ($user->meal_plan_type === 'carbCycling'): ?>
                    <!-- High-Carb Day Macros -->
                    <div style="border: 1px solid #ccc; padding: 10px; margin-bottom: 10px; border-radius: 5px;">
                        <h4>High-Carb Day</h4>
                        <p><strong>Workout Day TDEE:</strong> <?php echo esc_html(number_format($user->workout_day_tdee, 2)); ?> kcal</p>
                        <p><strong>Calories (Workout Day):</strong> <?php echo esc_html(number_format($user->calories_workoutDay, 2)); ?> kcal</p>
                        <p><strong>Protein:</strong> <?php echo esc_html(number_format($user->protein_intake_highCarb, 2)); ?> g</p>
                        <p><strong>Carbs:</strong> <?php echo esc_html(number_format($user->workout_carbs_highCarb, 2)); ?> g</p>
                        <p><strong>Fats:</strong> <?php echo esc_html(number_format($user->workout_fats_highCarb, 2)); ?> g</p>

                        <h4>Off Day</h4>
                        <p><strong>Off Day TDEE:</strong> <?php echo esc_html(number_format($user->off_day_tdee, 2)); ?> kcal</p>
                        <p><strong>Calories (Off Day):</strong> <?php echo esc_html(number_format($user->calories_offDay, 2)); ?> kcal</p>
                        <p><strong>Protein:</strong> <?php echo esc_html(number_format($user->protein_intake_highCarb, 2)); ?> g</p>
                        <p><strong>Carbs:</strong> <?php echo esc_html(number_format($user->off_day_carbs_highCarb, 2)); ?> g</p>
                        <p><strong>Fats:</strong> <?php echo esc_html(number_format($user->off_day_fats_highCarb, 2)); ?> g</p>
                    </div>

                    <!-- Low-Carb Day Macros -->
                    <div style="border: 1px solid #ccc; padding: 10px; margin-bottom: 10px; border-radius: 5px;">
                        <h4>Low-Carb Day</h4>
                        <p><strong>Workout Day TDEE:</strong> <?php echo esc_html(number_format($user->workout_day_tdee, 2)); ?> kcal</p>
                        <p><strong>Calories (Workout Day):</strong> <?php echo esc_html(number_format($user->calories_workoutDay, 2)); ?> kcal</p>
                        <p><strong>Protein:</strong> <?php echo esc_html(number_format($user->protein_intake_lowCarb, 2)); ?> g</p>
                        <p><strong>Carbs:</strong> <?php echo esc_html(number_format($user->workout_carbs_lowCarb, 2)); ?> g</p>
                        <p><strong>Fats:</strong> <?php echo esc_html(number_format($user->workout_fats_lowCarb, 2)); ?> g</p>

                        <h4>Off Day</h4>
                        <p><strong>Off Day TDEE:</strong> <?php echo esc_html(number_format($user->off_day_tdee, 2)); ?> kcal</p>
                        <p><strong>Calories (Off Day):</strong> <?php echo esc_html(number_format($user->calories_offDay, 2)); ?> kcal</p>
                        <p><strong>Protein:</strong> <?php echo esc_html(number_format($user->protein_intake_lowCarb, 2)); ?> g</p>
                        <p><strong>Carbs:</strong> <?php echo esc_html(number_format($user->off_day_carbs_lowCarb, 2)); ?> g</p>
                        <p><strong>Fats:</strong> <?php echo esc_html(number_format($user->off_day_fats_lowCarb, 2)); ?> g</p>
                    </div>
                <?php else: ?>
                    <!-- Standard/Keto Day Macros -->
                    <div style="border: 1px solid #ccc; padding: 10px; margin-bottom: 10px; border-radius: 5px;">
                        <h4>Workout Day</h4>
                        <p><strong>Workout Day TDEE:</strong> <?php echo esc_html(number_format($user->workout_day_tdee, 2)); ?> kcal</p>
                        <p><strong>Calories:</strong> <?php echo esc_html(number_format($user->calories_workoutDay, 2)); ?> kcal</p>
                        <p><strong>Protein:</strong> <?php echo esc_html(number_format($user->protein_intake, 2)); ?> g</p>
                        <p><strong>Carbs:</strong> <?php echo esc_html(number_format($user->workout_carbs, 2)); ?> g</p>
                        <p><strong>Fats:</strong> <?php echo esc_html(number_format($user->workout_fats, 2)); ?> g</p>
                    </div>

                    <div style="border: 1px solid #ccc; padding: 10px; margin-bottom: 10px; border-radius: 5px;">
                        <h4>Off Day</h4>
                        <p><strong>Off Day TDEE:</strong> <?php echo esc_html(number_format($user->off_day_tdee, 2)); ?> kcal</p>
                        <p><strong>Calories:</strong> <?php echo esc_html(number_format($user->calories_offDay, 2)); ?> kcal</p>
                        <p><strong>Protein:</strong> <?php echo esc_html(number_format($user->protein_intake, 2)); ?> g</p>
                        <p><strong>Carbs:</strong> <?php echo esc_html(number_format($user->off_day_carbs, 2)); ?> g</p>
                        <p><strong>Fats:</strong> <?php echo esc_html(number_format($user->off_day_fats, 2)); ?> g</p>
                    </div>
                <?php endif; ?>
            </section>
        </div>
        <?php
        return ob_get_clean();
    }
}
