jQuery(document).ready(function ($) {
    const userId = $('#init_defaults').data('user-id');
    if (userId) {
        $.ajax({
            url: dashboardInit.ajax_url,
            type: 'POST',
            data: {
                action: 'initialize_defaults',
                nonce: dashboardInit.nonce
            },
            success: function (response) {
                if (response.success) {
                    console.log('Default settings initialized:', response.data);
                }
            },
            error: function (error) {
                console.error('Failed to initialize settings:', error);
            }
        });
    }
});
