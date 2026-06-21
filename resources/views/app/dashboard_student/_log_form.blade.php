<form method="POST" action="{{ route('app.daily-log.store') }}" class="snd-log-form">
    @csrf
    <input type="text" name="studied_topics" class="snd-log-input"
           placeholder="Kya padha? (e.g. Newton's laws, Integration)"
           value="{{ $todayLog?->studied_topics }}" maxlength="500">

    <div class="snd-two">
        <input type="number" name="hours_studied" class="snd-log-input"
               placeholder="Kitne ghante?" step="0.5" min="0" max="24"
               value="{{ $todayLog?->hours_studied }}">
        <input type="text" name="expenses" class="snd-log-input"
               placeholder="₹ Kharch?" value="{{ $todayLog?->expenses ? (int)$todayLog->expenses : '' }}" maxlength="10">
    </div>

    <input type="text" name="food" class="snd-log-input"
           placeholder="Khaana kaisa tha? (optional)" value="{{ $todayLog?->food }}" maxlength="300">

    <div class="snd-mood-row">
        <span class="snd-mood-label">Mood:</span>
        @foreach([1 => '😫', 2 => '😐', 3 => '🙂', 4 => '😊', 5 => '🔥'] as $val => $emoji)
            <input type="radio" name="mood" id="mood{{ $val }}" value="{{ $val }}"
                   class="snd-mood-radio" {{ $todayLog?->mood == $val ? 'checked' : '' }}>
            <label for="mood{{ $val }}" class="snd-mood-btn">{{ $emoji }}</label>
        @endforeach
    </div>

    <button type="submit" class="snd-log-submit">Save log</button>
</form>
