<?php
if (!defined('ABSPATH')) {
    exit;
}

class SundayAutomation {

    public static function init() {
        add_shortcode('sunday_automation', [__CLASS__, 'run_sunday_automation']);
        add_action('wp_ajax_run_sunday_automation', [__CLASS__, 'ajax_run_sunday_automation']);
    }

    // Trigger automation on page load
    public static function run_sunday_automation() {
        // Trigger automation directly when the page loads
        $result = self::execute_automation();

        ob_start();
        ?>
        <div id="automation-status" class="status">
            <?php echo esc_html($result['message']); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    // Handle AJAX-based automation execution
    public static function ajax_run_sunday_automation() {
        check_ajax_referer('magic_nonce', 'nonce');

        // Run the automation process
        $result = self::execute_automation();

        if ($result['success']) {
            wp_send_json_success(['message' => $result['message']]);
        } else {
            wp_send_json_error(['message' => $result['message']]);
        }
    }

    // Executes the automation workflow
    private static function execute_automation() {
        global $wpdb;

        try {
            // Execute Step 1
            $step1_result = Step1::run('sunday');
            if (!$step1_result['success']) {
                return [
                    'success' => false,
                    'message' => 'Step 1 failed: ' . $step1_result['message'],
                ];
            }

            // Execute Step 2
            $step2_result = Step2::run('sunday');
            if (!$step2_result['success']) {
                return [
                    'success' => false,
                    'message' => 'Step 2 failed: ' . $step2_result['message'],
                ];
            }

            // Execute Step 3
            $step3_result = Step3::run('sunday');
            if (!$step3_result['success']) {
                return [
                    'success' => false,
                    'message' => 'Step 3 failed: ' . $step3_result['message'],
                ];
            }

            // Final success message
            return [
                'success' => true,
                'message' => 'Sunday Automation completed successfully. All steps processed for all meals.',
            ];

        } catch (Exception $e) {
            // Catch and log any errors
            error_log('Automation Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred during automation: ' . $e->getMessage(),
            ];
        }
    }
}

// Initialize the SundayAutomation class
SundayAutomation::init();
