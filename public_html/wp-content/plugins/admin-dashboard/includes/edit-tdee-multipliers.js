jQuery(document).ready(function ($) {
    $('#tdee-multipliers-form').on('submit', function (e) {
        e.preventDefault();

        const data = {
            action: 'save_tdee_multipliers',
            nonce: tdeeMultipliers.nonce,
            multipliers: $(this).serialize()
        };

        $.post(tdeeMultipliers.ajax_url, data, function (response) {
            if (response.success) {
                alert('TDEE Multipliers saved successfully.');
            } else {
                alert('Error saving TDEE Multipliers.');
            }
        });
    });
});
