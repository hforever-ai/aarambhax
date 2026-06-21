<x-layouts.app
    :title="$post->meta_title ?: $post->title.' — Aarambhax Legal'"
    :description="$post->meta_description ?: $post->excerpt_or_summary"
    ogType="article"
    :ogImage="$post->og_image_url ?: route('og.post', $post->slug)"
    :canonical="$post->canonical"
>
    @php
        $sibling = $post->translation_group_id
            ? \App\Models\Post::where('translation_group_id', $post->translation_group_id)
                ->where('id', '!=', $post->id)->first()
            : null;
    @endphp

    @if($sibling)
        @push('json-ld')
            <link rel="alternate" hreflang="{{ $post->language === 'hi' ? 'hi-IN' : 'en-IN' }}" href="{{ route('blog.show', $post->slug) }}">
            <link rel="alternate" hreflang="{{ $sibling->language === 'hi' ? 'hi-IN' : 'en-IN' }}" href="{{ route('blog.show', $sibling->slug) }}">
            <link rel="alternate" hreflang="x-default" href="{{ route('blog.show', $post->slug) }}">
        @endpush
    @endif

    @push('json-ld')
        <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $post->title,
            'description' => $post->meta_description ?: $post->excerpt_or_summary,
            'image' => $post->hero_image_url ? [$post->hero_image_url] : [],
            'datePublished' => optional($post->published_at)->toIso8601String(),
            'dateModified' => $post->updated_at->toIso8601String(),
            'author' => [
                '@type' => 'Person',
                'name' => $post->author->name ?? 'Aarambhax Editorial',
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Aarambhax Legal',
            ],
            'mainEntityOfPage' => $post->canonical,
            'inLanguage' => $post->language === 'hi' ? 'hi-IN' : 'en-IN',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
        </script>
        <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => route('blog.index')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $post->title, 'item' => $post->canonical],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
        </script>
    @endpush

    <article class="container-page py-12 max-w-3xl">
        <nav aria-label="Breadcrumb" class="mb-6 text-sm" style="color: var(--fg-muted);">
            <a href="{{ url('/') }}" style="color: var(--fg-muted);">Home</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('blog.index') }}" style="color: var(--fg-muted);">Blog</a>
            <span aria-hidden="true">/</span>
            <span style="color: var(--fg);">{{ Str::limit($post->title, 40) }}</span>
        </nav>

        <header class="mb-8">
            <div class="flex flex-wrap items-center gap-2 mb-4">
                @if($post->category)
                    <span class="badge badge-accent">{{ $post->category->name_en }}</span>
                @endif
                @if($post->language === 'hi')
                    <span class="badge" style="background: color-mix(in srgb, var(--link) 18%, transparent); color: var(--link);">हिंदी</span>
                @endif
                <span class="text-xs" style="color: var(--fg-muted);">{{ $post->reading_time_minutes ?? 5 }} min read</span>
            </div>

            <h1 class="text-3xl sm:text-5xl font-serif font-medium leading-[1.1] tracking-tight mb-4" style="color: var(--fg);">
                {{ $post->title }}
            </h1>

            @if($post->subtitle)
                <p class="text-xl" style="color: var(--fg-muted);">{{ $post->subtitle }}</p>
            @endif

            <div class="mt-6 pt-6 border-t flex items-center gap-3 text-sm" style="border-color: var(--border); color: var(--fg-muted);">
                @if($post->author)
                    <span>By <strong style="color: var(--fg);">{{ $post->author->name }}</strong></span>
                    <span aria-hidden="true">·</span>
                @endif
                <time datetime="{{ optional($post->published_at)->toIso8601String() }}">
                    {{ optional($post->published_at)->format('d F Y') }}
                </time>
            </div>
        </header>

        <img src="{{ $post->hero_image_or_default }}"
             alt="{{ $post->hero_image_alt ?? '' }}"
             class="w-full rounded-xl mb-8"
             width="1200" height="630"
             fetchpriority="high">


        <div class="prose-aarambhax" style="color: var(--fg);">
            {!! $post->body_html ?? \Illuminate\Support\Str::markdown($post->body) !!}
        </div>

        {{-- CTA --}}
        <div class="card mt-10" style="background: var(--surface-2); border-color: var(--accent);">
            <h3 class="font-serif text-xl font-semibold mb-2" style="color: var(--fg);">
                Generate this draft in 30 seconds.
            </h3>
            <p class="text-sm mb-4" style="color: var(--fg-muted);">
                Aarambhax produces a court-ready draft from your facts, with verified citations.
            </p>
            <a href="{{ url('/#waitlist') }}" class="btn btn-primary">Try Aarambhax Free</a>
        </div>

        @if($post->faqs->isNotEmpty())
            <x-faq-block :faqs="$post->faqs" heading="Common questions about this topic" />
        @endif
    </article>
</x-layouts.app>
