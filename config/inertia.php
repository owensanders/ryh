<?php

declare(strict_types=1);

// Everything else keeps the package's defaults (vendor/inertiajs/inertia-laravel/config/inertia.php).
return [
    // Pages render in the browser only; the HTML carries public pages' meta
    // tags (config/meta.php) instead. Left on, Inertia would try Vite's dev
    // server for a server render whenever `npm run dev` is running.
    'ssr' => [
        'enabled' => false,
    ],

    'pages' => [
        // This app keeps its pages in the lower-cased `pages/` directory the
        // Vue starter kit ships with, and writes them all in Vue.
        'paths' => [resource_path('js/pages')],
        'extensions' => ['vue'],
    ],

    'testing' => [
        // assertInertia() fails if the component file doesn't exist.
        'ensure_pages_exist' => true,
    ],
];
