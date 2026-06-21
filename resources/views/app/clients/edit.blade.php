<x-layouts.app-shell :title="'Edit '.$client->name.' — Aarambhax'">
    @include('app._partials.premium_styles')

    <section class="cases-page">
        <div class="container-page form-page-inner">
            <nav aria-label="Breadcrumb" class="form-page-breadcrumb">
                <a href="{{ route('app.clients.show', $client) }}">← {{ $client->name }}</a>
            </nav>
            <header class="form-page-header">
                <h1 class="cases-title">Edit client</h1>
            </header>

            <div class="premium-card">
                <form method="POST" action="{{ route('app.clients.update', $client) }}">
                    @csrf
                    @method('PATCH')
                    @include('app.clients._fields', ['client' => $client])
                    <div class="premium-actions">
                        <a href="{{ route('app.clients.show', $client) }}" class="cases-btn-ghost">Cancel</a>
                        <button type="submit" class="cases-btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <style>
        .form-page-inner { padding-top: 2.5rem; padding-bottom: 4rem; max-width: 640px; }
        .form-page-header { margin-bottom: 1.75rem; }
        .form-page-breadcrumb { font-size: 0.8125rem; color: var(--fg-muted); margin-bottom: 1rem; }
        .form-page-breadcrumb a { color: var(--fg-muted); text-decoration: none; transition: color 150ms ease-out; }
        .form-page-breadcrumb a:hover { color: var(--fg); }
    </style>
</x-layouts.app-shell>
