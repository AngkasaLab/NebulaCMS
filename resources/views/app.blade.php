<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Inline script to initialize theme --}}
    <script>
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.classList.remove('light', 'dark');
            document.documentElement.classList.add(theme);
        })();
    </script>

    {{-- Inline style to set the HTML background color based on our theme in app.css --}}
    <style>
        html {
            background-color: oklch(1 0 0);
        }

        html.dark {
            background-color: oklch(0.145 0 0);
        }
    </style>

    <title inertia>{{ config('app.name', 'Laravel') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    @php
        $component = is_string($page['component'] ?? null) ? $page['component'] : null;
        $pluginAssets = app(\App\Services\PluginFrontendAssetRegistry::class)->resolveForComponent($component);
    @endphp

    @routes
    @viteReactRefresh
    @vite('resources/js/plugin-runtime-host.ts')
    @foreach ($pluginAssets['styles'] as $style)
        <link rel="stylesheet" href="{{ $style }}" />
    @endforeach
    @foreach ($pluginAssets['scripts'] as $script)
        <script type="module" src="{{ $script }}"></script>
    @endforeach
    @vite('resources/js/app.tsx')
    @inertiaHead
</head>

<body class="font-sans antialiased">
    @inertia
</body>

</html>
