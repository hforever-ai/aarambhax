@once
<style>
    /* ── Shared styles for Analysis / Draft / Brainstorm tabs ── */

    .case-empty-state {
        text-align: center;
        padding: 1.5rem 1rem;
        background: color-mix(in srgb, var(--fg-muted) 4%, var(--surface));
        border-radius: 8px;
        font-size: 0.9375rem;
        color: var(--fg-muted);
    }
    .case-empty-state a {
        color: var(--link);
        font-weight: 500;
    }

    .analysis-form {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    .doc-picker {
        background: color-mix(in srgb, var(--accent) 3%, var(--surface));
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 1rem 1.25rem;
    }
    .doc-picker-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.625rem;
    }
    .doc-picker-label {
        font-size: 0.8125rem;
        color: var(--fg-muted);
        text-transform: uppercase;
        letter-spacing: 0.04em;
        font-weight: 500;
    }
    .doc-picker-toggle-all {
        background: none;
        border: none;
        color: var(--link);
        font-size: 0.8125rem;
        font-weight: 500;
        cursor: pointer;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        transition: background 150ms ease-out;
    }
    .doc-picker-toggle-all:hover {
        background: color-mix(in srgb, var(--link) 10%, transparent);
    }
    .doc-picker-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .doc-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.4rem 0.75rem;
        background: var(--surface);
        border: 1.5px solid var(--border);
        border-radius: 999px;
        cursor: pointer;
        transition: all 150ms ease-out;
        font-size: 0.8125rem;
    }
    .doc-chip:hover {
        border-color: color-mix(in srgb, var(--accent) 50%, var(--border));
    }
    .doc-chip input[type="checkbox"] {
        width: 14px;
        height: 14px;
        accent-color: var(--accent);
        cursor: pointer;
        margin: 0;
    }
    .doc-chip:has(input:checked) {
        background: color-mix(in srgb, var(--accent) 8%, var(--surface));
        border-color: var(--accent);
        color: var(--fg);
    }
    .doc-chip:not(:has(input:checked)) {
        opacity: 0.6;
    }
    .doc-chip-name {
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .analysis-instruction {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .analysis-instruction-label {
        font-size: 0.8125rem;
        color: var(--fg-muted);
        text-transform: uppercase;
        letter-spacing: 0.04em;
        font-weight: 500;
    }
    .analysis-instruction textarea {
        width: 100%;
        padding: 0.75rem 1rem;
        background: var(--surface);
        border: 1.5px solid var(--border);
        border-radius: 8px;
        color: var(--fg);
        font-family: var(--font-sans);
        font-size: 0.9375rem;
        resize: vertical;
        min-height: 60px;
        transition: border-color 150ms ease-out;
    }
    .analysis-instruction textarea:focus {
        outline: none;
        border-color: var(--accent);
    }

    .analysis-actions {
        display: flex;
        gap: 0.75rem;
        justify-content: flex-end;
        align-items: center;
    }

    /* Past works list */
    .past-works {
        margin-top: 2rem;
    }
    .past-works-title {
        font-family: var(--font-serif);
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--fg-muted);
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin: 0 0 0.875rem;
    }
    .past-work-row {
        display: flex !important;
        align-items: center;
        gap: 1rem;
        padding: 0.875rem 1.25rem !important;
        margin-top: 0 !important;
        margin-bottom: 0.5rem;
        text-decoration: none;
        color: inherit;
    }
    .past-work-icon {
        flex-shrink: 0;
        font-size: 1.25rem;
    }
    .past-work-main {
        flex: 1;
        min-width: 0;
    }
    .past-work-title {
        font-weight: 500;
        color: var(--fg);
        font-size: 0.9375rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .past-work-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.625rem;
        font-size: 0.75rem;
        color: var(--fg-muted);
        margin-top: 0.25rem;
        align-items: center;
    }
    .past-work-arrow {
        flex-shrink: 0;
        color: var(--fg-muted);
        font-size: 1.125rem;
        transition: transform 150ms ease-out;
    }
    .past-work-row:hover .past-work-arrow {
        transform: translateX(2px);
        color: var(--fg);
    }

    .tier-badge {
        display: inline-flex;
        align-items: center;
        font-size: 0.6875rem;
        font-weight: 600;
        letter-spacing: 0.04em;
        padding: 0.0625rem 0.5rem;
        border-radius: 4px;
    }
    .tier-free {
        background: color-mix(in srgb, var(--success) 12%, transparent);
        color: var(--success);
    }
    .tier-paid {
        background: color-mix(in srgb, var(--accent) 14%, transparent);
        color: var(--accent);
    }
    .pipeline-running { color: var(--accent); }
    .pipeline-failed { color: var(--danger); }
</style>

<script>
(function () {
    document.querySelectorAll('[data-toggle-all], #ana-toggle-all').forEach(btn => {
        btn.addEventListener('click', () => {
            const form = btn.closest('form');
            if (! form) return;
            const checkboxes = form.querySelectorAll('input[name="document_ids[]"]');
            const allChecked = Array.from(checkboxes).every(c => c.checked);
            checkboxes.forEach(c => { c.checked = ! allChecked; });
            btn.textContent = allChecked
                ? 'Select all (' + checkboxes.length + ')'
                : 'All (' + checkboxes.length + ')';
        });
    });
})();
</script>
@endonce
