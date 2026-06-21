<x-layouts.app
    :title="$sample['title'].' — Sample Draft — Aarambhax'"
    :description="'Sample legal draft — '.$sample['subtitle']"
>
    <section class="container-page py-10 max-w-3xl">
        <a href="{{ route('sample-drafts.index') }}" class="text-sm" style="color: var(--fg-muted);">← All sample drafts</a>

        <div class="mt-2 mb-6">
            <div class="flex items-center gap-2 mb-3 flex-wrap">
                <span class="badge badge-accent">{{ $sample['forum'] }}</span>
                <span class="badge" style="background: color-mix(in srgb, var(--link) 18%, transparent); color: var(--link);">{{ $sample['language'] }}</span>
            </div>
            <h1 class="text-3xl sm:text-4xl font-serif font-medium leading-tight" style="color: var(--fg);">{{ $sample['title'] }}</h1>
            <p class="mt-2 text-lg" style="color: var(--fg-muted);">{{ $sample['subtitle'] }}</p>
        </div>

        <div class="card mb-6" style="background: var(--surface-2);">
            <h2 class="text-sm font-semibold uppercase tracking-wide mb-2" style="color: var(--fg-muted);">Case facts used</h2>
            <p class="text-sm" style="color: var(--fg);"><strong>Parties:</strong> {{ $sample['parties'] }}</p>
            <p class="text-sm mt-1" style="color: var(--fg);"><strong>Sections cited:</strong> {{ implode(', ', $sample['sections']) }}</p>
        </div>

        <article class="card prose-aarambhax" style="color: var(--fg);">
            {!! \Illuminate\Support\Str::markdown($sample['preview']) !!}
        </article>

        <div class="card mt-8" style="background: var(--surface-2); border-color: var(--accent);">
            <h2 class="font-serif text-xl font-semibold mb-2" style="color: var(--fg);">Generate yours in 30 seconds.</h2>
            <p class="text-sm mb-4" style="color: var(--fg-muted);">
                Aarambhax produces drafts like this from your case facts, with verified citations and forum-specific format.
            </p>
            <div class="flex gap-3 flex-wrap">
                <a href="{{ route('register') }}" class="btn btn-primary">Try Aarambhax free</a>
                <a href="{{ route('verifier.show') }}" class="btn btn-secondary">Verify your own draft</a>
            </div>
        </div>
    </section>
</x-layouts.app>
