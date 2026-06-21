@php
    $publicNavItems = [
        ['url' => url('/'),                   'pattern_check' => fn() => request()->is('/'),                       'label' => __('nav.home')],
        ['url' => route('verifier.show'),     'pattern_check' => fn() => request()->routeIs('verifier.*'),         'label' => __('nav.verifier')],
        ['url' => route('blog.index'),        'pattern_check' => fn() => request()->routeIs('blog.*'),             'label' => __('nav.blog')],
        ['url' => route('faq.index'),         'pattern_check' => fn() => request()->routeIs('faq.*'),              'label' => __('nav.faq')],
        ['url' => route('pages.pricing'),     'pattern_check' => fn() => request()->routeIs('pages.pricing'),      'label' => __('nav.pricing')],
        ['url' => route('pages.about'),       'pattern_check' => fn() => request()->routeIs('pages.about'),        'label' => __('nav.about')],
    ];
@endphp

<header class="border-b" style="border-color: var(--border); background: var(--bg);">
    <nav class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 flex items-center justify-between py-3 gap-3" aria-label="Primary">
        {{-- Mobile hamburger (visible <lg) --}}
        <button type="button" id="public-menu-btn"
                class="lg:hidden hamburger-btn"
                aria-label="Toggle menu"
                aria-expanded="false"
                aria-controls="public-menu">
            <span class="hamburger-bar"></span>
            <span class="hamburger-bar"></span>
            <span class="hamburger-bar"></span>
        </button>

        <a href="{{ url('/') }}" class="inline-flex items-center shrink-0" aria-label="Aarambhax Legal — home">
            <x-logo size="xl" />
        </a>

        <ul class="hidden lg:flex items-center gap-1" role="list">
            @foreach($publicNavItems as $item)
                <li><a href="{{ $item['url'] }}" class="nav-link {{ ($item['pattern_check'])() ? 'nav-link-active' : '' }}">{{ $item['label'] }}</a></li>
            @endforeach
        </ul>

        <div class="flex items-center gap-2 shrink-0">
            {{-- Language switcher --}}
            <div class="inline-flex rounded-md overflow-hidden" style="border: 1px solid var(--border);" role="group" aria-label="Language">
                <a href="{{ route('locale.set', 'en') }}"
                   class="px-2.5 py-1.5 text-sm"
                   aria-current="{{ app()->getLocale() === 'en' ? 'true' : 'false' }}"
                   style="background: {{ app()->getLocale() === 'en' ? 'var(--primary)' : 'transparent' }}; color: {{ app()->getLocale() === 'en' ? 'var(--primary-fg)' : 'var(--fg-muted)' }};">EN</a>
                <a href="{{ route('locale.set', 'hi') }}"
                   class="px-2.5 py-1.5 text-sm"
                   aria-current="{{ app()->getLocale() === 'hi' ? 'true' : 'false' }}"
                   style="background: {{ app()->getLocale() === 'hi' ? 'var(--primary)' : 'transparent' }}; color: {{ app()->getLocale() === 'hi' ? 'var(--primary-fg)' : 'var(--fg-muted)' }};">हिं</a>
            </div>

            <button type="button"
                    data-theme-toggle
                    aria-label="Toggle dark / light theme"
                    class="theme-toggle">
                <span data-icon="light" aria-hidden="true">🌙</span>
                <span data-icon="dark"  aria-hidden="true">☀️</span>
            </button>

            @auth
                <a href="{{ route('app.dashboard') }}" class="btn btn-primary">Open app</a>
            @else
                <a href="{{ route('login') }}" class="nav-link hidden sm:inline-flex">Login</a>
                <a href="{{ route('register') }}" class="btn btn-primary hidden sm:inline-flex">Sign up</a>
            @endauth
        </div>
    </nav>

    {{-- Mobile drawer --}}
    <div id="public-menu" class="lg:hidden mobile-drawer" aria-hidden="true">
        <ul class="flex flex-col gap-1 px-4 py-3" role="list">
            @foreach($publicNavItems as $item)
                <li><a href="{{ $item['url'] }}" class="nav-link block w-full {{ ($item['pattern_check'])() ? 'nav-link-active' : '' }}">{{ $item['label'] }}</a></li>
            @endforeach
            <li class="pt-2 mt-2" style="border-top: 1px solid var(--border);">
                @auth
                    <a href="{{ route('app.dashboard') }}" class="nav-link block w-full">Open app</a>
                @else
                    <a href="{{ route('login') }}" class="nav-link block w-full">Login</a>
                    <a href="{{ route('register') }}" class="nav-link block w-full">Sign up</a>
                @endauth
            </li>
        </ul>
    </div>
</header>

<script>
    (function () {
        var btn  = document.getElementById('public-menu-btn');
        var menu = document.getElementById('public-menu');
        if (!btn || !menu) return;
        btn.addEventListener('click', function () {
            var open = menu.classList.toggle('is-open');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            menu.setAttribute('aria-hidden', open ? 'false' : 'true');
        });
        menu.addEventListener('click', function (e) {
            if (e.target.closest('a, button')) {
                menu.classList.remove('is-open');
                btn.setAttribute('aria-expanded', 'false');
                menu.setAttribute('aria-hidden', 'true');
            }
        });
    })();
</script>
