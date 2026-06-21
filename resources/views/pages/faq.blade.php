<x-layouts.app
    title="Frequently Asked Questions — Aarambhax Legal"
    description="Common questions about BNS / BNSS / BSA, drafting, Revenue Court applications, court technology, and the Aarambhax product."
>
    <section class="container-page py-12">
        <header class="mb-10 max-w-3xl">
            <h1 class="text-4xl sm:text-5xl font-serif font-medium leading-tight mb-4" style="color: var(--fg);">
                Frequently Asked Questions
            </h1>
            <p class="text-lg" style="color: var(--fg-muted);">
                Common questions about Indian legal drafting, the new criminal codes (BNS / BNSS / BSA),
                Chhattisgarh Revenue Court practice, and the Aarambhax product.
            </p>
        </header>

        @if($faqs->isEmpty())
            <div class="card text-center py-12">
                <p style="color: var(--fg-muted);">FAQs are being added. Check back shortly.</p>
            </div>
        @else
            <x-faq-block :faqs="$faqs" heading="" :showSchema="true" />
        @endif
    </section>
</x-layouts.app>
