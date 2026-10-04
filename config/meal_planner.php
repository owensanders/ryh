<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Meal Plan Guards
    |--------------------------------------------------------------------------
    |
    | Every AI meal plan is a paid API call, so these keep spend bounded on
    | top of the one-plan-per-household-per-week allowance. The daily cap is
    | app-wide; the pause kicks in when the provider reports no credit left.
    |
    */

    'daily_cap' => (int) env('AI_MEAL_PLAN_DAILY_CAP', 50),

    'max_output_tokens' => 8000,

    'pause_hours' => 6,

    // Where to send the "out of AI credit" alert. Null logs it only.
    'alert_email' => env('AI_ALERT_EMAIL'),

];
