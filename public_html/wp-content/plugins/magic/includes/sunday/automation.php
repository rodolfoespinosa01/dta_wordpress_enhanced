<?php
if (!defined('ABSPATH')) {
    exit;
}

// Include dependencies
require_once plugin_dir_path(__FILE__) . '/class-step1.php';
require_once plugin_dir_path(__FILE__) . '/class-step2.php';

class SundayAutomation {

    public static function init() {
        // Register the shortcode
        add_shortcode('sunday_automation', [__CLASS__, 'run_sunday_automation']);

        // Register AJAX actions for authenticated users
        add_action('wp_ajax_run_sunday_automation', [__CLASS__, 'ajax_run_sunday_automation']);
    }

    // Shortcode: Trigger the automation process when the page loads
    public static function run_sunday_automation() {
        // Include a div for status updates
        return '<div id="automation-status" class="status">Starting Sunday Automation...</div>
                <script>
                    jQuery(document).ready(function ($) {
                        $.ajax({
                            url: "' . admin_url('admin-ajax.php') . '",
                            method: "POST",
                            data: {
                                action: "run_sunday_automation",
                                nonce: "' . wp_create_nonce('magic_nonce') . '"
                            },
                            success: function (response) {
                                if (response.success) {
                                    $("#automation-status").html(response.data.message).addClass("success");
                                } else {
                                    $("#automation-status").html(response.data.message).addClass("error");
                                }
                            },
                            error: function () {
                                $("#automation-status").html("An error occurred while running the automation.").addClass("error");
                            }
                        });
                    });
                </script>';
    }

    // AJAX handler for automation
    public static function ajax_run_sunday_automation() {
        // Verify the AJAX request
        check_ajax_referer('magic_nonce', 'nonce');

        // Execute automation
        $result = self::execute_automation();

        if ($result['success']) {
            wp_send_json_success(['message' => $result['message']]);
        } else {
            wp_send_json_error(['message' => $result['message']]);
        }
    }

    // Execute Step1 and Step2 for Sunday
    private static function execute_automation() {
        try {
            // Run Step 1
            $step1_result = Step1::run('sunday');
            if (!$step1_result['success']) {
                error_log('Step 1 failed: ' . $step1_result['message']);
                return ['success' => false, 'message' => 'Step 1 failed: ' . $step1_result['message']];
            }

            // Run Step 2
            $step2_result = Step2::run('sunday');
            if (!$step2_result['success']) {
                error_log('Step 2 failed: ' . $step2_result['message']);
                return ['success' => false, 'message' => 'Step 2 failed: ' . $step2_result['message']];
            }

            return ['success' => true, 'message' => 'Sunday Automation Completed: Step 1 and Step 2 executed successfully.'];
        } catch (Exception $e) {
            error_log('Automation error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()];
        }
    }
}

// Initialize the SundayAutomation class
SundayAutomation::init();
