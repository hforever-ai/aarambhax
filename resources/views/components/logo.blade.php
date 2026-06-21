@props([
    'size' => 'md',
    'showTagline' => false,
    'markOnly' => false,
])

{{--
    Aarambhax Legal brand component.

    Sizes (mark / wordmark / total impression):
      sm   18px / 0.95rem   — footer, fine print
      md   24px / 1.05rem   — public marketing site nav
      lg   32px / 1.4rem    — public marketing hero
      xl   40px / 1.75rem   — chambers app top-bar (the size lawyers see all day)
      2xl  56px / 2.25rem   — auth pages, splash

    The wordmark and mark are sized as a *system*. If you change one, change both.
--}}

@php
    $markPx = match($size) {
        'sm'  => 18,
        'lg'  => 32,
        'xl'  => 40,
        '2xl' => 56,
        default => 24,
    };
    $wordRem = match($size) {
        'sm'  => 0.95,
        'lg'  => 1.4,
        'xl'  => 1.75,
        '2xl' => 2.25,
        default => 1.05,
    };
    $gapRem = match($size) {
        'sm'  => 0.4,
        'lg'  => 0.55,
        'xl'  => 0.7,
        '2xl' => 0.85,
        default => 0.5,
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center']) }} style="gap: {{ $gapRem }}rem; line-height: 1;">
    {{-- SVG mark — geometric "A" with gold crossbar. --}}
    <svg xmlns="http://www.w3.org/2000/svg"
         viewBox="0 0 64 64"
         width="{{ $markPx }}"
         height="{{ $markPx }}"
         style="flex-shrink: 0; color: var(--fg);"
         aria-hidden="true"
         focusable="false">
        <path d="M 32 8 L 14 56 L 22 56 L 32 28 Z" fill="currentColor"/>
        <path d="M 32 8 L 50 56 L 42 56 L 32 28 Z" fill="currentColor"/>
        <rect x="22" y="38" width="20" height="4" rx="0.5" fill="#C8A24B"/>
    </svg>

    @unless($markOnly)
        <span style="display: inline-flex; flex-direction: column; line-height: 1;">
            <span style="font-family: var(--font-serif); font-weight: 600; letter-spacing: -0.015em; color: var(--fg); font-size: {{ $wordRem }}rem;">
                Aarambha<span style="color: var(--accent);">x</span><span style="font-weight: 400; margin-left: 0.4em; color: var(--fg-muted);">Legal</span>
            </span>
            @if($showTagline)
                <span style="font-family: var(--font-sans); text-transform: uppercase; letter-spacing: 0.2em; margin-top: 0.45em; font-size: {{ max(10, $wordRem * 6) }}px; color: var(--fg-muted);">
                    Drafts for every court
                </span>
            @endif
        </span>
    @endunless
</span>
