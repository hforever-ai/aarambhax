<x-layouts.app-shell :title="$client->name.' — Aarambhax'">
    @include('app._partials.premium_styles')

    <section class="cases-page">
        <div class="container-page client-show-inner">
            <nav aria-label="Breadcrumb" class="form-page-breadcrumb">
                <a href="{{ route('app.clients.index') }}">← All clients</a>
            </nav>

            <header class="cases-header" style="margin-bottom: 2rem;">
                <div class="client-show-id">
                    <div class="client-show-avatar">{{ strtoupper(substr($client->name, 0, 1)) }}</div>
                    <h1 class="cases-title">{{ $client->name }}</h1>
                </div>
                <div class="client-show-actions">
                    <a href="{{ route('app.clients.edit', $client) }}" class="cases-btn-ghost">Edit</a>
                    <form method="POST" action="{{ route('app.clients.destroy', $client) }}" class="inline" onsubmit="return confirm('Delete this client? Linked cases will be unlinked.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="cases-btn-ghost client-delete-btn">Delete</button>
                    </form>
                </div>
            </header>

            @if(session('flash'))
                <div class="premium-error" style="border-color: color-mix(in srgb, var(--success) 30%, var(--border)); background: color-mix(in srgb, var(--success) 8%, transparent); color: var(--success);" role="status">
                    <p>{{ session('flash') }}</p>
                </div>
            @endif

            <div class="premium-card" style="margin-bottom: 1.5rem;">
                <div class="premium-card-head">
                    <h2 class="premium-card-title">Contact</h2>
                </div>
                <dl class="client-show-dl">
                    @if($client->phone)
                        <div><dt>Phone</dt><dd>{{ $client->phone }}</dd></div>
                    @endif
                    @if($client->email)
                        <div><dt>Email</dt><dd>{{ $client->email }}</dd></div>
                    @endif
                    @if($client->address)
                        <div><dt>Address</dt><dd style="white-space: pre-line;">{{ $client->address }}</dd></div>
                    @endif
                    @if(! $client->phone && ! $client->email && ! $client->address)
                        <p class="premium-card-sub" style="margin: 0;">No contact details on file.</p>
                    @endif
                </dl>
                @if($client->notes)
                    <div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--border);">
                        <p class="cases-eyebrow" style="margin-bottom: 0.5rem;">Notes</p>
                        <p style="color: var(--fg); white-space: pre-line; line-height: 1.55; margin: 0;">{{ $client->notes }}</p>
                    </div>
                @endif
            </div>

            <div>
                <div class="cases-header" style="margin-bottom: 1rem;">
                    <h2 class="cases-title" style="font-size: 1.3125rem;">Cases</h2>
                    <a href="{{ route('app.cases.create') }}" class="cases-btn-ghost">+ New case</a>
                </div>
                @if($client->cases->isEmpty())
                    <div class="cases-empty" style="padding: 2rem 1.5rem;">
                        <p class="cases-empty-sub" style="margin-bottom: 0;">No cases linked to this client yet.</p>
                    </div>
                @else
                    <ul class="cases-list" role="list">
                        @foreach($client->cases as $c)
                            <li>
                                <a href="{{ route('app.cases.show', $c) }}" class="cases-row">
                                    <div class="cases-row-main">
                                        <div class="cases-row-title-line">
                                            <h3 class="cases-row-title">{{ \Illuminate\Support\Str::limit($c->title, 80) }}</h3>
                                            @if(isset($c->status))
                                                <span class="cases-status cases-status-{{ $c->status === 'active' ? 'active' : 'closed' }}">{{ ucfirst($c->status) }}</span>
                                            @endif
                                        </div>
                                        <div class="cases-row-meta">
                                            @if($c->forum)<span>{{ str_replace('_', ' ', strtoupper($c->forum)) }}</span>@endif
                                            @if($c->case_no)<span class="cases-row-sep">·</span><span class="font-mono">{{ $c->case_no }}</span>@endif
                                        </div>
                                    </div>
                                    <span class="cases-row-arrow">→</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </section>

    <style>
        .client-show-inner { padding-top: 2.5rem; padding-bottom: 4rem; max-width: 880px; }
        .form-page-breadcrumb { font-size: 0.8125rem; color: var(--fg-muted); margin-bottom: 1rem; }
        .form-page-breadcrumb a { color: var(--fg-muted); text-decoration: none; transition: color 150ms ease-out; }
        .form-page-breadcrumb a:hover { color: var(--fg); }
        .client-show-id { display: flex; align-items: center; gap: 1rem; }
        .client-show-avatar {
            display: inline-flex; align-items: center; justify-content: center;
            width: 56px; height: 56px;
            background: color-mix(in srgb, var(--accent) 18%, transparent);
            color: var(--accent);
            font-family: var(--font-serif);
            font-size: 1.5rem; font-weight: 600;
            border-radius: 14px;
        }
        .client-show-actions { display: flex; gap: 0.5rem; }
        .client-delete-btn { color: var(--danger); }
        .client-delete-btn:hover { background: color-mix(in srgb, var(--danger) 8%, transparent); border-color: color-mix(in srgb, var(--danger) 30%, var(--border)); color: var(--danger); }

        .client-show-dl {
            display: grid; grid-template-columns: 140px 1fr; gap: 0.625rem 1.25rem;
            margin: 0; font-size: 0.9375rem;
        }
        .client-show-dl > div { display: contents; }
        .client-show-dl dt { color: var(--fg-muted); font-size: 0.8125rem; margin: 0.125rem 0 0; }
        .client-show-dl dd { color: var(--fg); margin: 0; }

        /* Cases list pattern (inlined for the cases under client) */
        .cases-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.625rem; }
        .cases-row {
            display: flex; align-items: center; justify-content: space-between;
            gap: 1.5rem; padding: 1.25rem 1.5rem;
            background: var(--surface); border: 1px solid var(--border); border-radius: 14px;
            text-decoration: none; color: inherit;
            transition: border-color 220ms ease-out, transform 220ms ease-out, box-shadow 220ms ease-out;
        }
        .cases-row:hover {
            border-color: color-mix(in srgb, var(--accent) 50%, var(--border));
            transform: translateY(-1px);
            box-shadow: 0 6px 22px -10px color-mix(in srgb, var(--primary) 22%, transparent);
        }
        .cases-row-main { flex: 1; min-width: 0; }
        .cases-row-title-line { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.4rem; flex-wrap: wrap; }
        .cases-row-title {
            font-family: var(--font-serif); font-size: 1.125rem; font-weight: 500;
            color: var(--fg); margin: 0; letter-spacing: -0.005em;
        }
        .cases-row-meta {
            display: flex; align-items: center; gap: 0.5rem;
            font-size: 0.8125rem; color: var(--fg-muted); flex-wrap: wrap;
        }
        .cases-row-meta .font-mono {
            font-size: 0.75rem; background: color-mix(in srgb, var(--fg) 5%, transparent);
            padding: 0.0625rem 0.4rem; border-radius: 4px; color: var(--fg);
        }
        .cases-row-arrow { color: var(--fg-muted); font-size: 1.25rem; transition: transform 200ms ease-out, color 200ms ease-out; }
        .cases-row:hover .cases-row-arrow { transform: translateX(3px); color: var(--accent); }

        @media (prefers-reduced-motion: reduce) {
            .cases-row, .cases-row-arrow { transition: none; }
            .cases-row:hover { transform: none; }
        }
    </style>
</x-layouts.app-shell>
