<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'dark') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "dark" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: #ffffff;
            }

            html.dark {
                background-color: #09090b;
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon-32x32.png" type="image/png" sizes="32x32">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])

        {{-- SEO: rendered server-side so crawlers and link previews see it without JavaScript. --}}
        @php
            $seo = $page['props']['seo'] ?? [];
            $appName = config('app.name', 'Laravel');
            // Same "Title - App" format as the client-side title callback in app.ts.
            $seoTitle = ! empty($seo['title']) ? $seo['title'].' - '.$appName : $appName;
            $seoDescription = $seo['description'] ?? null;
            $seoImage = \App\Services\Seo::url('og-image.png');
        @endphp
        <x-inertia::head>
            <title>{{ $seoTitle }}</title>
        </x-inertia::head>
        @if ($seoDescription)
            <meta name="description" content="{{ $seoDescription }}">
        @endif
        <meta name="robots" content="{{ $seo['robots'] ?? 'noindex, nofollow' }}">
        @if (! empty($seo['canonical']))
            <link rel="canonical" href="{{ $seo['canonical'] }}">
        @endif

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ $appName }}">
        <meta property="og:title" content="{{ $seoTitle }}">
        @if ($seoDescription)
            <meta property="og:description" content="{{ $seoDescription }}">
        @endif
        @if (! empty($seo['url']))
            <meta property="og:url" content="{{ $seo['url'] }}">
        @endif
        <meta property="og:image" content="{{ $seoImage }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:alt" content="Davao Beyblade Battle League (DBBL) crest">

        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $seoTitle }}">
        @if ($seoDescription)
            <meta name="twitter:description" content="{{ $seoDescription }}">
        @endif
        <meta name="twitter:image" content="{{ $seoImage }}">
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
