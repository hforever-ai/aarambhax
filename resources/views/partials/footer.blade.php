<footer class="mt-20 border-t" style="border-color: var(--border); background: var(--surface-2);" aria-label="Footer">
    <div class="container-page py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div class="md:col-span-1">
                <x-logo size="md" :showTagline="true" />
                <p class="mt-3 text-sm" style="color: var(--fg-muted);">
                    AI-powered drafts for Indian advocates. Hindi + English.
                    Built for Chhattisgarh courts.
                </p>
            </div>

            <div>
                <h3 class="text-sm font-semibold mb-3" style="font-family: var(--font-sans); letter-spacing: 0.05em; text-transform: uppercase;">Product</h3>
                <ul class="space-y-2 text-sm" role="list">
                    <li><a href="{{ url('/') }}#features" style="color: var(--fg-muted);">Features</a></li>
                    <li><a href="{{ url('/') }}#sample-drafts" style="color: var(--fg-muted);">Sample Drafts</a></li>
                    <li><a href="{{ route('pages.pricing') }}" style="color: var(--fg-muted);">Pricing</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-sm font-semibold mb-3" style="font-family: var(--font-sans); letter-spacing: 0.05em; text-transform: uppercase;">Resources</h3>
                <ul class="space-y-2 text-sm" role="list">
                    <li><a href="{{ route('blog.index') }}" style="color: var(--fg-muted);">Blog</a></li>
                    <li><a href="{{ route('faq.index') }}" style="color: var(--fg-muted);">FAQ</a></li>
                    <li><a href="{{ route('pages.about') }}" style="color: var(--fg-muted);">About us</a></li>
                    <li><a href="{{ route('pages.vision') }}" style="color: var(--fg-muted);">Vision</a></li>
                    <li><a href="{{ route('pages.contact') }}" style="color: var(--fg-muted);">Contact</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-sm font-semibold mb-3" style="font-family: var(--font-sans); letter-spacing: 0.05em; text-transform: uppercase;">Legal</h3>
                <ul class="space-y-2 text-sm" role="list">
                    <li><a href="{{ route('pages.privacy') }}" style="color: var(--fg-muted);">Privacy</a></li>
                    <li><a href="{{ route('pages.terms') }}" style="color: var(--fg-muted);">Terms</a></li>
                    <li><a href="{{ route('pages.accessibility') }}" style="color: var(--fg-muted);">Accessibility</a></li>
                </ul>
            </div>
        </div>

        <div class="mt-10 pt-6 border-t flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3" style="border-color: var(--border);">
            <p class="text-xs" style="color: var(--fg-muted);">
                © {{ date('Y') }} Aarambhax Legal. AI-generated drafts require advocate review and verification. Not a substitute for legal advice.
            </p>
            <p class="text-xs" style="color: var(--fg-muted);">
                Built in India for Indian advocates.
            </p>
        </div>
    </div>
</footer>
