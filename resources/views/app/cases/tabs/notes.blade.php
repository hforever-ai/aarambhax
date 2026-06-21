{{-- Notes tab — personal notepad for the case. Free tier.
     Acts as a scratchpad; content saved here via "Add to Notes" from other tabs. --}}
@php
    $config = [
        'title' => 'Case Notes',
        'subtitle' => 'Your personal notepad for this case — capture key points, save from other tabs, ask Aarambh to organize.<br>Free tier · ₹0 per turn.',
        'placeholder' => 'e.g. "Summarize everything saved so far" or jot something down…',
        'tier_label' => 'FREE tier',
        'tier_color_var' => 'success',
        'send_url' => route('app.cases.chat.send', ['case' => $case, 'kind' => 'notes']),
        'show_lang_picker' => true,
        'hide_add_to_notes' => true,
        'quick_actions' => [
            ['icon' => '📋', 'label' => 'Summarize my notes',
             'prompt' => "Summarize everything I have saved in my notes for this case so far. Group by theme (e.g. facts, witnesses, arguments, documents). Keep it concise — a working reference I can carry into court."],
            ['icon' => '🔢', 'label' => 'Create a to-do list',
             'prompt' => "Based on all my notes for this case, create a prioritized to-do list — what needs to be done before the next hearing, what documents are missing, what arguments need to be prepared."],
            ['icon' => '📅', 'label' => 'Timeline of events',
             'prompt' => "Extract and organize all dates and events mentioned in my notes into a chronological timeline. Flag any gaps or inconsistencies."],
            ['icon' => '❓', 'label' => 'What am I missing?',
             'prompt' => "Looking at my notes so far, what key information, documents, or arguments seem to be missing? What should I be thinking about that isn't captured yet?"],
        ],
    ];
@endphp

@include('app.cases.tabs._chat_workspace', [
    'config' => $config,
    'case' => $case,
    'documents' => $documents,
    'conversation' => $conversation,
    'messages' => $messages,
])
