<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $title ?? 'StudyAI' }}</title>
    <meta name="robots" content="noindex">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* ── Shrutam Navy Theme ── */
        :root {
            --bg:         #070e1c;
            --surface:    #0d1a2e;
            --surface-2:  #132338;
            --surface-3:  #1a2f47;
            --fg:         #e8f1ff;
            --fg-muted:   #7ea8d4;
            --fg-subtle:  #4a6a8a;
            --border:     rgba(96,165,250,0.22);
            --border-sub: rgba(96,165,250,0.12);
            --border-str: rgba(96,165,250,0.40);
            --primary:    #f59e0b;
            --primary-fg: #070e1c;
            --accent:     #f59e0b;
            --accent-fg:  #070e1c;
            --accent-glow:rgba(245,158,11,0.15);
            --accent-faint:#fef3c7;
            --link:       #60a5fa;
            --success:    #22c55e;
            --success-bg: #052e16;
            --warning:    #f59e0b;
            --danger:     #ef4444;
            --danger-bg:  #450a0a;
            --font-serif: 'Nunito', 'Noto Sans Devanagari', ui-sans-serif, sans-serif;
            --font-sans:  'Inter', 'Noto Sans Devanagari', ui-sans-serif, sans-serif;
            --font-mono:  'JetBrains Mono', ui-monospace, monospace;
        }

        *, *::before, *::after { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            background: var(--bg);
            color: var(--fg);
            font-family: var(--font-sans);
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0;
        }
        h1,h2,h3,h4,h5,h6 { font-family: var(--font-serif); font-weight: 700; letter-spacing: -0.01em; }

        /* ── Nav ── */
        .sn-shell { background: rgba(7,14,28,0.85); border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 50; backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); }
        .sn-nav { max-width: 1200px; margin: 0 auto; padding: 0.75rem 1.25rem; display: flex; align-items: center; gap: 1rem; }
        .sn-brand { display: inline-flex; align-items: center; gap: 0.5rem; text-decoration: none; flex-shrink: 0; }
        .sn-brand-mark { width: 32px; height: 32px; background: linear-gradient(135deg,#f59e0b,#d97706); border-radius: 9px; display: inline-flex; align-items: center; justify-content: center; font-family: var(--font-serif); font-weight: 800; font-size: 1rem; color: #070e1c; box-shadow: 0 0 16px rgba(245,158,11,0.35); }
        .sn-brand-text { font-family: var(--font-serif); font-weight: 800; font-size: 1.125rem; color: var(--fg); letter-spacing: -0.02em; }

        .sn-nav-links { display: none; flex: 1; align-items: center; gap: 0.25rem; justify-content: center; list-style: none; padding: 0; margin: 0; }
        @media(min-width:640px) { .sn-nav-links { display: flex; } }
        .sn-nav-link { display: inline-flex; align-items: center; padding: 0.4375rem 0.875rem; font-size: 0.875rem; font-weight: 600; color: var(--fg-muted); text-decoration: none; border-radius: 8px; transition: color 150ms, background 150ms; }
        .sn-nav-link:hover { color: var(--fg); background: var(--surface-2); }
        .sn-nav-link.is-active { color: var(--accent); background: var(--accent-glow); }

        .sn-nav-right { display: flex; align-items: center; gap: 0.625rem; margin-left: auto; }
        .sn-user-pill { display: inline-flex; align-items: center; gap: 0.4375rem; padding: 0.25rem 0.625rem 0.25rem 0.25rem; background: var(--surface-2); border: 1px solid var(--border); border-radius: 999px; font-size: 0.8125rem; color: var(--fg-muted); }
        .sn-user-av { width: 22px; height: 22px; background: var(--accent); color: var(--accent-fg); font-size: 0.625rem; font-weight: 800; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .sn-logout { background: transparent; border: none; font-size: 0.8125rem; color: var(--fg-subtle); cursor: pointer; padding: 0.375rem 0.5rem; border-radius: 6px; transition: color 150ms; font-family: var(--font-sans); }
        .sn-logout:hover { color: var(--danger); }

        /* Mobile bottom nav */
        .sn-bottom-nav { display: flex; position: fixed; bottom: 0; left: 0; right: 0; background: rgba(7,14,28,0.95); border-top: 1px solid var(--border); backdrop-filter: blur(16px); z-index: 50; padding: 0.5rem 0 calc(0.5rem + env(safe-area-inset-bottom)); }
        @media(min-width:640px) { .sn-bottom-nav { display: none; } }
        .sn-bottom-nav a { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 0.2rem; padding: 0.375rem 0; font-size: 0.6rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--fg-subtle); text-decoration: none; transition: color 150ms; }
        .sn-bottom-nav a.is-active { color: var(--accent); }
        .sn-bottom-nav svg { width: 20px; height: 20px; }

        main { flex: 1; padding-bottom: 5rem; }
        @media(min-width:640px) { main { padding-bottom: 2rem; } }

        /* Flash messages */
        .sn-flash-wrap { max-width: 1200px; margin: 0 auto; padding: 0.75rem 1.25rem 0; }
        .sn-flash { background: rgba(34,197,94,0.12); border: 1px solid rgba(34,197,94,0.3); color: #4ade80; border-radius: 10px; padding: 0.625rem 1rem; font-size: 0.875rem; }
    </style>
</head>
<body>
    @php
        $navItems = [
            ['route' => 'app.dashboard',           'pattern' => 'app.dashboard',       'label' => 'Home',     'icon' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>'],
            ['route' => 'app.student-notes.index', 'pattern' => 'app.student-notes.*', 'label' => 'Notes',    'icon' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>'],
            ['route' => 'app.settings.profile',    'pattern' => 'app.settings.*',      'label' => 'Settings', 'icon' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>'],
        ];
    @endphp

    <header class="sn-shell">
        <nav class="sn-nav">
            <a href="{{ route('app.dashboard') }}" class="sn-brand">
                <span class="sn-brand-mark">Z</span>
                <span class="sn-brand-text">Zenith</span>
            </a>

            <ul class="sn-nav-links">
                @foreach($navItems as $item)
                    @php $active = request()->routeIs($item['pattern']); @endphp
                    <li>
                        <a href="{{ route($item['route']) }}" class="sn-nav-link {{ $active ? 'is-active' : '' }}" @if($active) aria-current="page" @endif>
                            {{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="sn-nav-right">
                @auth
                    <div class="sn-user-pill">
                        <span class="sn-user-av">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</span>
                        <span>{{ explode(' ', auth()->user()->name)[0] }}</span>
                    </div>
                @endauth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="sn-logout">Logout</button>
                </form>
            </div>
        </nav>
    </header>

    @if(session('flash'))
        <div class="sn-flash-wrap">
            <div class="sn-flash">{{ session('flash') }}</div>
        </div>
    @endif

    <main id="main-content">{{ $slot }}</main>

    {{-- Mobile bottom nav --}}
    <nav class="sn-bottom-nav" aria-label="Mobile navigation">
        @foreach($navItems as $item)
            @php $active = request()->routeIs($item['pattern']); @endphp
            <a href="{{ route($item['route']) }}" class="{{ $active ? 'is-active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $item['icon'] !!}</svg>
                {{ $item['label'] }}
            </a>
        @endforeach
        <form method="POST" action="{{ route('logout') }}" style="flex:1">
            @csrf
            <button type="submit" style="width:100%;height:100%;background:transparent;border:none;display:flex;flex-direction:column;align-items:center;gap:.2rem;padding:.375rem 0;font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--fg-subtle);cursor:pointer;font-family:var(--font-sans)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Out
            </button>
        </form>
    </nav>
</body>
</html>
