<x-layouts.app-shell title="Telegram setup — Aarambhax">
    @include('app._partials.premium_styles')

    <section class="cases-page">
        <div class="container-page settings-inner">
            <header class="settings-header">
                <p class="cases-eyebrow">Account</p>
                <h1 class="cases-title">Settings</h1>
            </header>

            <nav aria-label="Settings sub-navigation" class="settings-subnav">
                <a href="{{ route('app.settings.profile') }}"
                   class="settings-subnav-link {{ request()->routeIs('app.settings.profile*') ? 'is-active' : '' }}">Profile</a>
                <a href="{{ route('app.settings.telegram') }}"
                   class="settings-subnav-link {{ request()->routeIs('app.settings.telegram*') ? 'is-active' : '' }}">Telegram</a>
            </nav>

            <div class="premium-card">
                <div class="premium-card-head">
                    <h2 class="premium-card-title">Telegram reminders</h2>
                    <p class="premium-card-sub">Get hearing reminders 24 hours before each listing — free, unlimited, sent via Telegram.</p>
                </div>

                @if(! $isConfigured)
                    <div class="premium-error" style="border-color: color-mix(in srgb, var(--warning) 30%, var(--border)); background: color-mix(in srgb, var(--warning) 8%, transparent); color: var(--warning);" role="status">
                        <p><strong>⚠</strong> Telegram bot is not yet configured on the server. Reminders will queue but not deliver until the bot token is set.</p>
                    </div>
                @endif

                @if($user->telegram_chat_id)
                    <div class="tg-status-row">
                        <div>
                            <p class="cases-eyebrow" style="margin: 0 0 0.4rem;">Status</p>
                            <p style="font-family: var(--font-serif); font-size: 1.125rem; font-weight: 500; color: var(--fg); margin: 0;">Connected</p>
                            <p style="font-size: 0.8125rem; color: var(--fg-muted); margin-top: 0.25rem;">Chat ID <code class="tg-code">{{ $user->telegram_chat_id }}</code></p>
                        </div>
                        <span class="tg-pill {{ $user->telegram_alerts_enabled ? 'tg-pill-on' : 'tg-pill-off' }}">
                            {{ $user->telegram_alerts_enabled ? 'Alerts enabled' : 'Alerts paused' }}
                        </span>
                    </div>
                    <div class="premium-actions">
                        <form method="POST" action="{{ route('app.settings.telegram.disconnect') }}" class="inline" onsubmit="return confirm('Disconnect Telegram?');">
                            @csrf
                            <button type="submit" class="cases-btn-ghost" style="color: var(--danger);">Disconnect</button>
                        </form>
                        <form method="POST" action="{{ route('app.settings.telegram.toggle') }}" class="inline">
                            @csrf
                            <button type="submit" class="cases-btn-primary">{{ $user->telegram_alerts_enabled ? 'Pause alerts' : 'Resume alerts' }}</button>
                        </form>
                    </div>
                @else
                    <div>
                        <p style="font-family: var(--font-serif); font-size: 1.125rem; font-weight: 500; color: var(--fg); margin: 0 0 1rem;">Pair this account</p>
                        <ol class="tg-steps">
                            <li><strong>1.</strong> Click "Generate code" below.</li>
                            <li><strong>2.</strong> Open Telegram and search for <strong>@{{ $botUsername }}</strong>.</li>
                            <li><strong>3.</strong> Send <code class="tg-code">/start &lt;your-code&gt;</code>.</li>
                            <li><strong>4.</strong> You'll get a confirmation. Done.</li>
                        </ol>

                        @if($user->telegram_pairing_code)
                            <div class="tg-code-block">
                                <p class="cases-eyebrow" style="margin: 0 0 0.4rem;">Your pairing code</p>
                                <p class="tg-code-value">{{ $user->telegram_pairing_code }}</p>
                                <p style="font-size: 0.75rem; color: var(--fg-muted); margin-top: 0.5rem;">Send <code class="tg-code">/start {{ $user->telegram_pairing_code }}</code> in Telegram</p>
                            </div>
                            <div class="premium-actions">
                                <form method="POST" action="{{ route('app.settings.telegram.code') }}" class="inline">
                                    @csrf
                                    <button type="submit" class="cases-btn-ghost">Generate new code</button>
                                </form>
                            </div>
                        @else
                            <div class="premium-actions">
                                <form method="POST" action="{{ route('app.settings.telegram.code') }}">
                                    @csrf
                                    <button type="submit" class="cases-btn-primary">Generate code</button>
                                </form>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </section>

    <style>
        .settings-inner { padding-top: 2.5rem; padding-bottom: 4rem; max-width: 720px; }
        .settings-header { margin-bottom: 1.75rem; }

        .settings-subnav {
            display: flex; gap: 0.125rem; margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--border);
        }
        .settings-subnav-link {
            display: inline-flex; align-items: center;
            padding: 0.625rem 1rem; font-size: 0.9375rem; font-weight: 500;
            color: var(--fg-muted); text-decoration: none;
            border-bottom: 2px solid transparent; margin-bottom: -1px;
            transition: color 150ms ease-out;
        }
        .settings-subnav-link:hover { color: var(--fg); }
        .settings-subnav-link.is-active {
            color: var(--fg); border-bottom-color: var(--accent);
        }

        .tg-status-row {
            display: flex; align-items: center; justify-content: space-between;
            gap: 1.25rem; padding: 1.25rem;
            background: color-mix(in srgb, var(--accent) 4%, var(--surface));
            border: 1px solid color-mix(in srgb, var(--accent) 18%, var(--border));
            border-radius: 12px;
            flex-wrap: wrap;
        }
        .tg-pill {
            display: inline-flex; align-items: center;
            font-size: 0.6875rem; padding: 0.25rem 0.75rem;
            border-radius: 999px; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.05em;
        }
        .tg-pill-on { background: color-mix(in srgb, var(--success) 14%, transparent); color: var(--success); }
        .tg-pill-off { background: color-mix(in srgb, var(--warning) 14%, transparent); color: var(--warning); }

        .tg-code {
            font-family: var(--font-mono, ui-monospace), monospace;
            font-size: 0.75rem;
            background: color-mix(in srgb, var(--fg) 5%, transparent);
            padding: 0.0625rem 0.4rem;
            border-radius: 4px;
            color: var(--fg);
        }
        .tg-steps {
            list-style: none; padding: 0;
            display: flex; flex-direction: column; gap: 0.5rem;
            font-size: 0.875rem; color: var(--fg-muted); line-height: 1.5;
            margin: 0 0 1.5rem;
        }
        .tg-steps strong { color: var(--accent); margin-right: 0.4rem; }

        .tg-code-block {
            background: color-mix(in srgb, var(--accent) 6%, var(--surface));
            border: 1.5px dashed color-mix(in srgb, var(--accent) 35%, var(--border));
            border-radius: 12px;
            padding: 1.25rem;
            text-align: center;
            margin-bottom: 0.5rem;
        }
        .tg-code-value {
            font-family: var(--font-mono, ui-monospace), monospace;
            font-size: 1.875rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            color: var(--accent);
            margin: 0;
        }
    </style>
</x-layouts.app-shell>
