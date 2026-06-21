@php
    $u = auth()->user();
    if (! $u || $u->isApproved() || $u->isAdmin()) {
        return;
    }
    $isRejected = $u->isRejected();
@endphp

<div role="status" aria-live="polite"
     class="mb-6 rounded-lg p-4 sm:p-5 flex items-start gap-3"
     style="border: 1.5px solid {{ $isRejected ? 'var(--danger)' : 'var(--warning)' }};
            background: color-mix(in srgb, {{ $isRejected ? 'var(--danger)' : 'var(--warning)' }} 10%, var(--surface));">

    <div class="flex-shrink-0 mt-0.5"
         style="color: {{ $isRejected ? 'var(--danger)' : 'var(--warning)' }};">
        @if($isRejected)
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        @else
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        @endif
    </div>

    <div class="min-w-0 flex-1">
        @if($isRejected)
            <p class="font-medium" style="color: var(--fg);">
                Your account was not approved
            </p>
            <p class="text-sm mt-1" style="color: var(--fg-muted);">
                If you believe this is a mistake, contact
                <a href="mailto:admin@aarambhax.in" style="color: var(--link);">admin@aarambhax.in</a>.
            </p>
        @else
            <p class="font-medium" style="color: var(--fg);">
                Account pending approval
            </p>
            <p class="text-sm mt-1" style="color: var(--fg-muted);">
                Your account was created on {{ $u->created_at->format('d M Y') }}. An admin (Ajay or Vikash bhai)
                must approve you before you can use the platform. You can browse, but most actions are disabled.
                You'll receive an email once approved.
            </p>
        @endif
    </div>
</div>
