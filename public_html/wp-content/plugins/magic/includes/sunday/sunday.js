jQuery(document).ready(function ($) {
    // Automatically run the Sunday automation on page load
    $(document).ready(function () {
        const statusContainer = $('#automation-status');

        // AJAX request to trigger automation
        $.ajax({
            url: magic_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'run_sunday_automation',
                nonce: magic_ajax.nonce,
            },
            success: function (response) {
                if (response.success) {
                    statusContainer.text(response.data.message).addClass('success');
                } else {
                    statusContainer.text(response.data.message).addClass('error');
                }
            },
            error: function () {
                statusContainer.text('An unexpected error occurred.').addClass('error');
            }
        });
    });
});
