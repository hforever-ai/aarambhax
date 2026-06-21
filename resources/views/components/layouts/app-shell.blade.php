<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <script>
        (function () {
            try {
                var saved = localStorage.getItem('theme');
                var dark  = saved === 'dark' || (!saved && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.dataset.theme = dark ? 'dark' : 'light';
            } catch (e) {}
        })();
    </script>
    <title>{{ $title ?? 'App — Aarambhax Legal' }}</title>
    <meta name="robots" content="noindex">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex flex-col">
    <a href="#main-content" class="skip-link">Skip to main content</a>

    @php
        $isStudent = auth()->check() && auth()->user()->isStudent();
        if ($isStudent) {
            $navItems = [
                ['route' => 'app.dashboard',            'pattern' => 'app.dashboard',        'label' => 'Home'],
                ['route' => 'app.student-notes.index',  'pattern' => 'app.student-notes.*',  'label' => 'My Notes'],
                ['route' => 'app.settings.profile',     'pattern' => 'app.settings.*',       'label' => 'Settings'],
            ];
        } else {
            // Draft creation removed from UI per Karya-first design — Karya is the
            // only entry point for AI-generated content. Existing drafts still
            // accessible via Drafts list (read/edit). Routes + controllers kept.
            $navItems = [
                ['route' => 'app.dashboard',       'pattern' => 'app.dashboard',  'label' => 'Dashboard'],
                ['route' => 'app.cases.index',     'pattern' => 'app.cases.*',    'label' => 'Cases'],
                ['route' => 'app.drafts.index',    'pattern' => 'app.drafts.*',   'label' => 'Drafts'],
                ['route' => 'app.catalogue.index', 'pattern' => 'app.catalogue.*','label' => 'Catalogue'],
                ['route' => 'app.clients.index',   'pattern' => 'app.clients.*',  'label' => 'Clients'],
                ['route' => 'app.hearings.index',  'pattern' => 'app.hearings.*', 'label' => 'Hearings'],
                ['route' => 'app.settings.profile','pattern' => 'app.settings.*', 'label' => 'Settings'],
            ];
        }
    @endphp

    <header class="app-nav-shell">
        <nav class="app-nav" aria-label="App navigation">
            {{-- Brand mark --}}
            <a href="{{ route('app.dashboard') }}" class="app-nav-brand" aria-label="Aarambhax — go to dashboard">
                <span class="app-nav-brand-mark">A</span>
                <span class="app-nav-brand-text">Aarambhax</span>
            </a>

            {{-- Mobile hamburger (visible <lg) --}}
            <button type="button" id="mobile-menu-btn"
                    class="lg:hidden hamburger-btn"
                    aria-label="Toggle menu"
                    aria-expanded="false"
                    aria-controls="mobile-menu">
                <span class="hamburger-bar"></span>
                <span class="hamburger-bar"></span>
                <span class="hamburger-bar"></span>
            </button>

            {{-- Desktop nav (visible ≥lg) --}}
            <ul class="hidden lg:flex app-nav-links" role="list">
                @foreach($navItems as $item)
                    @php($active = request()->routeIs($item['pattern']))
                    <li>
                        <a href="{{ route($item['route']) }}"
                           @if($active) aria-current="page" @endif
                           class="app-nav-link {{ $active ? 'is-active' : '' }}">
                            {{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="app-nav-right">
                <button type="button" data-theme-toggle aria-label="Toggle dark / light theme" class="app-nav-theme-toggle">
                    <span data-icon="light" aria-hidden="true">🌙</span>
                    <span data-icon="dark"  aria-hidden="true">☀️</span>
                </button>
                @auth
                    <div class="app-nav-user hidden lg:flex" title="{{ auth()->user()->email }}">
                        <span class="app-nav-user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="app-nav-user-name">{{ explode(' ', auth()->user()->name)[0] }}</span>
                    </div>
                @endauth
                <form method="POST" action="{{ route('logout') }}" class="hidden lg:inline">
                    @csrf
                    <button type="submit" class="app-nav-logout" aria-label="Log out">Logout</button>
                </form>
            </div>
        </nav>

        {{-- Mobile drawer (visible only on small screens, hidden by default) --}}
        <div id="mobile-menu" class="lg:hidden mobile-drawer" aria-hidden="true">
            <ul class="flex flex-col gap-1 px-4 py-3" role="list">
                @foreach($navItems as $item)
                    @php($active = request()->routeIs($item['pattern']))
                    <li>
                        <a href="{{ route($item['route']) }}"
                           @if($active) aria-current="page" @endif
                           class="app-nav-link app-nav-link-mobile {{ $active ? 'is-active' : '' }}">
                            {{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
                <li class="pt-2 mt-2" style="border-top: 1px solid var(--border);">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="app-nav-link app-nav-link-mobile w-full text-left">Logout</button>
                    </form>
                </li>
            </ul>
        </div>

        <style>
            .app-nav-shell {
                background: color-mix(in srgb, var(--surface) 60%, var(--bg));
                border-bottom: 1px solid var(--border);
                position: sticky;
                top: 0;
                z-index: 50;
                backdrop-filter: blur(12px);
                -webkit-backdrop-filter: blur(12px);
            }

            .app-nav {
                max-width: 1280px;
                margin: 0 auto;
                padding: 0.75rem 1.25rem;
                display: flex;
                align-items: center;
                gap: 1rem;
            }

            /* Brand */
            .app-nav-brand {
                display: inline-flex;
                align-items: center;
                gap: 0.625rem;
                text-decoration: none;
                color: var(--fg);
                flex-shrink: 0;
                transition: opacity 150ms ease-out;
            }
            .app-nav-brand:hover { opacity: 0.85; }
            .app-nav-brand:focus-visible {
                outline: 2px solid var(--accent);
                outline-offset: 4px;
                border-radius: 4px;
            }
            .app-nav-brand-mark {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 32px;
                height: 32px;
                background: var(--primary);
                color: var(--primary-fg);
                font-family: var(--font-serif);
                font-size: 1.0625rem;
                font-weight: 600;
                border-radius: 8px;
                letter-spacing: -0.02em;
                box-shadow: 0 2px 8px -2px color-mix(in srgb, var(--primary) 35%, transparent);
            }
            .app-nav-brand-text {
                font-family: var(--font-serif);
                font-size: 1.125rem;
                font-weight: 500;
                letter-spacing: -0.01em;
                display: none;
            }
            @media (min-width: 640px) {
                .app-nav-brand-text { display: inline; }
            }

            /* Links */
            .app-nav-links {
                display: flex;
                align-items: center;
                gap: 0.125rem;
                flex: 1;
                justify-content: center;
                list-style: none;
                padding: 0;
                margin: 0;
            }
            .app-nav-link {
                display: inline-flex;
                align-items: center;
                padding: 0.5rem 0.875rem;
                font-size: 0.875rem;
                font-weight: 500;
                color: var(--fg-muted);
                text-decoration: none;
                border-radius: 7px;
                transition: color 150ms ease-out, background 150ms ease-out;
                position: relative;
                background: transparent;
                border: none;
                cursor: pointer;
                font-family: var(--font-sans);
            }
            .app-nav-link:hover {
                color: var(--fg);
                background: color-mix(in srgb, var(--fg) 4%, transparent);
            }
            .app-nav-link:focus-visible {
                outline: 2px solid var(--accent);
                outline-offset: 2px;
            }
            .app-nav-link.is-active {
                color: var(--fg);
                background: color-mix(in srgb, var(--accent) 12%, transparent);
            }
            .app-nav-link.is-active::after {
                content: '';
                position: absolute;
                left: 1rem;
                right: 1rem;
                bottom: 2px;
                height: 2px;
                background: var(--accent);
                border-radius: 2px;
                opacity: 0.6;
            }
            @media (min-width: 1280px) {
                .app-nav-link { padding: 0.5rem 1rem; font-size: 0.9375rem; }
            }

            /* Right cluster */
            .app-nav-right {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                flex-shrink: 0;
            }
            .app-nav-theme-toggle {
                width: 34px;
                height: 34px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                background: transparent;
                border: 1px solid var(--border);
                border-radius: 8px;
                cursor: pointer;
                font-size: 0.875rem;
                color: var(--fg-muted);
                transition: background 150ms ease-out, border-color 150ms ease-out;
            }
            .app-nav-theme-toggle:hover {
                background: color-mix(in srgb, var(--fg) 5%, transparent);
                border-color: color-mix(in srgb, var(--fg) 20%, var(--border));
            }
            .app-nav-theme-toggle:focus-visible {
                outline: 2px solid var(--accent);
                outline-offset: 2px;
            }
            .app-nav-theme-toggle [data-icon='light'] { display: inline; }
            .app-nav-theme-toggle [data-icon='dark']  { display: none; }
            [data-theme='dark'] .app-nav-theme-toggle [data-icon='light'] { display: none; }
            [data-theme='dark'] .app-nav-theme-toggle [data-icon='dark']  { display: inline; }

            .app-nav-user {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.25rem 0.625rem 0.25rem 0.25rem;
                background: color-mix(in srgb, var(--accent) 8%, transparent);
                border: 1px solid color-mix(in srgb, var(--accent) 18%, var(--border));
                border-radius: 999px;
                font-size: 0.8125rem;
                color: var(--fg);
                cursor: default;
            }
            .app-nav-user-avatar {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 24px;
                height: 24px;
                background: var(--accent);
                color: var(--accent-fg);
                font-size: 0.6875rem;
                font-weight: 700;
                border-radius: 50%;
                flex-shrink: 0;
            }
            .app-nav-user-name {
                font-weight: 500;
                font-size: 0.8125rem;
            }

            .app-nav-logout {
                display: inline-flex;
                align-items: center;
                padding: 0.5rem 0.75rem;
                font-size: 0.8125rem;
                font-weight: 500;
                color: var(--fg-muted);
                background: transparent;
                border: none;
                border-radius: 7px;
                cursor: pointer;
                transition: color 150ms ease-out, background 150ms ease-out;
            }
            .app-nav-logout:hover {
                color: var(--danger);
                background: color-mix(in srgb, var(--danger) 6%, transparent);
            }

            /* Mobile drawer */
            .app-nav-link-mobile {
                width: 100%;
                padding: 0.625rem 0.875rem;
                border-radius: 8px;
            }
            .app-nav-link-mobile.is-active::after { display: none; }

            @media (prefers-reduced-motion: reduce) {
                .app-nav-link, .app-nav-theme-toggle, .app-nav-logout, .app-nav-brand { transition: none; }
            }
        </style>
    </header>

    @if (session('edit_success'))
        <div class="container-page mt-4">
            <div class="card" style="border-color: var(--success); background: color-mix(in srgb, var(--success) 8%, var(--surface));" role="status">
                <p style="color: var(--success);"><strong>✓</strong> {{ session('edit_success') }}</p>
            </div>
        </div>
    @endif

    @if (session('draft_warning'))
        <div class="container-page mt-4">
            <div class="card" style="border-color: var(--warning); background: color-mix(in srgb, var(--warning) 8%, var(--surface));" role="status">
                <p style="color: var(--warning);"><strong>⚠</strong> {{ session('draft_warning') }}</p>
            </div>
        </div>
    @endif

    @auth
        @if(! auth()->user()->isApproved() && ! auth()->user()->isAdmin())
            <div class="container-page mt-4">
                <x-pending-approval-banner />
            </div>
        @endif
    @endauth

    @if (session('flash'))
        <div class="container-page mt-4">
            <div class="card" style="border-color: var(--accent); background: color-mix(in srgb, var(--accent) 8%, var(--surface));" role="status">
                <p style="color: var(--fg);">{{ session('flash') }}</p>
            </div>
        </div>
    @endif

    @if ($errors->has('approval'))
        <div class="container-page mt-4">
            <div class="card" style="border-color: var(--warning); background: color-mix(in srgb, var(--warning) 8%, var(--surface));" role="alert">
                <p style="color: var(--fg);"><strong>⚠</strong> {{ $errors->first('approval') }}</p>
            </div>
        </div>
    @endif

    <main id="main-content" class="flex-1">
        {{ $slot }}
    </main>

    <script>
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-theme-toggle]');
            if (!btn) return;
            var current = document.documentElement.dataset.theme;
            var next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.dataset.theme = next;
            try { localStorage.setItem('theme', next); } catch (e) {}
            document.querySelectorAll('[data-theme-toggle] [data-icon]').forEach(function (el) {
                el.style.display = el.dataset.icon === next ? 'inline' : 'none';
            });
        });
        (function () {
            var current = document.documentElement.dataset.theme;
            document.querySelectorAll('[data-theme-toggle] [data-icon]').forEach(function (el) {
                el.style.display = el.dataset.icon === current ? 'inline' : 'none';
            });
        })();

        // Mobile hamburger toggle
        (function () {
            var btn  = document.getElementById('mobile-menu-btn');
            var menu = document.getElementById('mobile-menu');
            if (!btn || !menu) return;
            btn.addEventListener('click', function () {
                var open = menu.classList.toggle('is-open');
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
                menu.setAttribute('aria-hidden', open ? 'false' : 'true');
            });
            // Close drawer when a nav link is tapped
            menu.addEventListener('click', function (e) {
                if (e.target.closest('a, button')) {
                    menu.classList.remove('is-open');
                    btn.setAttribute('aria-expanded', 'false');
                    menu.setAttribute('aria-hidden', 'true');
                }
            });
        })();
    </script>
</body>
</html>
