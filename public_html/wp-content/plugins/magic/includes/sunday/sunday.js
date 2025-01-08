jQuery(document).ready(function ($) {
    // Automatically start the automation process when the page loads
    runSundayAutomation();

    function runSundayAutomation() {
        const statusDiv = $('#automation-status');

        // Clear any existing status message
        statusDiv.text('Starting automation...');

        // AJAX request to execute automation
        $.ajax({
            url: magicAjax.ajax_url, // URL for AJAX call
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'run_sunday_automation',
                nonce: magicAjax.nonce
            },
            success: function (response) {
                if (response.success) {
                    // Update status with success message
                    statusDiv.text(response.data.message);
                } else {
                    // Update status with error message
                    statusDiv.text('Error: ' + response.data.message);
                }
            },
            error: function (xhr, status, error) {
                // Handle AJAX errors
                statusDiv.text('An unexpected error occurred: ' + error);
                console.error('AJAX Error:', xhr, status, error);
            }
        });
    }
});
