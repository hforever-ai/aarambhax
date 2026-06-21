<x-layouts.app title="Accessibility — Aarambhax Legal" description="Our commitment to WCAG 2.2 AA accessibility for Indian advocates with disabilities.">
    <section class="container-page py-12 max-w-3xl">
        <h1 class="text-4xl sm:text-5xl font-serif font-medium mb-6" style="color: var(--fg);">Accessibility Statement</h1>

        <div class="prose-aarambhax" style="color: var(--fg);">
            <p><strong>Last reviewed:</strong> {{ date('F Y') }}</p>

            <h2>Our commitment</h2>
            <p>Aarambhax Legal aims to meet WCAG 2.2 Level AA conformance and India's Rights of Persons with Disabilities Act, 2016 (Section 40 and Accessibility Standards).</p>

            <h2>What we do</h2>
            <ul>
                <li>Semantic HTML5 so screen readers read pages correctly</li>
                <li>Keyboard-only navigation reachable on every interactive element</li>
                <li>Visible focus rings (gold, 2px) on all controls</li>
                <li>Skip link as first focusable element on every page</li>
                <li>Text contrast ratios of at least 4.5:1 for body, 3:1 for large text — verified in both themes</li>
                <li>All images have meaningful <code>alt</code> attributes; decorative images use <code>role="presentation"</code></li>
                <li>Form fields have visible <code>&lt;label&gt;</code> elements and validation messages associated via <code>aria-describedby</code></li>
                <li>Reduced-motion preference respected via <code>prefers-reduced-motion</code></li>
                <li>Hindi terms tagged with <code>lang="hi"</code> for correct screen-reader pronunciation</li>
            </ul>

            <h2>Known limitations</h2>
            <p>We're a small team and discover issues continuously. Currently we know:</p>
            <ul>
                <li>Some early blog posts may have heading-level skips that we are correcting</li>
                <li>Sample-draft PDFs are not yet tagged for screen readers (in progress)</li>
            </ul>

            <h2>Report an issue</h2>
            <p>If you encounter an accessibility barrier, email <a href="mailto:accessibility@aarambhax.net">accessibility@aarambhax.net</a>. We aim to respond within 5 business days.</p>
        </div>
    </section>
</x-layouts.app>
