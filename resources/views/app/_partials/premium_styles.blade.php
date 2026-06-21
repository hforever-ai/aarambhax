@once
{{-- Shared premium styles used across cases / drafts / clients / hearings / forms.
     Loaded once per page via @once so multiple includes don't duplicate. --}}
<style>
    .cases-page {
        background: linear-gradient(180deg,
            color-mix(in srgb, var(--accent) 3%, var(--bg)) 0%,
            var(--bg) 240px);
        min-height: 100vh;
    }
    .cases-inner { padding-top: 2.5rem; padding-bottom: 4rem; max-width: 1100px; }

    .cases-header {
        display: flex; align-items: flex-end; justify-content: space-between;
        gap: 1.25rem; margin-bottom: 2.25rem; flex-wrap: wrap;
    }
    .cases-eyebrow {
        font-size: 0.75rem; color: var(--fg-muted);
        text-transform: uppercase; letter-spacing: 0.08em; margin: 0 0 0.4rem;
    }
    .cases-title {
        font-family: var(--font-serif);
        font-size: clamp(2rem, 3.5vw, 2.625rem); font-weight: 500;
        color: var(--fg); letter-spacing: -0.018em; line-height: 1.1; margin: 0;
    }
    .cases-subtitle {
        font-size: 0.9375rem; color: var(--fg-muted);
        margin: 0.625rem 0 0; max-width: 56ch; line-height: 1.55;
    }

    .cases-btn-primary {
        display: inline-flex; align-items: center; gap: 0.5rem;
        padding: 0.6875rem 1.25rem; font-size: 0.9375rem; font-weight: 500;
        background: var(--primary); color: var(--primary-fg);
        border: 1px solid var(--primary); border-radius: 9px;
        text-decoration: none; cursor: pointer;
        font-family: var(--font-sans);
        transition: transform 120ms ease-out, box-shadow 220ms ease-out;
    }
    .cases-btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px -6px color-mix(in srgb, var(--primary) 35%, transparent);
    }
    .cases-btn-primary:focus-visible { outline: 2px solid var(--accent); outline-offset: 3px; }
    .cases-btn-primary.is-disabled { opacity: 0.5; cursor: not-allowed; }

    .cases-btn-ghost {
        display: inline-flex; align-items: center; gap: 0.5rem;
        padding: 0.6875rem 1.25rem; font-size: 0.9375rem; font-weight: 500;
        background: transparent; color: var(--fg);
        border: 1px solid var(--border); border-radius: 9px;
        text-decoration: none; cursor: pointer;
        font-family: var(--font-sans);
        transition: background 150ms ease-out, border-color 150ms ease-out;
    }
    .cases-btn-ghost:hover {
        background: color-mix(in srgb, var(--fg) 4%, transparent);
        border-color: color-mix(in srgb, var(--fg) 20%, var(--border));
    }
    .cases-btn-ghost:focus-visible { outline: 2px solid var(--accent); outline-offset: 3px; }

    .cases-row-sep { color: color-mix(in srgb, var(--border) 60%, transparent); }

    /* Status pills */
    .cases-status {
        display: inline-flex; align-items: center; gap: 0.3rem;
        font-size: 0.6875rem; padding: 0.1875rem 0.625rem;
        border-radius: 999px; font-weight: 600;
        text-transform: uppercase; letter-spacing: 0.05em;
    }
    .cases-status::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
    .cases-status-active { background: color-mix(in srgb, var(--success) 14%, transparent); color: var(--success); }
    .cases-status-closed { background: color-mix(in srgb, var(--fg-muted) 12%, transparent); color: var(--fg-muted); }

    /* Empty state */
    .cases-empty {
        background: var(--surface);
        border: 1px dashed color-mix(in srgb, var(--accent) 28%, var(--border));
        border-radius: 16px; padding: 3rem 2rem; text-align: center;
    }
    .cases-empty-icon { display: inline-flex; color: var(--accent); opacity: 0.7; margin-bottom: 1rem; }
    .cases-empty-title {
        font-family: var(--font-serif); font-size: 1.3125rem;
        color: var(--fg); font-weight: 500; margin: 0 0 0.5rem;
    }
    .cases-empty-sub {
        font-size: 0.9375rem; color: var(--fg-muted);
        margin: 0 auto 1.5rem; max-width: 50ch; line-height: 1.55;
    }
    .cases-pagination { margin-top: 1.5rem; }

    /* Card */
    .premium-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 1.875rem;
    }
    .premium-card-head { margin-bottom: 1.5rem; }
    .premium-card-title {
        font-family: var(--font-serif);
        font-size: 1.3125rem; font-weight: 500;
        color: var(--fg); letter-spacing: -0.01em;
        margin: 0 0 0.4rem;
    }
    .premium-card-sub {
        font-size: 0.875rem; color: var(--fg-muted);
        margin: 0; line-height: 1.55;
    }

    /* Form fields */
    .premium-form { display: flex; flex-direction: column; gap: 1.25rem; }
    .premium-field { display: flex; flex-direction: column; gap: 0.4rem; }
    .premium-field label {
        font-size: 0.8125rem; font-weight: 500;
        color: var(--fg); letter-spacing: 0.005em;
    }
    .premium-field-required { color: var(--danger); margin-left: 0.125rem; }
    .premium-field input,
    .premium-field textarea,
    .premium-field select {
        width: 100%; padding: 0.625rem 0.875rem;
        background: var(--bg);
        border: 1.5px solid var(--border); border-radius: 8px;
        color: var(--fg);
        font-family: var(--font-sans); font-size: 0.9375rem;
        transition: border-color 150ms ease-out, background 150ms ease-out;
    }
    .premium-field textarea { resize: vertical; min-height: 80px; line-height: 1.55; }
    .premium-field .is-mono {
        font-family: var(--font-mono, ui-monospace), monospace;
        font-size: 0.875rem;
    }
    .premium-field input:focus,
    .premium-field textarea:focus,
    .premium-field select:focus {
        outline: none; border-color: var(--accent); background: var(--surface);
    }
    .premium-field input::placeholder,
    .premium-field textarea::placeholder { color: color-mix(in srgb, var(--fg-muted) 70%, transparent); }
    .premium-field-help {
        font-size: 0.75rem; color: var(--fg-muted);
    }

    .premium-grid-2 { display: grid; grid-template-columns: 1fr; gap: 1.25rem; }
    @media (min-width: 720px) { .premium-grid-2 { grid-template-columns: 1fr 1fr; } }

    .premium-actions {
        display: flex; gap: 0.5rem; justify-content: flex-end;
        margin-top: 0.5rem; padding-top: 1.25rem;
        border-top: 1px solid var(--border);
    }

    .premium-error {
        background: color-mix(in srgb, var(--danger) 8%, transparent);
        border: 1px solid color-mix(in srgb, var(--danger) 30%, var(--border));
        border-radius: 8px; padding: 0.75rem 1rem; margin-bottom: 1.25rem;
        font-size: 0.875rem; color: var(--danger);
    }
    .premium-error p { margin: 0.125rem 0; }

    @media (prefers-reduced-motion: reduce) {
        .cases-btn-primary, .cases-btn-ghost { transition: none; }
        .cases-btn-primary:hover, .cases-btn-ghost:hover { transform: none; }
    }
</style>
@endonce
