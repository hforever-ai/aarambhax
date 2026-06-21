@props([
    'icon' => '✦',
    'title',
    'description',
    'badge' => null,
])

<article class="card h-full">
    <div class="flex items-start justify-between mb-3">
        <span class="text-2xl" aria-hidden="true" style="color: var(--accent);">{{ $icon }}</span>
        @if($badge)
            <span class="badge badge-accent">{{ $badge }}</span>
        @endif
    </div>
    <h3 class="text-xl font-serif font-semibold mb-2" style="color: var(--fg);">{{ $title }}</h3>
    <p class="text-sm leading-relaxed" style="color: var(--fg-muted);">{{ $description }}</p>
</article>
