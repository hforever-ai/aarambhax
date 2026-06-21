<x-layouts.app
    title="Aarambhax Legal — Drafts for every court in Chhattisgarh"
    description="AI-powered legal drafting for CG High Court, District Courts, and Revenue Courts. Native Hindi support, verified citations, built for working advocates."
>
    {{-- Hero --}}
    <section class="container-page pt-16 pb-20" aria-labelledby="hero-heading">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
            <div class="lg:col-span-7">
                <span class="badge badge-accent mb-5">Built for Chhattisgarh advocates</span>
                <h1 id="hero-heading" class="text-4xl sm:text-5xl lg:text-6xl font-serif font-medium leading-[1.1] tracking-tight" style="color: var(--fg);">
                    Legal drafts for every court.
                    <span style="color: var(--fg-muted);">In Hindi, in English. In 30 seconds.</span>
                </h1>
                <p class="mt-6 text-lg max-w-2xl" style="color: var(--fg-muted);">
                    AI-powered drafting tuned for Chhattisgarh High Court, District &amp; Sessions courts, and Revenue Courts.
                    Verified citations. Native Devanagari output. CG LRC ready.
                </p>
                <p class="mt-3 text-sm max-w-2xl" style="color: var(--fg-muted);">
                    The Hindi + Revenue Court drafting tool other Indian legal AI platforms don't build.
                </p>
                <div class="mt-8 flex flex-wrap items-center gap-4">
                    <a href="#waitlist" class="btn btn-primary">Try Free — 5 Drafts</a>
                    <a href="#sample-drafts" class="btn btn-secondary">See Sample Drafts</a>
                </div>
                <p class="mt-5 text-xs" style="color: var(--fg-muted);">
                    No credit card. Use it on a real case.
                </p>
            </div>

            <div class="lg:col-span-5">
                {{-- Hero illustration: open book + draft document with verified citation --}}
                <img src="{{ asset('images/hero-illustration.svg') }}"
                     alt="Stylised illustration of an open law book and a legal draft with a verified citation badge"
                     class="w-full h-auto"
                     loading="eager"
                     width="600" height="480">
            </div>
        </div>
    </section>

    {{-- Trust strip --}}
    <section class="border-y" style="border-color: var(--border); background: var(--surface-2);" aria-label="Capabilities at a glance">
        <div class="container-page py-6">
            <div class="flex flex-wrap items-center justify-center gap-x-8 gap-y-3 text-sm" style="color: var(--fg-muted);">
                <span>⚖️ CG High Court Bilaspur</span>
                <span>🏛️ District &amp; Sessions courts</span>
                <span>📜 CG Land Revenue Code ready</span>
                <span>🇮🇳 Native Devanagari</span>
                <span>✓ Verified citations</span>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section id="features" class="container-page py-20" aria-labelledby="features-heading">
        <h2 id="features-heading" class="text-3xl sm:text-4xl font-serif font-medium mb-3" style="color: var(--fg);">
            Built for the way Indian advocates actually work.
        </h2>
        <p class="text-lg mb-12 max-w-2xl" style="color: var(--fg-muted);">
            Forum-aware, language-aware, citation-verified. Every feature targets a real pain point in your daily practice.
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <x-feature-card
                icon="✍"
                title="Drafting that knows the forum"
                description="HC writs in English, district pleadings in Hindi, revenue applications with khasra/khata structure. Auto-detected from your court selection."
            />
            <x-feature-card
                icon="✓"
                title="Citations you can trust"
                description="Every section and judgment is verified against the actual statute and Indian Kanoon database. Green, amber, red badges show what's solid."
            />
            <x-feature-card
                icon="📚"
                title="Bare Acts at your fingertips"
                description="BNS, BNSS, BSA, CrPC, IPC, CG LRC, NI Act and more — searchable, with plain-language explanations and historical amendments."
            />
            <x-feature-card
                icon="💬"
                title="Edit by chatting"
                description="Tell the AI 'tighten this paragraph' or 'add a ground about delay'. It edits with full memory of your case facts — no context loss."
            />
            <x-feature-card
                icon="📄"
                title="Chat with judgments"
                description="Upload a 600-page judgment, FIR, or charge-sheet. Ask questions in plain Hindi or English. Get cited answers."
                badge="Coming soon"
            />
            <x-feature-card
                icon="🔔"
                title="Telegram reminders"
                description="Tomorrow's hearings, hand-delivered. Free. Unlimited. No setup beyond a single /start command."
            />
        </div>
    </section>

    {{-- Sample drafts gallery --}}
    <section id="sample-drafts" class="border-t" style="border-color: var(--border); background: var(--surface-2);" aria-labelledby="samples-heading">
        <div class="container-page py-20">
            <h2 id="samples-heading" class="text-3xl sm:text-4xl font-serif font-medium mb-3" style="color: var(--fg);">
                See real output, not promises.
            </h2>
            <p class="text-lg mb-12 max-w-2xl" style="color: var(--fg-muted);">
                Three drafts generated by Aarambhax in under a minute each. Each one comes with verified citations and the original facts you provided.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div class="card">
                    <span class="badge badge-accent mb-3">English · CG HC</span>
                    <h3 class="font-serif text-lg font-semibold mb-2">Writ Petition (Civil) under Article 226</h3>
                    <p class="text-sm" style="color: var(--fg-muted);">Service matter — pension dispute. Full memo of parties, synopsis, list of dates.</p>
                </div>
                <div class="card">
                    <span class="badge badge-accent mb-3">Hindi · District</span>
                    <h3 class="font-serif text-lg font-semibold mb-2">अग्रिम जमानत आवेदन</h3>
                    <p class="text-sm" style="color: var(--fg-muted);">BNSS §482 के अंतर्गत अग्रिम जमानत — पूर्ण आधार और साक्ष्य।</p>
                </div>
                <div class="card">
                    <span class="badge badge-accent mb-3">Hindi · Revenue</span>
                    <h3 class="font-serif text-lg font-semibold mb-2">नामांतरण आवेदन (CG LRC §109)</h3>
                    <p class="text-sm" style="color: var(--fg-muted);">उत्तराधिकार के आधार पर नामांतरण — खसरा, खाता, खतौनी सहित।</p>
                </div>
            </div>

            <p class="mt-8 text-sm" style="color: var(--fg-muted);">
                <em>Sample PDFs coming soon. Sign up below and we'll send you the full samples when they go live.</em>
            </p>
        </div>
    </section>

    {{-- Featured FAQs --}}
    @if(isset($faqs) && count($faqs) > 0)
        <x-faq-block :faqs="$faqs" heading="Common questions" />
    @endif

    {{-- Waitlist CTA --}}
    <section id="waitlist" class="container-page py-20" aria-labelledby="waitlist-heading">
        <div class="card max-w-2xl mx-auto text-center" style="background: var(--surface);">
            <h2 id="waitlist-heading" class="text-2xl sm:text-3xl font-serif font-medium mb-3" style="color: var(--fg);">
                Join the early-access list.
            </h2>
            <p class="text-base mb-6" style="color: var(--fg-muted);">
                We'll email you when Aarambhax opens for beta. No spam, no marketing — just one launch email.
            </p>
            <form action="{{ route('newsletter.subscribe') }}" method="POST" class="flex flex-col sm:flex-row gap-3 max-w-md mx-auto">
                @csrf
                <label for="newsletter-email" class="sr-only">Email address</label>
                <input
                    id="newsletter-email"
                    type="email"
                    name="email"
                    required
                    placeholder="advocate@example.in"
                    class="flex-1 px-4 py-3 rounded-md text-base"
                    style="background: var(--bg); border: 1.5px solid var(--border); color: var(--fg);"
                >
                <button type="submit" class="btn btn-primary">Notify me</button>
            </form>
        </div>
    </section>
</x-layouts.app>
