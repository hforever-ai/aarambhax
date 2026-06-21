<x-layouts.app-shell title="Profile — Aarambhax">
    <section class="settings-page">
        <div class="container-page settings-inner">
            <header class="settings-header">
                <p class="settings-eyebrow">Account</p>
                <h1 class="settings-title">Settings</h1>
            </header>

            <nav aria-label="Settings sub-navigation" class="settings-subnav">
                <a href="{{ route('app.settings.profile') }}"
                   class="settings-subnav-link {{ request()->routeIs('app.settings.profile*') ? 'is-active' : '' }}">
                    Profile
                </a>
                <a href="{{ route('app.settings.telegram') }}"
                   class="settings-subnav-link {{ request()->routeIs('app.settings.telegram*') ? 'is-active' : '' }}">
                    Telegram
                </a>
            </nav>

            <div class="settings-card">
                <div class="settings-card-head">
                    <h2 class="settings-card-title">Advocate profile</h2>
                    <p class="settings-card-sub">These details auto-fill draft signature blocks and cause-titles where possible.</p>
                </div>

                @if($errors->any())
                    <div class="settings-error" role="alert">
                        @foreach($errors->all() as $err)
                            <p>• {{ $err }}</p>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('app.settings.profile.update') }}" class="settings-form">
                    @csrf
                    @method('PATCH')

                    <div class="settings-field">
                        <label for="name">Full name <span class="settings-required">*</span></label>
                        <input id="name" type="text" name="name" required maxlength="200" value="{{ old('name', $user->name) }}">
                    </div>

                    <div class="settings-field">
                        <label for="bar_enrolment_no">Bar Council enrolment number</label>
                        <input id="bar_enrolment_no" type="text" name="bar_enrolment_no" maxlength="50"
                               value="{{ old('bar_enrolment_no', $user->bar_enrolment_no) }}"
                               placeholder="C/G/1234/2010"
                               class="settings-mono">
                    </div>

                    <div class="settings-field">
                        <label for="chamber_address">Chamber address</label>
                        <textarea id="chamber_address" name="chamber_address" rows="3" maxlength="1000"
                                  placeholder="Chamber 14, Bar Association Building, High Court Premises, Bilaspur, Chhattisgarh 495001">{{ old('chamber_address', $user->chamber_address) }}</textarea>
                    </div>

                    <div class="settings-field">
                        <label for="signature_block_en">Signature block — English</label>
                        <textarea id="signature_block_en" name="signature_block_en" rows="4" maxlength="2000"
                                  placeholder="Yours faithfully,&#10;&#10;[Name]&#10;Advocate&#10;Bar Council Enrolment No: ..."
                                  class="settings-mono settings-textarea-tight">{{ old('signature_block_en', $user->signature_block_en) }}</textarea>
                    </div>

                    <div class="settings-field">
                        <label for="signature_block_hi">Signature block — Hindi (Devanagari)</label>
                        <textarea id="signature_block_hi" name="signature_block_hi" rows="4" maxlength="2000"
                                  placeholder="विनम्र अधिवक्ता,&#10;&#10;[नाम]&#10;अधिवक्ता&#10;बार काउंसिल पंजीकरण क्रमांक: ..."
                                  class="settings-textarea-tight">{{ old('signature_block_hi', $user->signature_block_hi) }}</textarea>
                    </div>

                    <div class="settings-actions">
                        <button type="submit" class="cases-btn-primary">Save profile</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <style>
        .settings-page {
            background: linear-gradient(180deg,
                color-mix(in srgb, var(--accent) 3%, var(--bg)) 0%,
                var(--bg) 240px);
            min-height: 100vh;
        }
        .settings-inner {
            padding-top: 2.5rem;
            padding-bottom: 4rem;
            max-width: 720px;
        }

        .settings-header { margin-bottom: 1.75rem; }
        .settings-eyebrow {
            font-size: 0.75rem;
            color: var(--fg-muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin: 0 0 0.4rem;
        }
        .settings-title {
            font-family: var(--font-serif);
            font-size: clamp(2rem, 3.5vw, 2.625rem);
            font-weight: 500;
            color: var(--fg);
            letter-spacing: -0.018em;
            line-height: 1.1;
            margin: 0;
        }

        .settings-subnav {
            display: flex;
            gap: 0.125rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--border);
        }
        .settings-subnav-link {
            display: inline-flex;
            align-items: center;
            padding: 0.625rem 1rem;
            font-size: 0.9375rem;
            font-weight: 500;
            color: var(--fg-muted);
            text-decoration: none;
            border-bottom: 2px solid transparent;
            margin-bottom: -1px;
            transition: color 150ms ease-out;
        }
        .settings-subnav-link:hover { color: var(--fg); }
        .settings-subnav-link.is-active {
            color: var(--fg);
            border-bottom-color: var(--accent);
        }

        .settings-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 1.875rem;
        }
        .settings-card-head { margin-bottom: 1.5rem; }
        .settings-card-title {
            font-family: var(--font-serif);
            font-size: 1.3125rem;
            font-weight: 500;
            color: var(--fg);
            letter-spacing: -0.01em;
            margin: 0 0 0.4rem;
        }
        .settings-card-sub {
            font-size: 0.875rem;
            color: var(--fg-muted);
            margin: 0;
            line-height: 1.55;
        }

        .settings-error {
            background: color-mix(in srgb, var(--danger) 8%, transparent);
            border: 1px solid color-mix(in srgb, var(--danger) 30%, var(--border));
            border-radius: 8px;
            padding: 0.75rem 1rem;
            margin-bottom: 1.25rem;
            font-size: 0.875rem;
            color: var(--danger);
        }
        .settings-error p { margin: 0.125rem 0; }

        .settings-form {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }
        .settings-field {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }
        .settings-field label {
            font-size: 0.8125rem;
            font-weight: 500;
            color: var(--fg);
            letter-spacing: 0.005em;
        }
        .settings-required {
            color: var(--danger);
            margin-left: 0.125rem;
        }
        .settings-field input,
        .settings-field textarea {
            width: 100%;
            padding: 0.625rem 0.875rem;
            background: var(--bg);
            border: 1.5px solid var(--border);
            border-radius: 8px;
            color: var(--fg);
            font-family: var(--font-sans);
            font-size: 0.9375rem;
            transition: border-color 150ms ease-out, background 150ms ease-out;
        }
        .settings-field textarea {
            resize: vertical;
            min-height: 80px;
            line-height: 1.55;
        }
        .settings-field .settings-mono {
            font-family: var(--font-mono, ui-monospace), monospace;
            font-size: 0.875rem;
        }
        .settings-textarea-tight {
            line-height: 1.5 !important;
        }
        .settings-field input:focus,
        .settings-field textarea:focus {
            outline: none;
            border-color: var(--accent);
            background: var(--surface);
        }
        .settings-field input::placeholder,
        .settings-field textarea::placeholder {
            color: color-mix(in srgb, var(--fg-muted) 70%, transparent);
        }

        .settings-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
            padding-top: 0.5rem;
            margin-top: 0.5rem;
            border-top: 1px solid var(--border);
            padding-top: 1.25rem;
        }
        .cases-btn-primary {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.6875rem 1.25rem; font-size: 0.9375rem; font-weight: 500;
            background: var(--primary); color: var(--primary-fg);
            border: 1px solid var(--primary); border-radius: 9px;
            text-decoration: none; cursor: pointer;
            transition: transform 120ms ease-out, box-shadow 220ms ease-out;
            font-family: var(--font-sans);
        }
        .cases-btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px -6px color-mix(in srgb, var(--primary) 35%, transparent);
        }
        .cases-btn-primary:focus-visible {
            outline: 2px solid var(--accent); outline-offset: 3px;
        }

        @media (prefers-reduced-motion: reduce) {
            .cases-btn-primary, .settings-subnav-link { transition: none; }
            .cases-btn-primary:hover { transform: none; }
        }
    </style>
</x-layouts.app-shell>
