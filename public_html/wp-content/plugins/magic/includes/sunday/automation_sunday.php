<?php
if (!defined('ABSPATH')) {
    exit;
}

class AutomationSunday {

    public static function init() {
        // Shortcode for the Sunday start button (Page 1)
        add_shortcode('start_sunday_automation_button', [__CLASS__, 'render_start_button']);
        
        // Shortcode for the Sunday automation progress bar (Page 2)
        add_shortcode('sunday_automation', [__CLASS__, 'render_automation_sunday']);
        
        add_shortcode('sunday_timer', [__CLASS__, 'render_timer']);

        // AJAX handler for running steps
        add_action('wp_ajax_run_sunday_step', [__CLASS__, 'run_sunday_step']);
        add_action('wp_ajax_nopriv_run_sunday_step', [__CLASS__, 'run_sunday_step']); // For non-logged-in users if needed
    }
    
    public static function render_timer() {
    ob_start();
    ?>
    <div style="text-align: center; margin: 50px;">
        <h2>Countdown Timer</h2>
        <p id="timer-container" style="font-size: 20px; font-weight: bold;">
            Time Remaining: <span id="timer-countdown">90</span> seconds
        </p>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let timeRemaining = 90; // 90 seconds countdown
            const timerDisplay = document.getElementById('timer-countdown');
            const timerContainer = document.getElementById('timer-container');

            // Timer countdown logic
            const timerInterval = setInterval(function () {
                timeRemaining--;
                timerDisplay.textContent = timeRemaining;

                // Stop the timer when it reaches zero
                if (timeRemaining <= 0) {
                    clearInterval(timerInterval);
                    timerDisplay.textContent = 0;
                }
            }, 1000); // Update every second

            // Listen for a custom event that indicates completion
            document.addEventListener('automationComplete', function () {
                clearInterval(timerInterval); // Stop the timer
                timerContainer.style.display = 'none'; // Hide the timer
            });
        });
    </script>
    <?php
    return ob_get_clean();
}


    // Page 1: Render the Start Button
    public static function render_start_button() {
        ob_start();
        ?>
        <div style="text-align: center; margin: 50px;">
            <h2>Start Sunday Automation</h2>
            <p>Click the button below to start processing Sunday's meal plan.</p>
            <a href="<?php echo esc_url(home_url('/sunday-automation?start=1')); ?>" 
               style="padding: 10px 20px; background-color: #0073aa; color: #fff; text-decoration: none; border-radius: 5px; font-size: 16px;">
                Start Sunday Automation
            </a>
        </div>
        <?php
        return ob_get_clean();
    }

    // Page 2: Render the Automation Progress Bar
    public static function render_automation_sunday() {
        ob_start();
        ?>
        <div style="text-align: center; margin: 50px;">
            <h2>Processing Sunday Meal Plan</h2>
            <div style="position: relative; width: 100%; max-width: 600px; margin: 20px auto; background-color: #e0e0e0; border-radius: 5px; overflow: hidden;">
                <div id="progress-fill" style="width: 0; height: 20px; background-color: #0073aa;"></div>
            </div>
            <div id="automation-status"></div> <!-- Container for the button -->
        </div>
        <?php
        return ob_get_clean();
    }

    // Handle AJAX requests for each step
    public static function run_sunday_step() {
        if (!isset($_POST['step']) || !is_numeric($_POST['step'])) {
            wp_send_json_error(['message' => 'Invalid step.']);
        }

        $step = intval($_POST['step']);
        $day = 'sunday';

        // Define the mapping of step numbers to classes
        $steps = [
            1 => 'Step1',
            2 => 'Step2',
            3 => 'Step3',
            4 => 'Step4',
            5 => 'Step5',
            6 => 'Step6',
            7 => 'Step7',
            8 => 'Step8',
            9 => 'Step9',
            10 => 'Step10',
        ];

        if (!isset($steps[$step])) {
            error_log("Automation Error: Invalid step number - $step.");
            wp_send_json_error(['message' => 'Invalid step number.']);
        }

        $class_name = $steps[$step];

        // Check if the class exists
        if (!class_exists($class_name)) {
            error_log("Automation Error: Class $class_name does not exist.");
            wp_send_json_error(['message' => "Class $class_name does not exist."]);
        }

        // Check if the class has a `run` method
        if (!method_exists($class_name, 'run')) {
            error_log("Automation Error: Method 'run' not found in $class_name.");
            wp_send_json_error(['message' => "Method 'run' not found in $class_name."]);
        }

        // Execute the `run` method
        $result = call_user_func([$class_name, 'run'], $day);

        if ($result['success']) {
            if ($step === count($steps)) { // Check if it's the final step
                wp_send_json_success([
                    'progress' => 100, // 100% progress
                    'final' => true,   // Indicate the final step is complete
                    'redirect_url' => home_url('/user-dashboard/meal-plan-details/sunday/sunday-detailed/'),
                ]);
            } else {
                wp_send_json_success(['progress' => $step * 10]); // Each step contributes 10% to the progress
            }
        } else {
            error_log("Automation Error in $class_name: " . $result['message']);
            wp_send_json_error(['message' => $result['message']]);
        }
    }
}



// Initialize the class
AutomationSunday::init();
