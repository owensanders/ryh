<?php

declare(strict_types=1);

// Everything else keeps the package's defaults (vendor/inertiajs/inertia-laravel/config/inertia.php).
return [
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
