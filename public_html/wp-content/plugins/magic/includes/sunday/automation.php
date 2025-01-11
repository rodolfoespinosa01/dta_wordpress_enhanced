<?php
if (!defined('ABSPATH')) {
    exit;
}

class SundayAutomation {

    public static function init() {
        add_shortcode('sunday_automation', [__CLASS__, 'run_sunday_automation']);
        add_action('wp_ajax_run_sunday_automation', [__CLASS__, 'ajax_run_sunday_automation']);
    }

    // Shortcode for Sunday Automation
    public static function run_sunday_automation() {
        ob_start();
        ?>
        <div id="automation-container">
            <div id="automation-status" class="status">Initializing Sunday meal plan creation...</div>
            <div id="loading-bar-container">
                <div id="loading-bar"></div>
            </div>
        </div>
        <script>
            jQuery(document).ready(function ($) {
                // Automatically start the automation process
                runSundayAutomation();

                function runSundayAutomation() {
                    const statusDiv = $('#automation-status');
                    const loadingBar = $('#loading-bar');

                    // Update the status message
                    statusDiv.text('Starting Sunday meal plan creation...');
                    loadingBar.css({ width: '10%' });

                    // AJAX request to start automation
                    $.ajax({
                        url: magicAjax.ajax_url, // AJAX URL
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'run_sunday_automation',
                            nonce: magicAjax.nonce
                        },
                        success: function (response) {
                            if (response.success) {
                                // Update status and progress bar
                                statusDiv.text(response.data.message);
                                loadingBar.css({ width: '100%' });
                            } else {
                                statusDiv.text('Error: ' + response.data.message);
                                loadingBar.css({ width: '0%' });
                            }
                        },
                        error: function (xhr, status, error) {
                            statusDiv.text('An unexpected error occurred: ' + error);
                            loadingBar.css({ width: '0%' });
                            console.error('AJAX Error:', xhr, status, error);
                        }
                    });
                }
            });
        </script>
        <style>
            #loading-bar-container {
                width: 100%;
                background-color: #f3f3f3;
                border: 1px solid #ddd;
                border-radius: 5px;
                margin-top: 10px;
            }
            #loading-bar {
                width: 0;
                height: 10px;
                background-color: #4caf50;
                border-radius: 5px;
            }
        </style>
        <?php
        return ob_get_clean();
    }

    // AJAX handler for Sunday Automation
    public static function ajax_run_sunday_automation() {
        check_ajax_referer('magic_nonce', 'nonce');

        try {
            // Run Step 1
            $step1_result = Step1::run('sunday');
            if (!$step1_result['success']) {
                wp_send_json_error(['message' => 'Step 1 failed: ' . $step1_result['message']]);
            }

            // Run Step 2
            $step2_result = Step2::run('sunday');
            if (!$step2_result['success']) {
                wp_send_json_error(['message' => 'Step 2 failed: ' . $step2_result['message']]);
            }

            // Run Step 3
            $step3_result = Step3::run('sunday');
            if (!$step3_result['success']) {
                wp_send_json_error(['message' => 'Step 3 failed: ' . $step3_result['message']]);
            }

            // Run Step 4
            $step4_result = Step4::run('sunday');
            if (!$step4_result['success']) {
                wp_send_json_error(['message' => 'Step 4 failed: ' . $step4_result['message']]);
            }

            // Final success message
            wp_send_json_success(['message' => 'Sunday meal plan created successfully!']);
        } catch (Exception $e) {
            // Catch and handle errors
            wp_send_json_error(['message' => 'An error occurred during automation: ' . $e->getMessage()]);
        }
    }
}

// Initialize the SundayAutomation class
SundayAutomation::init();
