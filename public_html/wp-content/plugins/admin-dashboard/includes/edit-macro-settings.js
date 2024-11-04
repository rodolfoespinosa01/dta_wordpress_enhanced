jQuery(document).ready(function ($) {
    $('#macro-settings-form').on('submit', function (e) {
        e.preventDefault();

        const data = {
            action: 'save_macro_settings',
            nonce: macroSettings.nonce,
            settings: $(this).serialize()
        };

        $.post(macroSettings.ajax_url, data, function (response) {
            if (response.success) {
                alert('Settings saved successfully.');
            } else {
                alert('Error saving settings.');
            }
        });
    });
});
