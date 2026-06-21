{{-- One conversation turn — rendered server-side for messages already in DB --}}
<div class="bs-turn {{ $msg->role === 'user' ? 'user' : 'assistant' }}">
    <div class="bs-turn-head">
        <span class="bs-turn-icon">{{ $msg->role === 'user' ? 'Y' : 'A' }}</span>
        <span>
            {{ $msg->role === 'user' ? 'You' : 'Aarambh' }} ·
            {{ $msg->created_at->diffForHumans() }}
        </span>
    </div>
    <div class="bs-turn-body">
        @if($msg->role === 'user')
            {{ $msg->content }}
        @else
            @php
                $content = (string) ($msg->content ?? '');
                $isBilingual = str_contains($content, '<<<ENGLISH>>>') && str_contains($content, '<<<HINDI>>>');
            @endphp
            @if($isBilingual)
                @php
                    [$beforeEn, $rest] = explode('<<<ENGLISH>>>', $content, 2);
                    [$enPart, $hiPart] = explode('<<<HINDI>>>', $rest, 2);
                @endphp
                <div class="bs-bilingual">
                    <div class="bs-bilingual-col">
                        <div class="bs-bilingual-label">English</div>
                        <div>{!! \Illuminate\Support\Str::of(trim($enPart))->markdown(['html_input' => 'escape']) !!}</div>
                    </div>
                    <div class="bs-bilingual-col">
                        <div class="bs-bilingual-label">हिन्दी</div>
                        <div>{!! \Illuminate\Support\Str::of(trim($hiPart))->markdown(['html_input' => 'escape']) !!}</div>
                    </div>
                </div>
            @else
                {!! \Illuminate\Support\Str::of($content)->markdown(['html_input' => 'escape']) !!}
            @endif
        @endif
    </div>
    @if($msg->role === 'assistant')
        @php($isFree = str_contains((string) $msg->model_used, 'lite'))
        @php($conv = $msg->conversation)
        <div class="bs-turn-foot">
            <span class="bs-turn-foot-pill {{ $isFree ? '' : 'bs-turn-foot-pill-paid' }}">{{ $isFree ? 'FREE' : 'PAID' }}</span>
            <span>{{ number_format(($msg->tokens_input ?? 0) + ($msg->tokens_output ?? 0)) }} tokens</span>
            @if($msg->model_used)
                <span>{{ $msg->model_used }}</span>
            @endif
            <span class="bs-turn-actions">
                <button type="button" class="bs-turn-action" data-copy="{{ $msg->id }}" title="Copy markdown to clipboard" aria-label="Copy">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                    Copy
                </button>
                @if($conv && $conv->kind !== 'draft')
                    <form method="POST" action="{{ route('app.cases.chat.copy-to-draft', [$case, $msg]) }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="bs-turn-action" title="Send this answer to the Draft tab as a starting message">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            Add to Draft
                        </button>
                    </form>
                @endif
            </span>
        </div>
        <textarea data-copy-source="{{ $msg->id }}" hidden>{{ $msg->content }}</textarea>
    @endif
</div>
