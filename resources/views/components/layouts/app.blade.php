<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

    {{-- Apply theme from localStorage / OS preference BEFORE first paint to avoid flash --}}
    <script>
        (function () {
            try {
                var saved = localStorage.getItem('theme');
                var dark  = saved === 'dark' ||
                            (!saved && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.dataset.theme = dark ? 'dark' : 'light';
            } catch (e) {}
        })();
    </script>

    <title>{{ $title ?? 'Aarambhax Legal — Drafts for every court' }}</title>
    <meta name="description" content="{{ $description ?? 'AI-powered legal drafting tuned for Chhattisgarh High Court, District Courts, and Revenue Courts. Verified citations. Native Devanagari output.' }}">

    <link rel="canonical" href="{{ $canonical ?? url()->current() }}">

    {{-- Open Graph --}}
    <meta property="og:type" content="{{ $ogType ?? 'website' }}">
    <meta property="og:title" content="{{ $title ?? 'Aarambhax Legal' }}">
    <meta property="og:description" content="{{ $description ?? 'Legal drafts for every court in Chhattisgarh, in Hindi and English.' }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="Aarambhax Legal">
    <meta property="og:locale" content="en_IN">
    @if(isset($ogImage))
        <meta property="og:image" content="{{ $ogImage }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
    @endif

    {{-- Twitter --}}
    <meta name="twitter:card" content="summary_large_image">

    {{-- Theme color (mobile address bar) --}}
    <meta name="theme-color" content="#0B1F3A" media="(prefers-color-scheme: dark)">
    <meta name="theme-color" content="#F8F6F0" media="(prefers-color-scheme: light)">

    {{-- Favicons (placeholder — replace with real ones later) --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

    {{-- Preconnect to Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Page-specific JSON-LD --}}
    @stack('json-ld')
</head>
<body class="min-h-screen flex flex-col">
    <a href="#main-content" class="skip-link">Skip to main content</a>

    @include('partials.nav')

    <main id="main-content" class="flex-1">
        {{ $slot }}
    </main>

    @include('partials.footer')

    {{-- Theme toggle script --}}
    <script>
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-theme-toggle]');
            if (!btn) return;
            var current = document.documentElement.dataset.theme;
            var next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.dataset.theme = next;
            try { localStorage.setItem('theme', next); } catch (e) {}
            // Update icon
            document.querySelectorAll('[data-theme-toggle] [data-icon]').forEach(function (el) {
                el.style.display = el.dataset.icon === next ? 'inline' : 'none';
            });
        });

        // Initialize icon visibility on load
        (function () {
            var current = document.documentElement.dataset.theme;
            document.querySelectorAll('[data-theme-toggle] [data-icon]').forEach(function (el) {
                el.style.display = el.dataset.icon === current ? 'inline' : 'none';
            });
        })();
    </script>
</body>
</html>
