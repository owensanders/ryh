<?php

declare(strict_types=1);

/*
 * Which routes the browser's route() helper knows (Ziggy), published in every
 * page's HTML by @routes (resources/views/app.blade.php). Everything the app
 * links to, but never Telescope, Stripe's webhook or the file routes. One
 * list for everyone: signing in is an Inertia visit, not a page load, so a
 * signed-out list would still be the one in use straight after.
 */
return [
    'except' => ['telescope*', 'cashier.*', 'storage.*'],
];
