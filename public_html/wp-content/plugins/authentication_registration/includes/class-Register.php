<?php
require_once plugin_dir_path(__FILE__) . 'calculations.php';
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
        $birth_date = sanitize_text_field($_POST['birth_date']); // New birth date field

        // Validate birth date
        if (!$birth_date || !strtotime($birth_date)) {
            return '<p>Invalid birth date. Please select a valid date.</p>';
        }

        // Calculate the user's age
        $age = self::calculate_age($birth_date);
        if ($age < 18) { // Check if the user is under 18
            return '<p>You must be at least 18 years old to register.</p>';
        }

        // Additional form data
        $gender = sanitize_text_field($_POST['gender']);
        $activity_level = sanitize_text_field($_POST['activity_level']);
        $goal = sanitize_text_field($_POST['goal']);
        $meal_plan_type = sanitize_text_field($_POST['meal_plan_type']);
        $weight = floatval($_POST['weight']);
        $weight_unit = sanitize_text_field($_POST['weight_unit']);

        // Convert weight to kg if needed
        $weight_kg = ($weight_unit === 'lbs') ? $weight * 0.453592 : $weight;
        $weight_lbs = ($weight_unit === 'kg') ? $weight * 2.20462 : $weight;

           // Height conversion logic
        $height_unit = sanitize_text_field($_POST['height_unit']);
        if ($height_unit === 'imperial') {
            // Default to 5 feet and 0 inches if not explicitly set
            $height_ft = isset($_POST['height_ft']) ? intval($_POST['height_ft']) : 5;
            $height_in = isset($_POST['height_in']) ? floatval($_POST['height_in']) : 0;
            $height_cm = ($height_ft * 30.48) + ($height_in * 2.54);  // Convert feet/inches to cm
            $height_ft_in = ($height_ft * 12) + $height_in;  // Convert total feet/inches to inches
        } else {
            // Initialize the metric height value
            $height_cm = isset($_POST['height_cm']) ? floatval($_POST['height_cm']) : 0;
            $height_ft_in = $height_cm / 2.54;  // Convert cm to inches
            $height_ft = 0; // Set $height_ft and $height_in to default values for clarity
            $height_in = 0;
        }
        
        // Define default training time
        $default_training_time = isset($_POST['default_training_time']) ? sanitize_text_field($_POST['default_training_time']) : 'none';
        
        // Initialize meal data array
        $meal_data = [];
        $days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        
        // Capture the meal plan and training schedule for each day
        foreach ($days as $day) {
            $day_lower = strtolower($day);
            $meals = isset($_POST["meals_$day_lower"]) ? intval($_POST["meals_$day_lower"]) : 3;  // Default to 3 meals if not provided
            $training_time = isset($_POST["training_$day_lower"]) ? sanitize_text_field($_POST["training_$day_lower"]) : $default_training_time;
        
            // Structure data for each day within meal_data
            $meal_data[$day_lower] = [
                'meals' => $meals,
                'training_time' => $training_time
            ];
        }
        
        $training_days_per_week = calculate_training_days($meal_data);
        // Calculate BMR
        $bmr = calculate_bmr($gender, $weight_kg, $height_cm, $age);
        
        // Assuming $admin_id, $activity_level, and $training_days_per_week are already defined
        $tdee_values = fetch_tdee_multipliers($admin_id, $activity_level, $training_days_per_week);
        
        $workout_day_tdee = $tdee_values['workout_day_tdee'];
        $off_day_tdee = $tdee_values['off_day_tdee'];

        // Convert meal_data to JSON format for database storage
        $meal_data_json = json_encode($meal_data);
        
        // Check if the meal plan is 'carbCycling'
        if ($meal_plan_type === 'carbCycling') {
            // Process carb cycling data
            $carb_cycle_template = sanitize_text_field($_POST['carb_cycle_template']);
            $carbCycling_data = [];
        
            if ($carb_cycle_template === 'custom') {
                // Custom carb cycling schedule selected, save custom days
                $days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
                foreach ($days as $day) {
                    $carbCycling_data[$day] = sanitize_text_field($_POST["carbCycling_schedule"][$day]);
                }
            } else {
                // Predefined template, map to carb cycling data using 'highCarb' and 'lowCarb'
                switch ($carb_cycle_template) {
                    case 'option1':
                        $carbCycling_data = [
                            'sunday' => 'lowCarb', 'monday' => 'lowCarb', 'tuesday' => 'highCarb',
                            'wednesday' => 'lowCarb', 'thursday' => 'highCarb', 'friday' => 'lowCarb', 'saturday' => 'highCarb'
                        ];
                        break;
                    case 'option2':
                        $carbCycling_data = [
                            'sunday' => 'highCarb', 'monday' => 'highCarb', 'tuesday' => 'lowCarb',
                            'wednesday' => 'lowCarb', 'thursday' => 'highCarb', 'friday' => 'lowCarb', 'saturday' => 'highCarb'
                        ];
                        break;
                    case 'option3':
                        $carbCycling_data = [
                            'sunday' => 'highCarb', 'monday' => 'lowCarb', 'tuesday' => 'lowCarb',
                            'wednesday' => 'lowCarb', 'thursday' => 'lowCarb', 'friday' => 'highCarb', 'saturday' => 'highCarb'
                        ];
                        break;
                    case 'option4':
                        $carbCycling_data = [
                            'sunday' => 'highCarb', 'monday' => 'lowCarb', 'tuesday' => 'highCarb',
                            'wednesday' => 'lowCarb', 'thursday' => 'highCarb', 'friday' => 'lowCarb', 'saturday' => 'highCarb'
                        ];
                        break;
                }
            }
        } else {
            // Not carb cycling, set carb cycling data as NULL
            $carbCycling_data = null;
        }
        
        // Encode carbCycling_data into JSON format for storage
        $carbCycling_data_json = json_encode($carbCycling_data);


        // Register new User
        $user_id = wp_create_user($email, $password, $email);
        if (is_wp_error($user_id)) {
            return '<p>Registration failed: ' . esc_html($user_id->get_error_message()) . '</p>';
        }

        // Assign 'user' role and link to the admin
        $user = get_user_by('ID', $user_id);
        $user->set_role('user');
        update_user_meta($user_id, 'associated_admin', $admin_id);
        
         $birth_date = get_user_meta($user_id, 'birth_date', true);

            if ($birth_date) {
                $age = Register::calculate_age($birth_date);
            }
        
        // Calculate calories using the updated function
        $calories = calculate_calories($bmr, $workout_day_tdee, $off_day_tdee, $meal_plan_type, $goal, $admin_id);
        $calories_workoutDay = $calories['calories_workoutDay'];
        $calories_offDay = $calories['calories_offDay'];
        
         // Assume $weight_lbs, $meal_plan_type, $goal, $admin_id, $calories_workoutDay, and $calories_offDay are already defined

// First, calculate protein intake based on meal plan type
if ($meal_plan_type === 'carbCycling') {
    // Calculate protein intake for high-carb and low-carb days
    $protein_intake_highCarb = calculate_protein($weight_lbs, $meal_plan_type, $goal, $admin_id, 'highCarb');
    $protein_intake_lowCarb = calculate_protein($weight_lbs, $meal_plan_type, $goal, $admin_id, 'lowCarb');
} else {
    // For standard and keto plans
    $protein_intake = calculate_protein($weight_lbs, $meal_plan_type, $goal, $admin_id);
}

// Now, calculate carbs and fats
if ($meal_plan_type === 'carbCycling') {
    // High-Carb Day for Workout
    $workout_macros_highCarb = calculate_carbs_fats($calories_workoutDay, $protein_intake_highCarb, $meal_plan_type, $goal, $admin_id, 'highCarb');
    $workout_carbs_highCarb = $workout_macros_highCarb['carbs_grams'];
    $workout_fats_highCarb = $workout_macros_highCarb['fats_grams'];

    // Low-Carb Day for Workout
    $workout_macros_lowCarb = calculate_carbs_fats($calories_workoutDay, $protein_intake_lowCarb, $meal_plan_type, $goal, $admin_id, 'lowCarb');
    $workout_carbs_lowCarb = $workout_macros_lowCarb['carbs_grams'];
    $workout_fats_lowCarb = $workout_macros_lowCarb['fats_grams'];

    // High-Carb Day for Off Days
    $off_day_macros_highCarb = calculate_carbs_fats($calories_offDay, $protein_intake_highCarb, $meal_plan_type, $goal, $admin_id, 'highCarb');
    $off_day_carbs_highCarb = $off_day_macros_highCarb['carbs_grams'];
    $off_day_fats_highCarb = $off_day_macros_highCarb['fats_grams'];

    // Low-Carb Day for Off Days
    $off_day_macros_lowCarb = calculate_carbs_fats($calories_offDay, $protein_intake_lowCarb, $meal_plan_type, $goal, $admin_id, 'lowCarb');
    $off_day_carbs_lowCarb = $off_day_macros_lowCarb['carbs_grams'];
    $off_day_fats_lowCarb = $off_day_macros_lowCarb['fats_grams'];
} else {
    // For standard and keto plans
    // Workout Day Macros
    $workout_macros = calculate_carbs_fats($calories_workoutDay, $protein_intake, $meal_plan_type, $goal, $admin_id);
    $workout_carbs_grams = $workout_macros['carbs_grams'];
    $workout_fats_grams = $workout_macros['fats_grams'];

    // Off Day Macros
    $off_day_macros = calculate_carbs_fats($calories_offDay, $protein_intake, $meal_plan_type, $goal, $admin_id);
    $off_day_carbs_grams = $off_day_macros['carbs_grams'];
    $off_day_fats_grams = $off_day_macros['fats_grams'];
}



        // Insert additional user info into wp_user_info
        global $wpdb;
        $table_name = $wpdb->prefix . 'user_info';

        // Insert user data into the database, including calories
if ($meal_plan_type === 'carbCycling') {
    // For carb cycling, include high/low carb values
    $result = $wpdb->insert(
        $table_name,
        array(
            'user_id' => $user_id,
            'admin_id' => $admin_id,
            'age' => $age,
            'gender' => $gender,
            'weight_kg' => $weight_kg,
            'weight_lbs' => $weight_lbs,
            'height_cm' => $height_cm,
            'height_ft_in' => $height_ft_in,
            'activity_level' => $activity_level,
            'goal' => $goal,
            'meal_plan_type' => $meal_plan_type,
            'meal_data' => $meal_data_json, // Store as JSON
            'training_days_per_week' => $training_days_per_week,
            'bmr' => number_format((float)$bmr, 6, '.', ''),
            'workout_day_tdee' => number_format((float)$workout_day_tdee, 6, '.', ''),
            'off_day_tdee' => number_format((float)$off_day_tdee, 6, '.', ''),
            'calories_workoutDay' => number_format((float)$calories_workoutDay, 2, '.', ''),
            'calories_offDay' => number_format((float)$calories_offDay, 2, '.', ''),
            'carbCycling_data' => $carbCycling_data_json,
            'protein_intake' => null,  // Set protein_intake to NULL for carb cycling
            'workout_carbs' => null,
            'workout_fats' => null,
            'off_day_carbs' => null,
            'off_day_fats' => null,
            'protein_intake_highCarb' => number_format((float)$protein_intake_highCarb, 6, '.', ''),
            'protein_intake_lowCarb' => number_format((float)$protein_intake_lowCarb, 6, '.', ''),
            'workout_carbs_highCarb' => number_format((float)$workout_carbs_highCarb, 6, '.', ''),
            'workout_carbs_lowCarb' => number_format((float)$workout_carbs_lowCarb, 6, '.', ''),
            'workout_fats_highCarb' => number_format((float)$workout_fats_highCarb, 6, '.', ''),
            'workout_fats_lowCarb' => number_format((float)$workout_fats_lowCarb, 6, '.', ''),
            'off_day_carbs_highCarb' => number_format((float)$off_day_carbs_highCarb, 6, '.', ''),
            'off_day_carbs_lowCarb' => number_format((float)$off_day_carbs_lowCarb, 6, '.', ''),
            'off_day_fats_highCarb' => number_format((float)$off_day_fats_highCarb, 6, '.', ''),
            'off_day_fats_lowCarb' => number_format((float)$off_day_fats_lowCarb, 6, '.', '')
        )
    );
} else {
    // For non-carb cycling meal plans
    $result = $wpdb->insert(
        $table_name,
        array(
            'user_id' => $user_id,
            'admin_id' => $admin_id,
            'age' => $age,
            'gender' => $gender,
            'weight_kg' => $weight_kg,
            'weight_lbs' => $weight_lbs,
            'height_cm' => $height_cm,
            'height_ft_in' => $height_ft_in,
            'activity_level' => $activity_level,
            'goal' => $goal,
            'meal_plan_type' => $meal_plan_type,
            'meal_data' => $meal_data_json, // Store as JSON
            'training_days_per_week' => $training_days_per_week,
            'bmr' => number_format((float)$bmr, 6, '.', ''),
            'workout_day_tdee' => number_format((float)$workout_day_tdee, 6, '.', ''),
            'off_day_tdee' => number_format((float)$off_day_tdee, 6, '.', ''),
            'calories_workoutDay' => number_format((float)$calories_workoutDay, 2, '.', ''),
            'calories_offDay' => number_format((float)$calories_offDay, 2, '.', ''),
            'carbCycling_data' => null,
            'protein_intake' => number_format((float)$protein_intake, 6, '.', ''),
            'workout_carbs' => number_format((float)$workout_carbs_grams, 6, '.', ''),
            'workout_fats' => number_format((float)$workout_fats_grams, 6, '.', ''),
            'off_day_carbs' => number_format((float)$off_day_carbs_grams, 6, '.', ''),
            'off_day_fats' => number_format((float)$off_day_fats_grams, 6, '.', ''),
            
            // Set carb cycling fields to NULL
            'protein_intake_highCarb' => null,
            'protein_intake_lowCarb' => null,
            'workout_carbs_highCarb' => null,
            'workout_carbs_lowCarb' => null,
            'workout_fats_highCarb' => null,
            'workout_fats_lowCarb' => null,
            'off_day_carbs_highCarb' => null,
            'off_day_carbs_lowCarb' => null,
            'off_day_fats_highCarb' => null,
            'off_day_fats_lowCarb' => null,
            
             // Set carb cycling fields to NULL
            'protein_intake_highCarb' => null,
            'protein_intake_lowCarb' => null
        )
    );
}
        
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
            <!-- Username -->
            <label for="email">Email:</label>
            <input type="email" name="email" required><br>
        
            <!-- Password -->
            <label for="password">Password:</label><br>
            <input type="password" id="password" name="password" required><br><br>
        
                      <!-- Birth Date -->
            <label for="birth_date">Birth Date:</label><br>
            <input type="date" id="birth_date" name="birth_date" required><br><br>

        
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
                            <option value="highCarb">High Carb</option>
                            <option value="lowCarb">Low Carb</option>
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
    
    // Helper method to calculate age
    private static function calculate_age($birth_date) {
        $birthDate = new DateTime($birth_date);
        $today = new DateTime("today");
        return $birthDate->diff($today)->y;
    }
}
