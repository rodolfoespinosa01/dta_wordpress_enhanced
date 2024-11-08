<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class DailyMacros {

    // Initialize Daily Macros
    public static function init() {
        // Register shortcode for Daily Macros
        add_shortcode('user_daily_macros', [__CLASS__, 'display_daily_macros']);
    }

    // Display the daily macros data
    public static function display_daily_macros() {
        if (!is_user_logged_in()) {
            return '<p>You must be logged in to view your daily macros.</p>';
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
        $user_meta = get_user_meta($user_id);
        
        ob_start();
        ?>
        <!-- Daily Macro Plan Section -->
            <section class="daily-macros" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
              
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
        <?php
        return ob_get_clean();
    }
}

// Initialize Daily Macros
DailyMacros::init();
