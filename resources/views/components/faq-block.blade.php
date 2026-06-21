@props([
    'faqs' => [],
    'heading' => 'Frequently Asked Questions',
    'showSchema' => true,
])

@if($showSchema && count($faqs) > 0)
    @push('json-ld')
        <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type'    => 'FAQPage',
            'mainEntity' => collect($faqs)->map(fn($f) => [
                '@type' => 'Question',
                'name'  => $f->question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => strip_tags($f->answer_html ?? $f->answer),
                ],
            ])->values()->all(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
        </script>
    @endpush
@endif

<section aria-labelledby="faq-heading" class="my-16">
    <div class="container-page max-w-3xl">
        <h2 id="faq-heading" class="text-3xl sm:text-4xl font-serif font-semibold mb-8" style="color: var(--fg);">
            {{ $heading }}
        </h2>

        <ul role="list" class="space-y-3">
            @foreach($faqs as $faq)
                <li class="card" style="padding: 0;">
                    <details class="group">
                        <summary class="flex justify-between items-start gap-4 cursor-pointer list-none p-5">
                            <span class="font-serif text-lg font-medium" style="color: var(--fg);">
                                {{ $faq->question }}
                            </span>
                            <span class="shrink-0 mt-1 transition-transform group-open:rotate-180" aria-hidden="true" style="color: var(--accent);">▾</span>
                        </summary>
                        <div class="px-5 pb-5 prose-aarambhax" style="color: var(--fg-muted);">
                            {!! $faq->answer_html ?? nl2br(e($faq->answer)) !!}
                        </div>
                    </details>
                </li>
            @endforeach
        </ul>
    </div>
</section>
