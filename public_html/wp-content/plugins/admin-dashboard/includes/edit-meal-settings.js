jQuery(document).ready(function ($) {
    $('#meal-settings-form').on('submit', function (e) {
        e.preventDefault();

        const data = {
            action: 'save_meal_settings',
            nonce: mealSettings.nonce,
            settings: $(this).serialize()
        };

        $.post(mealSettings.ajax_url, data, function (response) {
            if (response.success) {
                alert('Settings saved successfully.');
            } else {
                alert('Error saving settings.');
            }
        });
    });
});
