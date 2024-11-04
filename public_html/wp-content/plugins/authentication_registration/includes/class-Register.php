<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Register {

    // Initialize the Register class
    public static function init() {
        add_shortcode('cap_auth_register_admin', [__CLASS__, 'admin_register_form']);
        add_shortcode('cap_auth_register_user', [__CLASS__, 'user_register_form']);
    }

    // Admin registration form shortcode handler
    public static function admin_register_form() {
        // Check if user is already logged in
        if (is_user_logged_in()) {
            return '<p>You are already registered and logged in.</p>';
        }

        // Check for a valid admin token in the URL
        if (!isset($_GET['admin_token']) || $_GET['admin_token'] !== '1') {
            wp_safe_redirect(home_url());
            exit;
        }

        // Form processing
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['password'])) {
            // Verify nonce for CSRF protection
            if (!isset($_POST['cap_register_nonce']) || !wp_verify_nonce($_POST['cap_register_nonce'], 'cap_register_action')) {
                return '<p>Nonce verification failed. Please try again.</p>';
            }

            $email = sanitize_email($_POST['email']);
            $password = sanitize_text_field($_POST['password']);

            // Basic validation
            if (!is_email($email) || empty($password) || strlen($password) < 8) {
                return '<p>Invalid email or password (must be at least 8 characters).</p>';
            }

            // Check if email is already registered
            if (email_exists($email)) {
                return '<p>Email is already registered.</p>';
            }

            // Register new Admin user
            $user_id = wp_create_user($email, $password, $email);
            if (is_wp_error($user_id)) {
                return '<p>Registration failed: ' . esc_html($user_id->get_error_message()) . '</p>';
            }

            // Assign 'admin' role
            $user = get_user_by('ID', $user_id);
            $user->set_role('admin');
            
            // Log the user in
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);

            // Redirect to Admin Dashboard
            wp_safe_redirect(site_url('/admin-dashboard'));
            exit;
        }

        // Display the registration form with a nonce
        ob_start();
        ?>
        <form method="POST">
    <?php wp_nonce_field('cap_register_action', 'cap_register_nonce'); ?>
    <label for="email_field">Email:</label>
    <input type="email" id="email_field" name="email" required>
    <br>
    <label for="password_field">Password:</label>
    <input type="password" id="password_field" name="password" required>
    <br>
    <button type="submit">Register</button>
</form>
        <?php
        return ob_get_clean();
    }

    // User registration form shortcode handler
    public static function user_register_form() {
        // Check if user is already logged in
        if (is_user_logged_in()) {
            return '<p>You are already registered and logged in.</p>';
        }
    
        // Check for a valid `admin_id` in the URL
        $admin_id = isset($_GET['admin_id']) ? intval($_GET['admin_id']) : null;
        if (!$admin_id || !user_can($admin_id, 'create_users')) {
            return '<p>Registration is only allowed through a valid admin link.</p>';
        }
    
        // Form processing
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['password'])) {
            // Verify nonce for CSRF protection
            if (!isset($_POST['cap_register_nonce']) || !wp_verify_nonce($_POST['cap_register_nonce'], 'cap_register_action')) {
                return '<p>Nonce verification failed. Please try again.</p>';
            }
    
            // Capture form data
            $email = sanitize_email($_POST['email']);
            $password = sanitize_text_field($_POST['password']);
            $age = intval($_POST['age']);
            $gender = sanitize_text_field($_POST['gender']);
            $activity_level = sanitize_text_field($_POST['activity_level']);
            $goal = sanitize_text_field($_POST['goal']);
            $weight = floatval($_POST['weight']);
            $weight_unit = sanitize_text_field($_POST['weight_unit']);
            $height_unit = sanitize_text_field($_POST['height_unit']);
            $meal_plan_type = sanitize_text_field($_POST['meal_plan_type']);
    
            // Convert weight to kg if needed
            $weight_kg = ($weight_unit === 'lbs') ? $weight * 0.453592 : $weight;
            $weight_lbs = ($weight_unit === 'kg') ? $weight * 2.20462 : $weight;
    
            // Convert height based on the selected unit
            if ($height_unit === 'imperial') {
                $height_ft = intval($_POST['height_ft']);
                $height_in = floatval($_POST['height_in']);
                $height_cm = ($height_ft * 30.48) + ($height_in * 2.54);
            } else {
                $height_cm = floatval($_POST['height_cm']);
            }
    
            // Register new User
            $user_id = wp_create_user($email, $password, $email);
            if (is_wp_error($user_id)) {
                return '<p>Registration failed: ' . esc_html($user_id->get_error_message()) . '</p>';
            }
    
            // Assign 'user' role and link to the admin
            $user = get_user_by('ID', $user_id);
            $user->set_role('user');
            update_user_meta($user_id, 'associated_admin', $admin_id);
    
            // Insert additional user info into wp_user_info
            global $wpdb;
            $table_name = $wpdb->prefix . 'user_info';
    
            $result = $wpdb->insert(
                $table_name,
                array(
                    'user_id' => $user_id,
                    'admin_id' => $admin_id,
                    'age' => $age,
                    'gender' => $gender,
                    'weight_kg' => number_format($weight_kg, 6, '.', ''),
                    'weight_lbs' => number_format($weight_lbs, 6, '.', ''),
                    'height_cm' => number_format($height_cm, 6, '.', ''),
                    'activity_level' => $activity_level,
                    'goal' => $goal,
                    'meal_plan_type' => $meal_plan_type
                )
            );
    
            if ($result === false) {
                echo '<p>Error: Could not save the data. Please try again.</p>';
                return;
            }
    
            // Log the user in and redirect
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);
    
            // Redirect to User Dashboard
            wp_safe_redirect(site_url('/user-dashboard'));
            exit;
        }
    
        // Display the registration form with a nonce
        ob_start();
        ?>
        <form method="post">
        <?php wp_nonce_field('cap_register_action', 'cap_register_nonce'); ?>
            <!-- Email -->
            <label for="email">Email:</label>
            <input type="email" name="email" required><br>
        
            <!-- Password -->
            <label for="password">Password:</label><br>
            <input type="password" id="password" name="password" required><br><br>
        
            <!-- Age -->
            <label for="age">Age:</label><br>
            <input type="number" id="age" name="age" required><br><br>
        
            <!-- Gender -->
            <label for="gender">Gender:</label><br>
            <select id="gender" name="gender" required>
                <option value="female">Female</option>
                <option value="male">Male</option>
            </select><br><br>
        
            <!-- Activity Level -->
            <label for="activity_level">Activity Level:</label><br>
            <select id="activity_level" name="activity_level" required>
                <option value="low">Low</option>
                <option value="mid">Mid</option>
                <option value="high">High</option>
            </select><br><br>
        
            <!-- Goal -->
            <label for="goal">What is your goal?</label><br>
            <select id="goal" name="goal" required>
                <option value="lose_weight">Lose Weight</option>
                <option value="maintain_weight">Maintain Weight</option>
                <option value="gain_weight">Gain Weight</option>
            </select><br><br>
        
            <!-- Weight -->
            <label for="weight">Weight:</label><br>
            <input type="number" id="weight" name="weight" step="0.0001" required><br>
            <label for="weight_unit">Unit:</label><br>
            <select id="weight_unit" name="weight_unit" required>
                <option value="kg">Kilograms (kg)</option>
                <option value="lbs">Pounds (lbs)</option>
            </select><br><br>
        
            <!-- Height Unit -->
            <label for="height_unit">Height Unit:</label><br>
            <select id="height_unit" name="height_unit" required onchange="toggleHeightInput(this.value)">
                <option value="imperial">Feet & Inches</option>
                <option value="metric">Centimeters</option>
            </select><br><br>
        
            <!-- Imperial Height Inputs -->
            <div id="imperial_height" style="display: block;">
                <label for="height_ft">Feet:</label><br>
                <input type="number" id="height_ft" name="height_ft" min="0" value="5"><br><br>
                <label for="height_in">Inches:</label><br>
                <input type="number" id="height_in" name="height_in" step="0.01" min="0" max="11" value="0"><br><br>
            </div>
        
            <!-- Metric Height Input -->
            <div id="metric_height" style="display: none;">
                <label for="height_cm">Centimeters:</label><br>
                <input type="number" id="height_cm" name="height_cm" step="0.01" min="0"><br><br>
            </div>
        
            <!-- Meal Plan Type -->
            <label for="meal_plan_type">Type of Meal Plan:</label><br>
            <select id="meal_plan_type" name="meal_plan_type" required onchange="toggleCarbCyclingOptions(this.value)">
                <option value="standard" selected>Standard</option>
                <option value="carbCycling">Carb Cycling</option>
                <option value="keto">Keto</option>
            </select><br><br>
        
            <!-- Carb Cycling Schedule (hidden by default) -->
            <div id="carbCycling_options" style="display: none;">
                <h3>Select Carb Cycling Schedule</h3>
                <label for="carb_cycle_template">Choose a Predefined Template:</label><br>
                <select id="carb_cycle_template" name="carb_cycle_template" onchange="applyCarbCyclingTemplate(this.value)">
                    <option value="">Select a template</option>
                    <option value="option1">Option 1: Sun-l, Mon-l, Tue-h, Wed-l, Thur-h, Fri-l, Sat-h</option>
                    <option value="option2">Option 2: Sun-h, Mon-h, Tue-l, Wed-l, Thur-h, Fri-l, Sat-h</option>
                    <option value="option3">Option 3: Sun-h, Mon-l, Tue-l, Wed-l, Thur-l, Fri-h, Sat-h</option>
                    <option value="option4">Option 4: Sun-h, Mon-l, Tue-h, Wed-l, Thur-h, Fri-l, Sat-h</option>
                    <option value="custom">Custom Schedule</option>
                </select><br><br>
        
                <!-- Custom Carb Cycling Schedule (only shown if 'Custom' is selected) -->
                <div id="custom_carbCycling_schedule" style="display: none;">
                    <h4>Custom Carb Cycling Schedule</h4>
                    <?php foreach (['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'] as $day) : ?>
                        <label for="<?php echo $day; ?>_carb_type"><?php echo ucfirst($day); ?>:</label><br>
                        <select id="<?php echo $day; ?>_carb_type" name="carbCycling_schedule[<?php echo $day; ?>]">
                            <option value="high">High Carb</option>
                            <option value="low">Low Carb</option>
                        </select><br><br>
                    <?php endforeach; ?>
                </div>
            </div>
        
            <!-- Meal Plan and Training Schedule -->
            <h3>Meal Plan and Training Schedule</h3>
        
            <label for="default_meals">Default Meals Per Day:</label><br>
            <select id="default_meals" name="default_meals" onchange="applyDefaultMeals(this.value)">
                <option value="custom">Customize Each Day</option>
                <option value="3">3 Meals a Day</option>
                <option value="4">4 Meals a Day</option>
                <option value="5">5 Meals a Day</option>
            </select><br><br>
        
            <!-- Default Training Time Option -->
            <label for="default_training_time">Default Training Time:</label><br>
            <select id="default_training_time" name="default_training_time" onchange="applyDefaultTrainingTime(this.value)">
                <option value="none">None</option>
                <option value="before_meal_1">Before Meal 1</option>
                <option value="before_meal_2">Before Meal 2</option>
                <option value="before_meal_3">Before Meal 3</option>
                <option value="before_meal_4">Before Meal 4</option>
                <option value="before_meal_5">Before Meal 5</option>
            </select><br><br>
        
            <?php 
            $days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
            foreach ($days as $day) { 
                $day_lower = strtolower($day);
            ?>
                <h4><?php echo ucfirst($day); ?></h4>
                <label for="meals_<?php echo $day_lower; ?>">Number of Meals:</label><br>
                <input type="number" id="meals_<?php echo $day_lower; ?>" name="meals_<?php echo $day_lower; ?>" min="1" max="5" value="3"><br><br>
        
                <label for="training_<?php echo $day_lower; ?>">Training Time:</label><br>
                <select id="training_<?php echo $day_lower; ?>" name="training_<?php echo $day_lower; ?>">
                    <option value="none">None</option>
                    <option value="before_meal_1">Before Meal 1</option>
                    <option value="before_meal_2">Before Meal 2</option>
                    <option value="before_meal_3">Before Meal 3</option>
                    <option value="before_meal_4">Before Meal 4</option>
                    <option value="before_meal_5">Before Meal 5</option>
                </select><br><br>
            <?php } ?>
        
            <!-- Submit Button -->
            <input type="submit" name="register_submit" value="Register">
</form>

<!-- JS for toggling and updating form sections -->
<script>
function toggleHeightInput(unit) {
    if (unit === 'imperial') {
        document.getElementById('imperial_height').style.display = 'block';
        document.getElementById('metric_height').style.display = 'none';
    } else {
        document.getElementById('imperial_height').style.display = 'none';
        document.getElementById('metric_height').style.display = 'block';
    }
}

function toggleCarbCyclingOptions(value) {
    if (value === 'carbCycling') {
        document.getElementById('carbCycling_options').style.display = 'block';
    } else {
        document.getElementById('carbCycling_options').style.display = 'none';
    }
}

function applyCarbCyclingTemplate(template) {
    if (template === 'custom') {
        document.getElementById('custom_carbCycling_schedule').style.display = 'block';
    } else {
        document.getElementById('custom_carbCycling_schedule').style.display = 'none';
    }
}

function applyDefaultMeals(value) {
    var days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    for (var i = 0; i < days.length; i++) {
        document.getElementById('meals_' + days[i]).value = value !== 'custom' ? value : '3';
    }
}

function applyDefaultTrainingTime(value) {
    var days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    for (var i = 0; i < days.length; i++) {
        document.getElementById('training_' + days[i]).value = value;
    }
}
</script>
        <?php
        return ob_get_clean();
    }
}
