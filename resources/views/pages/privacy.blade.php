<x-layouts.app title="Privacy Policy — Aarambhax Legal" description="How Aarambhax handles advocate and case data.">
    <section class="container-page py-12 max-w-3xl">
        <h1 class="text-4xl sm:text-5xl font-serif font-medium mb-6" style="color: var(--fg);">Privacy Policy</h1>

        <div class="prose-aarambhax" style="color: var(--fg);">
            <p><strong>Last updated:</strong> {{ date('F Y') }}</p>

            <h2>What we collect</h2>
            <ul>
                <li>Account info: name, email, Bar Council number (optional), phone</li>
                <li>Drafts and case context you create on the platform</li>
                <li>Usage analytics — counts only, never content</li>
            </ul>

            <h2>How we use it</h2>
            <p>To provide drafting features, send hearing reminders, and improve the product. We do not sell your data.</p>

            <h2>Where it lives</h2>
            <p>Hosted on servers in India. Encrypted at rest. Backups stored encrypted at Cloudflare R2.</p>

            <h2>Third-party AI processing</h2>
            <p>To generate drafts, your case facts and prompts are sent to Google Gemini. During the beta period (which uses Google's free tier), prompts may be used by Google to improve their models. After we move to a paid tier, prompts will be fully private. We display a clear notice on every upload screen.</p>

            <h2>Your rights</h2>
            <p>Delete your account at any time and all your data is permanently removed within 30 days. Email <a href="mailto:privacy@aarambhax.net">privacy@aarambhax.net</a>.</p>
        </div>
    </section>
</x-layouts.app>
