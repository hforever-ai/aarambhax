{{-- Samjhao tab — Hinglish client explainer. Free tier. --}}
@php
    $config = [
        'title' => 'Explain this case to the client',
        'subtitle' => 'Hinglish client explainers, WhatsApp-ready messages, plain-language updates.<br>Free tier · PII-redacted · ₹0 per turn.',
        'placeholder' => 'e.g. "Aman bhai ko samjhao kya hua aaj court mein"',
        'tier_label' => 'FREE tier',
        'tier_color_var' => 'success',
        'send_url' => route('app.cases.chat.send', ['case' => $case, 'kind' => 'samjhao']),
        'quick_actions' => [
            ['icon' => '💬', 'label' => "Explain the latest order in Hinglish",
             'prompt' => "Explain the most recent court order to the client in Hinglish (Devanagari + roman script mixed, the way Indian advocates actually talk to clients). Cover: what happened, why it matters, what we'll do next, watch-outs. 8-12 sentences, casual register, but accurate on legal points."],
            ['icon' => '📱', 'label' => "WhatsApp-ready summary",
             'prompt' => "Write a 4-6 line WhatsApp message I can paste to the client. Casual Hinglish register, first-person from advocate to client. Cover what happened today and what they need to do next (bring documents, attend hearing, deposit money, etc)."],
            ['icon' => '📝', 'label' => "What does this mean for me? (action items)",
             'prompt' => "Translate the case status into 3-5 concrete action items for the client. Each should be one sentence in Hinglish, telling them exactly what to do and by when. No legal jargon — write like you're talking to them in chambers."],
            ['icon' => '🎯', 'label' => "Next hearing — what to expect",
             'prompt' => "In Hinglish, explain to the client what will happen at the next hearing. What's the bench going to do, what should they wear, do they need to bring anything, what's likely to be the outcome. Keep it 6-8 lines, casual but clear."],
            ['icon' => '💰', 'label' => "Money/cost explainer",
             'prompt' => "If the case involves money (court fee, decree amount, deposit, attachment) — explain in Hinglish exactly how much, when, and to whom. If no money issue is in the file, say so clearly."],
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
