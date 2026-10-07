<?php

declare(strict_types=1);

// Everyone in the house counts, including pending invites and join requests.
$maxMembers = 8;

return [

    'name' => 'Home',
    'intervals' => [
        'monthly' => ['price' => 3.99, 'stripe_price' => env('STRIPE_PRICE_HOME_MONTHLY')],
        'annual' => ['price' => 39.99, 'stripe_price' => env('STRIPE_PRICE_HOME_ANNUAL')],
    ],
    'trial_days' => 30,
    'max_members' => $maxMembers,
    // Billing is per household (Household is the Cashier billable): one flat
    // subscription covers everyone in it, and each extra household pays its own.
    'sub' => 'Free for a month, then £3.99 a month (or £39.99 a year) per household. One subscription covers everyone in the house.',
    'cta' => 'Start your free month',
    // The free month is once per person (users.trial_claimed_at), not per household.
    'note' => 'Cancel anytime. No card needed to start. One free month per person.',
    'features' => [
        'Meal planner and recipe library',
        'AI meal planning, a week at a time',
        'Shared household calendar',
        'Unlimited shopping lists',
        'Chores with one-tap reminders',
        'Live in-app notifications',
        'Household budget',
        'Document vault with 20GB storage',
        "Up to {$maxMembers} household members",
        'Family roles to manage access',
        'Invite by email or join code',
        'Everyone in the house on one subscription',
    ],

];
