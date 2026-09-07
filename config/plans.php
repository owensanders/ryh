<?php

declare(strict_types=1);

return [

    'name' => 'Home',
    'intervals' => [
        'monthly' => ['price' => 3.99, 'stripe_price' => env('STRIPE_PRICE_HOME_MONTHLY')],
        'annual' => ['price' => 39.99, 'stripe_price' => env('STRIPE_PRICE_HOME_ANNUAL')],
    ],
    'trial_days' => 30,
    'sub' => 'Free for a month, then £3.99 a month (or £39.99 a year). One subscription covers everyone in the house.',
    'cta' => 'Start your free month',
    'note' => 'Cancel anytime. No card needed to start.',
    'features' => [
        'Meal planner and recipe library',
        'Shared household calendar',
        'Unlimited shopping lists',
        'Chores for everyone',
        'Unlimited household members',
        'Household budget',
        'Document vault with 20GB storage',
        'AI meal planning, a week at a time',
        'Family roles to manage access',
        'Multiple houses under one login',
    ],

];
