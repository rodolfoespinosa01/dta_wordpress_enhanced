<?php
// Default macro settings array
return [
   // Standard approach
    ['approach' => 'standard', 'goal' => 'lose_weight', 'variation' => NULL, 'calorie_percentage' => 0.85, 'protein_per_lb' => 1.0, 'carbs_leftover' => 0.6, 'fats_leftover' => 0.4],
    ['approach' => 'standard', 'goal' => 'maintain_weight', 'variation' => NULL, 'calorie_percentage' => 1.0, 'protein_per_lb' => 0.8, 'carbs_leftover' => 0.5, 'fats_leftover' => 0.5],
    ['approach' => 'standard', 'goal' => 'gain_weight', 'variation' => NULL, 'calorie_percentage' => 1.2, 'protein_per_lb' => 1.2, 'carbs_leftover' => 0.6, 'fats_leftover' => 0.4],

    // Carb Cycling approach
    ['approach' => 'carbCycling', 'goal' => 'lose_weight', 'variation' => 'lowCarb', 'calorie_percentage' => 0.85, 'protein_per_lb' => 1.0, 'carbs_leftover' => 0.2, 'fats_leftover' => 0.8],
    ['approach' => 'carbCycling', 'goal' => 'lose_weight', 'variation' => 'highCarb', 'calorie_percentage' => 0.85, 'protein_per_lb' => 1.0, 'carbs_leftover' => 0.8, 'fats_leftover' => 0.2],
    ['approach' => 'carbCycling', 'goal' => 'maintain_weight', 'variation' => 'lowCarb', 'calorie_percentage' => 1.0, 'protein_per_lb' => 0.85, 'carbs_leftover' => 0.2, 'fats_leftover' => 0.8],
    ['approach' => 'carbCycling', 'goal' => 'maintain_weight', 'variation' => 'highCarb', 'calorie_percentage' => 1.0, 'protein_per_lb' => 0.85, 'carbs_leftover' => 0.8, 'fats_leftover' => 0.2],
    ['approach' => 'carbCycling', 'goal' => 'gain_weight', 'variation' => 'lowCarb', 'calorie_percentage' => 1.2, 'protein_per_lb' => 0.75, 'carbs_leftover' => 0.2, 'fats_leftover' => 0.8],
    ['approach' => 'carbCycling', 'goal' => 'gain_weight', 'variation' => 'highCarb', 'calorie_percentage' => 1.2, 'protein_per_lb' => 0.75, 'carbs_leftover' => 0.8, 'fats_leftover' => 0.2],

    // Keto approach
    ['approach' => 'keto', 'goal' => 'lose_weight', 'variation' => NULL, 'calorie_percentage' => 0.85, 'protein_per_lb' => 1.0, 'carbs_leftover' => 0.2, 'fats_leftover' => 0.8],
    ['approach' => 'keto', 'goal' => 'maintain_weight', 'variation' => NULL, 'calorie_percentage' => 1.0, 'protein_per_lb' => 1.0, 'carbs_leftover' => 0.2, 'fats_leftover' => 0.8],
    ['approach' => 'keto', 'goal' => 'gain_weight', 'variation' => NULL, 'calorie_percentage' => 1.2, 'protein_per_lb' => 1.0, 'carbs_leftover' => 0.2, 'fats_leftover' => 0.8],
];
