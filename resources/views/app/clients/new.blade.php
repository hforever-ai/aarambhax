<x-layouts.app-shell title="New client — Aarambhax">
    @include('app._partials.premium_styles')

    <section class="cases-page">
        <div class="container-page form-page-inner">
            <header class="form-page-header">
                <p class="cases-eyebrow">Workspace</p>
                <h1 class="cases-title">New client</h1>
                <p class="cases-subtitle">Used to link multiple cases to the same person and pre-fill draft fields.</p>
            </header>

            <div class="premium-card">
                <form method="POST" action="{{ route('app.clients.store') }}">
                    @csrf
                    @include('app.clients._fields', ['client' => null])
                    <div class="premium-actions">
                        <a href="{{ route('app.clients.index') }}" class="cases-btn-ghost">Cancel</a>
                        <button type="submit" class="cases-btn-primary">Save client</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <style>
        .form-page-inner { padding-top: 2.5rem; padding-bottom: 4rem; max-width: 640px; }
        .form-page-header { margin-bottom: 1.75rem; }
    </style>
</x-layouts.app-shell>
