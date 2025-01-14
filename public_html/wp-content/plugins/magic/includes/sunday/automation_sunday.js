jQuery(document).ready(function ($) {
    let currentStep = 1; // Start at step 1
    const totalSteps = 10; // Total number of steps
    const $progressFill = $("#progress-fill"); // Progress bar
    const $statusContainer = $("#automation-status"); // Status container

    function processStep(step) {
        $.ajax({
            url: ajaxParams.ajaxUrl, // AJAX URL
            type: "POST",
            data: {
                action: "run_sunday_step",
                step: step,
            },
            success: function (response) {
                if (response.success) {
                    const progress = response.data.progress;
                    $progressFill.css("width", progress + "%");

                    // Check if automation is complete
                    if (response.data.final) {
                        // Trigger the custom 'automationComplete' event
                        const event = new Event('automationComplete');
                        document.dispatchEvent(event);

                        $statusContainer.html(`
                            <div style="text-align: center; margin-top: 20px;">
                                <a href="${response.data.redirect_url}" 
                                   class="btn btn-primary" 
                                   style="padding: 10px 20px; background-color: #0073aa; color: #fff; text-decoration: none; border-radius: 5px; font-size: 16px;">
                                    View Detailed Meal Plan
                                </a>
                            </div>
                        `);
                    } else {
                        processStep(step + 1); // Continue to the next step
                    }
                } else {
                    console.error("Error:", response.data.message);
                }
            },
            error: function (xhr, status, error) {
                console.error("AJAX Error:", error);
            },
        });
    }

    // Start automation process if triggered via URL (?start=1)
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get("start") === "1") {
        processStep(currentStep);
    } else {
        console.log("Automation not triggered. Add '?start=1' to the URL to start the process.");
    }
});
