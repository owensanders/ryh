<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

        {{-- Installable app (PWA): see public/manifest.webmanifest and public/sw.js. --}}
        <link rel="manifest" href="/manifest.webmanifest">
        <link rel="icon" href="/favicon.ico" sizes="32x32">
        <link rel="icon" href="/icons/icon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
        <meta name="theme-color" content="#fcfaf7" media="(prefers-color-scheme: light)">
        <meta name="theme-color" content="#0e1119" media="(prefers-color-scheme: dark)">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="RYH">

        @php
            // Not $page: that's Inertia's page data, which @inertia renders.
            $meta = config('meta.pages.'.request()->route()?->getName(), []);
            $title = isset($meta['title']) ? $meta['title'].' - '.config('app.name') : config('app.name');
            $description = $meta['description'] ?? config('meta.description');
            $image = url(config('meta.image'));
        @endphp
        <title data-inertia>{{ $title }}</title>
        {{-- For link previews and crawlers, which don't run JavaScript: config/meta.php. --}}
        <meta name="description" content="{{ $description }}">
        <link rel="canonical" href="{{ url()->current() }}">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ config('app.name') }}">
        <meta property="og:locale" content="en_GB">
        <meta property="og:title" content="{{ $title }}">
        <meta property="og:description" content="{{ $description }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="{{ $image }}">
        <meta property="og:image:width" content="1920">
        <meta property="og:image:height" content="1080">
        <meta name="twitter:card" content="summary_large_image">

        {{-- Until the app has loaded: the page's own background and the logo, not a blank white screen. --}}
        <style>
            #app:empty { min-height: 100svh; background: #fcfaf7 url('/icons/icon.svg') center / 72px no-repeat; }
            @media (prefers-color-scheme: dark) { #app:empty { background-color: #0e1119; } }
        </style>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900|instrument-serif:400|jetbrains-mono:400,500" rel="stylesheet" />

        @routes
        @vite(['resources/js/app.ts'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
