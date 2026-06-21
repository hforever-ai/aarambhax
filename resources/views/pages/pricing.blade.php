<x-layouts.app title="Pricing — Aarambhax Legal" description="Free during beta. Honest pricing — substantially less than English-only legal AI competitors.">
    <section class="container-page py-12 max-w-5xl">
        <h1 class="text-4xl sm:text-5xl font-serif font-medium mb-6" style="color: var(--fg);">Pricing</h1>
        <p class="text-lg mb-10 max-w-3xl" style="color: var(--fg-muted);">
            Aarambhax is free during beta. After public launch, we'll keep pricing honest — substantially less than English-only competitors that don't support Hindi or Revenue Courts.
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-16">
            <div class="card">
                <span class="badge badge-success mb-3">Available now</span>
                <h2 class="font-serif text-2xl font-semibold mb-2">Beta</h2>
                <p class="text-3xl font-semibold mb-4" style="color: var(--fg);">Free</p>
                <ul class="space-y-2 text-sm" style="color: var(--fg-muted);">
                    <li>✓ Unlimited drafts</li>
                    <li>✓ Hindi + English</li>
                    <li>✓ Verifier on every draft</li>
                    <li>✓ Bare Act lookup</li>
                    <li>✓ Telegram reminders</li>
                </ul>
                <a href="{{ url('/#waitlist') }}" class="btn btn-primary mt-6 w-full">Join the waitlist</a>
            </div>

            <div class="card" style="background: var(--surface-2);">
                <span class="badge badge-accent mb-3">After launch</span>
                <h2 class="font-serif text-2xl font-semibold mb-2">Individual advocate</h2>
                <p class="text-3xl font-semibold mb-1" style="color: var(--fg);">~₹999<span class="text-base font-normal" style="color: var(--fg-muted);">/month</span></p>
                <p class="text-xs mb-4" style="color: var(--fg-muted);">Indicative. Beta users get launch discount.</p>
                <ul class="space-y-2 text-sm" style="color: var(--fg-muted);">
                    <li>✓ Everything in Beta</li>
                    <li>✓ Priority support</li>
                    <li>✓ Higher monthly draft limit</li>
                    <li>✓ Multimodal upload (FIR, judgments, khasra photos)</li>
                </ul>
            </div>
        </div>

        {{-- Competitive comparison --}}
        <h2 class="text-3xl font-serif font-medium mb-3" style="color: var(--fg);">How Aarambhax compares</h2>
        <p class="mb-8 max-w-3xl" style="color: var(--fg-muted);">
            Honest, factual comparison with the most popular Indian legal AI tools as of 2026. We've checked their public listings, app stores, and feature pages.
        </p>

        <div class="card" style="padding: 0; overflow: hidden;">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr style="background: var(--surface-2);">
                            <th class="text-left p-4 font-semibold" style="color: var(--fg);">Capability</th>
                            <th class="text-center p-4 font-semibold" style="color: var(--accent);">Aarambhax</th>
                            <th class="text-center p-4 font-medium" style="color: var(--fg-muted);">Lawttorney.ai</th>
                            <th class="text-center p-4 font-medium" style="color: var(--fg-muted);">Generic legal AI</th>
                        </tr>
                    </thead>
                    <tbody style="color: var(--fg);">
                        <tr style="border-top: 1px solid var(--border);">
                            <td class="p-4">Hindi (Devanagari) drafting</td>
                            <td class="text-center p-4" style="color: var(--success);">✓ Native</td>
                            <td class="text-center p-4" style="color: var(--fg-muted);">Roadmap only</td>
                            <td class="text-center p-4" style="color: var(--fg-muted);">English only</td>
                        </tr>
                        <tr style="border-top: 1px solid var(--border);">
                            <td class="p-4">Revenue Court drafting (CG LRC)</td>
                            <td class="text-center p-4" style="color: var(--success);">✓ Naamantaran, batwara, vyapvartan</td>
                            <td class="text-center p-4" style="color: var(--fg-muted);">Not supported</td>
                            <td class="text-center p-4" style="color: var(--fg-muted);">Not supported</td>
                        </tr>
                        <tr style="border-top: 1px solid var(--border);">
                            <td class="p-4">Forum-aware drafting (HC vs district vs revenue)</td>
                            <td class="text-center p-4" style="color: var(--success);">✓ Auto-format per forum</td>
                            <td class="text-center p-4" style="color: var(--fg-muted);">Generic templates</td>
                            <td class="text-center p-4" style="color: var(--fg-muted);">Generic</td>
                        </tr>
                        <tr style="border-top: 1px solid var(--border);">
                            <td class="p-4">Citation Verifier</td>
                            <td class="text-center p-4" style="color: var(--success);">✓ Green / amber / red badges</td>
                            <td class="text-center p-4" style="color: var(--success);">✓</td>
                            <td class="text-center p-4" style="color: var(--fg-muted);">Mostly absent</td>
                        </tr>
                        <tr style="border-top: 1px solid var(--border);">
                            <td class="p-4">Edit retains full case context</td>
                            <td class="text-center p-4" style="color: var(--success);">✓ Conversation memory</td>
                            <td class="text-center p-4" style="color: var(--danger);">Often loses context</td>
                            <td class="text-center p-4" style="color: var(--fg-muted);">Varies</td>
                        </tr>
                        <tr style="border-top: 1px solid var(--border);">
                            <td class="p-4">CG High Court &amp; CG district format</td>
                            <td class="text-center p-4" style="color: var(--success);">✓ Native</td>
                            <td class="text-center p-4" style="color: var(--fg-muted);">Generic Indian</td>
                            <td class="text-center p-4" style="color: var(--fg-muted);">Generic</td>
                        </tr>
                        <tr style="border-top: 1px solid var(--border);">
                            <td class="p-4">BNS / BNSS / BSA-aware drafting</td>
                            <td class="text-center p-4" style="color: var(--success);">✓ Auto-detects pre/post-July-2024</td>
                            <td class="text-center p-4" style="color: var(--success);">✓</td>
                            <td class="text-center p-4" style="color: var(--fg-muted);">Often outdated to IPC</td>
                        </tr>
                        <tr style="border-top: 1px solid var(--border);">
                            <td class="p-4">Multimodal upload (FIR, khasra, judgment PDF)</td>
                            <td class="text-center p-4" style="color: var(--success);">✓ Coming Phase 2</td>
                            <td class="text-center p-4" style="color: var(--fg-muted);">Limited</td>
                            <td class="text-center p-4" style="color: var(--fg-muted);">Varies</td>
                        </tr>
                        <tr style="border-top: 1px solid var(--border);">
                            <td class="p-4">Telegram reminders for hearings</td>
                            <td class="text-center p-4" style="color: var(--success);">✓ Free, unlimited</td>
                            <td class="text-center p-4" style="color: var(--fg-muted);">No</td>
                            <td class="text-center p-4" style="color: var(--fg-muted);">No</td>
                        </tr>
                        <tr style="border-top: 1px solid var(--border); background: var(--surface-2);">
                            <td class="p-4 font-semibold">Indicative price</td>
                            <td class="text-center p-4 font-semibold" style="color: var(--accent);">~₹999/mo</td>
                            <td class="text-center p-4" style="color: var(--fg-muted);">₹2,999–3,999/mo</td>
                            <td class="text-center p-4" style="color: var(--fg-muted);">₹2,000–5,000/mo</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <p class="mt-6 text-xs" style="color: var(--fg-muted);">
            Comparisons sourced from public app store listings and product pages as of May 2026. Tools evolve quickly — please verify on each vendor's site before deciding. Aarambhax features marked "coming" are firm roadmap items, not vapourware.
        </p>
    </section>
</x-layouts.app>
