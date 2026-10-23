<?php

declare(strict_types=1);

/*
 * What a page tells link previews and search engines, sent in the HTML itself
 * (resources/views/app.blade.php): WhatsApp, iMessage, Slack and most crawlers
 * never run the JavaScript that sets the title. Keyed by route name; a page
 * that isn't listed (the app behind sign-in) gets the defaults. Titles match
 * the page's own <Head>, so nothing changes when the app takes over.
 */
return [
    'description' => 'One place for the whole household: chores, meals and shopping, the calendar, the budget and important documents. Free for 30 days.',

    // 1920×1080, from the landing page's product tour.
    'image' => '/videos/ryh-promo-poster.jpg',

    'pages' => [
        'home' => ['title' => 'Your home, organised'],
        'login' => ['title' => 'Log in'],
        'register' => ['title' => 'Register'],
        'password.request' => ['title' => 'Forgot password'],
        'support' => ['title' => 'Support', 'description' => 'Help with RYH: getting started, billing, your data and how to reach us.'],
        'status' => ['title' => 'Status', 'description' => 'Whether RYH and the services it relies on are working right now.'],
        'privacy' => ['title' => 'Privacy Policy', 'description' => 'What RYH collects about your household, why, and your rights over it.'],
        'terms' => ['title' => 'Terms of Service', 'description' => 'The terms for using RYH, including billing and households with children.'],
        'cookies' => ['title' => 'Cookie Policy', 'description' => 'The cookies RYH uses and what each one is for.'],
    ],
];
