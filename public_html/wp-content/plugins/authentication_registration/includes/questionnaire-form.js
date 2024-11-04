console.log("Questionnaire script loaded.");

// Ensure the DOM is fully loaded before attaching event listeners
document.addEventListener("DOMContentLoaded", function () {
    // Toggle height input fields based on selected unit (imperial or metric)
    window.toggleHeightInput = function (unit) {
        if (unit === 'imperial') {
            document.getElementById('imperial_height').style.display = 'block';
            document.getElementById('metric_height').style.display = 'none';
        } else {
            document.getElementById('imperial_height').style.display = 'none';
            document.getElementById('metric_height').style.display = 'block';
        }
    };

    // Show or hide carb cycling options based on meal plan type
    window.toggleCarbCyclingOptions = function (value) {
        const carbCyclingOptions = document.getElementById('carbCycling_options');
        if (carbCyclingOptions) {
            carbCyclingOptions.style.display = value === 'carbCycling' ? 'block' : 'none';
        }
    };

    // Show custom carb cycling schedule if 'Custom' template is selected
    window.applyCarbCyclingTemplate = function (template) {
        const customSchedule = document.getElementById('custom_carbCycling_schedule');
        if (customSchedule) {
            customSchedule.style.display = template === 'custom' ? 'block' : 'none';
        }
    };

    // Apply default meals for each day or allow customization
    window.applyDefaultMeals = function (value) {
        const days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        days.forEach(day => {
            const mealInput = document.getElementById('meals_' + day);
            if (mealInput) {
                mealInput.value = value !== 'custom' ? value : '3'; // Default to 3 if custom option isn't selected
            }
        });
    };

    // Apply default training time for each day
    window.applyDefaultTrainingTime = function (value) {
        const days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        days.forEach(day => {
            const trainingInput = document.getElementById('training_' + day);
            if (trainingInput) {
                trainingInput.value = value;
            }
        });
    };

    // Attach event listeners to the relevant form fields
    const heightUnitSelect = document.getElementById('height_unit');
    if (heightUnitSelect) {
        heightUnitSelect.addEventListener('change', function() {
            toggleHeightInput(this.value);
        });
    }

    const mealPlanTypeSelect = document.getElementById('meal_plan_type');
    if (mealPlanTypeSelect) {
        mealPlanTypeSelect.addEventListener('change', function() {
            toggleCarbCyclingOptions(this.value);
        });
    }

    const carbCycleTemplateSelect = document.getElementById('carb_cycle_template');
    if (carbCycleTemplateSelect) {
        carbCycleTemplateSelect.addEventListener('change', function() {
            applyCarbCyclingTemplate(this.value);
        });
    }

    const defaultMealsSelect = document.getElementById('default_meals');
    if (defaultMealsSelect) {
        defaultMealsSelect.addEventListener('change', function() {
            applyDefaultMeals(this.value);
        });
    }

    const defaultTrainingTimeSelect = document.getElementById('default_training_time');
    if (defaultTrainingTimeSelect) {
        defaultTrainingTimeSelect.addEventListener('change', function() {
            applyDefaultTrainingTime(this.value);
        });
    }
});
