<x-layouts.app
    title="Blog — Aarambhax Legal"
    description="Practical drafting guides, BNS/BNSS section mappings, and Revenue Court walk-throughs for Indian advocates."
>
    <section class="container-page py-16">
        <header class="mb-12 max-w-3xl">
            <span class="badge badge-accent mb-4">Aarambhax Editorial</span>
            <h1 class="text-4xl sm:text-5xl font-serif font-medium leading-tight mb-4" style="color: var(--fg);">
                Practical drafting reference for working advocates.
            </h1>
            <p class="text-lg" style="color: var(--fg-muted);">
                Step-by-step drafting guides, BNS / BNSS / BSA section mappings, and Revenue Court walk-throughs.
                Bilingual where it matters. Verified citations always.
            </p>
        </header>

        @if($posts->isEmpty())
            <div class="card text-center py-12">
                <p class="text-lg mb-2" style="color: var(--fg);">First posts coming soon.</p>
                <p style="color: var(--fg-muted);">We're publishing the launch series now. Subscribe below to get notified.</p>
            </div>
        @else
            <ul class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" role="list">
                @foreach($posts as $post)
                    <li class="card h-full flex flex-col" style="padding: 0; overflow: hidden;">
                        <a href="{{ route('blog.show', $post->slug) }}" class="block" aria-hidden="true" tabindex="-1">
                            <img src="{{ $post->hero_image_or_default }}"
                                 alt=""
                                 class="w-full h-40 object-cover"
                                 loading="lazy"
                                 width="1200" height="630">
                        </a>
                        <div class="flex-1 flex flex-col p-5">
                            <div class="flex items-center gap-2 mb-3 flex-wrap">
                                @if($post->category)
                                    <span class="badge badge-accent">{{ $post->category->name_en }}</span>
                                @endif
                                <span class="text-xs" style="color: var(--fg-muted);">{{ $post->reading_time_minutes ?? 5 }} min read</span>
                                @if($post->language === 'hi')
                                    <span class="badge" style="background: color-mix(in srgb, var(--link) 18%, transparent); color: var(--link);">हिंदी</span>
                                @endif
                            </div>
                            <h2 class="font-serif text-xl font-semibold mb-2 leading-tight">
                                <a href="{{ route('blog.show', $post->slug) }}" style="color: var(--fg);">{{ $post->title }}</a>
                            </h2>
                            <p class="text-sm flex-1" style="color: var(--fg-muted);">{{ $post->excerpt_or_summary }}</p>
                            <div class="mt-4 pt-4 border-t flex items-center justify-between text-xs" style="border-color: var(--border); color: var(--fg-muted);">
                                <span>{{ optional($post->published_at)->format('d M Y') ?? 'Draft' }}</span>
                                <a href="{{ route('blog.show', $post->slug) }}" class="font-medium" style="color: var(--accent);">Read →</a>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="mt-10">
                {{ $posts->links() }}
            </div>
        @endif
    </section>
</x-layouts.app>
