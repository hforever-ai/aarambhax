<x-layouts.app-shell title="Add hearing — Aarambhax">
    @include('app._partials.premium_styles')

    <section class="cases-page">
        <div class="container-page form-page-inner">
            <header class="form-page-header">
                <p class="cases-eyebrow">Workspace</p>
                <h1 class="cases-title">Add hearing</h1>
                <p class="cases-subtitle">Telegram reminder fires 24 hours before the hearing date if you've paired your Telegram account.</p>
            </header>

            @if($cases->isEmpty())
                <div class="cases-empty">
                    <p class="cases-empty-title">You need an active case first</p>
                    <p class="cases-empty-sub">Hearings are linked to a case. Create one to get started.</p>
                    <a href="{{ route('app.cases.create') }}" class="cases-btn-primary">Create a case</a>
                </div>
            @else
                <div class="premium-card">
                    <form method="POST" action="{{ route('app.hearings.store') }}" class="premium-form">
                        @csrf

                        <div class="premium-field">
                            <label for="case_id">Case <span class="premium-field-required">*</span></label>
                            <select id="case_id" name="case_id" required>
                                @foreach($cases as $c)
                                    <option value="{{ $c->id }}" {{ $selectedCaseId == $c->id ? 'selected' : '' }}>
                                        {{ $c->title }}{{ $c->case_no ? ' ('.$c->case_no.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="premium-grid-2">
                            <div class="premium-field">
                                <label for="date">Date <span class="premium-field-required">*</span></label>
                                <input id="date" type="date" name="date" required
                                       value="{{ today()->addDay()->format('Y-m-d') }}">
                            </div>
                            <div class="premium-field">
                                <label for="time">Time <span class="premium-field-help" style="font-weight: 400;">(optional)</span></label>
                                <input id="time" type="time" name="time">
                            </div>
                        </div>

                        <div class="premium-field">
                            <label for="purpose">Purpose</label>
                            <input id="purpose" type="text" name="purpose" maxlength="200"
                                   placeholder="e.g. Bahas / Charge framing / Final arguments">
                        </div>

                        <div class="premium-field">
                            <label for="court">Courtroom / bench <span class="premium-field-help" style="font-weight: 400;">(optional)</span></label>
                            <input id="court" type="text" name="court" maxlength="200"
                                   placeholder="Court Room 4">
                        </div>

                        <div class="premium-actions">
                            <a href="{{ route('app.hearings.index') }}" class="cases-btn-ghost">Cancel</a>
                            <button type="submit" class="cases-btn-primary">Add hearing</button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </section>

    <style>
        .form-page-inner { padding-top: 2.5rem; padding-bottom: 4rem; max-width: 640px; }
        .form-page-header { margin-bottom: 1.75rem; }
    </style>
</x-layouts.app-shell>
