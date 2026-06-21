{{-- Brainstorm tab — thin wrapper around _chat_workspace partial.
     Open Q&A with the case file. Free tier, PII-redacted. --}}
@php
    $config = [
        'title' => 'Talk through this case with Aarambh',
        'subtitle' => 'Free tier · PII-redacted · ₹0 per turn.<br>Pick documents on the left to focus the conversation.',
        'placeholder' => 'Ask anything about this case…',
        'tier_label' => 'FREE tier',
        'tier_color_var' => 'success',
        'send_url' => route('app.cases.chat.send', ['case' => $case, 'kind' => 'brainstorm']),
        'show_lang_picker' => true,
        'quick_actions' => [
            '_identify' => [
                ['icon' => '❓', 'label' => 'Identify case & strategy',
                 'prompt' => "Analyze the uploaded case documents to identify the precise category and sub-category of this case (e.g., criminal/bail, civil/property, family/divorce, revenue/mutation). Based on this, propose three distinct strategic approaches, weighing their pros and cons. Outline the immediate next steps and potential long-term goals for each strategy."],
            ],
            '_all' => [
                ['icon' => '⚖️', 'label' => "What's our strongest argument?",
                 'prompt' => "Based on the case documents, what is our single strongest legal argument? Cite specific sections and judgments where applicable. Then list the top 3 vulnerabilities in that argument so I know what to prepare for."],
                ['icon' => '📚', 'label' => 'Suggest precedents',
                 'prompt' => "Suggest 3–5 precedents (HC + SC) that support our position based on the case file. Mark each as [CITATION NEEDED — verify] since unverified. For each, explain in one sentence why it helps us."],
            ],
            'criminal' => [
                ['icon' => '🚓', 'label' => 'FIR quash strategy',
                 'prompt' => "Devise a comprehensive strategy for quashing the FIR in this criminal case. Explore options under Section 482 BNSS, 2023, or through compounding. Identify necessary steps, required documents, and potential legal challenges, including possible settlement options for compoundable offences."],
                ['icon' => '🏛️', 'label' => 'Trial defense strategy',
                 'prompt' => "Outline a robust defense strategy for the upcoming criminal trial based on the case file. Identify key prosecution witnesses to cross-examine, essential defense witnesses, and crucial documentary evidence to present. Focus on creating reasonable doubt and exploiting procedural lapses under BNSS, 2023."],
                ['icon' => '⚖️', 'label' => 'Appeal/revision strategy',
                 'prompt' => "Formulate a strategy for appeal or revision against the conviction or adverse order in this case. Identify key grounds for appeal (legal errors, misappreciation of evidence), procedural requirements, and potential for sentence reduction. Cite relevant sections of BNSS, 2023."],
                ['icon' => '🤝', 'label' => 'Plea bargaining advice',
                 'prompt' => "Explain the process and implications of plea bargaining under Chapter XXI-A of BNSS, 2023, for this specific case. Advise on whether it's a viable option considering the charges, evidence, and potential sentence reduction. Outline the steps, eligibility, and potential benefits and risks."],
            ],
            'civil' => [
                ['icon' => '💼', 'label' => 'Suit filing strategy',
                 'prompt' => "Develop a detailed strategy for filing this civil suit. Advise on the correct court, necessary parties, required documents, and potential interim reliefs. Identify anticipated objections from the defendant and how to preemptively address them, citing CPC provisions."],
                ['icon' => '📋', 'label' => 'Summarize this case',
                 'prompt' => "Summarize this civil case in plain language — what happened, the key legal issues, the current stage, and what the best next steps are. Use the document context. Keep it concise so I can brief a junior quickly."],
                ['icon' => '📜', 'label' => 'Execution of decree strategy',
                 'prompt' => "Outline a strategy for the effective execution of the decree in this civil matter. Identify potential hurdles, applicable modes of execution (attachment, sale of property, arrest), and required applications under Order XXI CPC. Advise on tracing assets of the judgment debtor."],
                ['icon' => '⏳', 'label' => 'Limitation period analysis',
                 'prompt' => "Analyze the applicable limitation period for filing this civil suit or appeal, referencing the Limitation Act, 1963. Advise on whether the claim is time-barred and explore any grounds for condonation of delay, citing relevant sections. Check all documents for dates."],
            ],
            'family' => [
                ['icon' => '🕊️', 'label' => 'Amicable settlement options',
                 'prompt' => "Explore different avenues for amicable settlement in this matrimonial dispute, including mediation through court-referred channels or private mediation. Identify key negotiation points, potential compromises, and how to draft a legally binding settlement agreement. Focus on child welfare and financial implications."],
                ['icon' => '📑', 'label' => 'Evidence for divorce/maintenance',
                 'prompt' => "Identify crucial evidence required to establish grounds for divorce or claim maintenance based on the case file. List specific documents, witness testimonies, and digital evidence needed. Advise on how to collect and present this evidence effectively under the Hindu Marriage Act, 1955, or Section 125 BNSS."],
                ['icon' => '👶', 'label' => 'Custody battle strategy',
                 'prompt' => "Develop a strategic plan for the child custody matter in this case. Focus on demonstrating the applicant's suitability, the child's wishes (if applicable), and stability. Advise on psychological evaluations, school records, and character witnesses. Cite the Guardians and Wards Act, 1890."],
                ['icon' => '🛡️', 'label' => 'DV Act protection strategy',
                 'prompt' => "Outline a comprehensive strategy for seeking or defending against orders under the Domestic Violence Act, 2005 in this case. Identify necessary evidence of violence or counter-evidence, and advise on interim reliefs including protection orders, residence orders, and monetary relief. Focus on prompt action."],
            ],
            'revenue' => [
                ['icon' => '📈', 'label' => 'Revenue appeal strategy',
                 'prompt' => "Formulate a strategy for filing and pursuing an appeal before the Revenue Appellate Authority or Board of Revenue in this case. Identify key grounds for appeal from the case file, required documents, and procedural steps. Advise on the hierarchy of revenue courts and potential for revision."],
                ['icon' => '🗺️', 'label' => 'Land survey dispute strategy',
                 'prompt' => "Devise a strategy to address the land boundary or survey dispute in this case. Advise on requesting a fresh survey, examining old maps and khasra records, and collecting local evidence. Explore options for rectification of records under the Chhattisgarh Land Revenue Code and potential civil suit for injunction or declaration."],
                ['icon' => '🚧', 'label' => 'Acquisition compensation max',
                 'prompt' => "Brainstorm strategies to maximize compensation in the land acquisition case. Advise on challenging the market value determination, claiming solatium, and seeking rehabilitation benefits under the RFCTLARR Act, 2013. Discuss options for negotiation, reference to collector, and litigation."],
                ['icon' => '🚜', 'label' => 'Khasra/Khatauni correction',
                 'prompt' => "Outline a strategy for correcting errors in the Khasra or Khatauni records in this case. Advise on the application process, required documentary evidence, and potential challenges. Cite the relevant sections of the Chhattisgarh Land Revenue Code for record rectification and appeal timelines."],
            ],
            'special_act' => [
                ['icon' => '🏢', 'label' => 'RERA litigation strategy',
                 'prompt' => "Develop a comprehensive litigation strategy for the RERA complaint in this case. Identify key arguments, required documentary evidence (BBA, payment receipts, RERA registration), and specific reliefs sought. Advise on engaging RERA consultants and potential appeals to the RERA Appellate Tribunal."],
                ['icon' => '🛒', 'label' => 'Consumer dispute strategy',
                 'prompt' => "Brainstorm effective strategies for prosecuting or defending the consumer complaint in this case. Advise on evidence gathering, expert opinions, and settlement possibilities. Highlight critical aspects of the Consumer Protection Act, 2019, including unfair trade practices and product liability."],
                ['icon' => '🚗', 'label' => 'MACT case management',
                 'prompt' => "Outline a complete strategy for managing the MACT claim in this case. Advise on collecting accident reports, medical records, FIR, and insurance documents. Discuss negotiation with insurance companies and potential for appeal to High Court. Cite Motor Vehicles Act, 1988, on structured compensation."],
                ['icon' => '💼', 'label' => 'Labour tribunal strategy',
                 'prompt' => "Formulate a strategy for representing a party before the Labour Court or Industrial Tribunal in this case. Advise on preparing the statement of claim or defense, evidence presentation, and cross-examination of witnesses. Focus on key provisions of the Industrial Disputes Act, 1947."],
            ],
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
