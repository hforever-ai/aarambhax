{{-- Cross-Examination tab — witness questions, contradictions. Free tier. --}}
@php
    $config = [
        'title' => 'Plan cross-examination',
        'subtitle' => 'Generate leading questions, find contradictions, map A/B/C branches.<br>Free tier · PII-redacted · ₹0 per turn.',
        'placeholder' => 'e.g. "Cross plan for the IO" or "find contradictions between FIR and 161 statement"',
        'tier_label' => 'FREE tier',
        'tier_color_var' => 'success',
        'send_url' => route('app.cases.chat.send', ['case' => $case, 'kind' => 'cross_exam']),
        'show_lang_picker' => true,
        'quick_actions' => [
            '_identify' => [
                ['icon' => '❓', 'label' => 'Identify case & cross plan',
                 'prompt' => "Analyze the uploaded case documents to identify the precise category and sub-category. Based on this, identify the 3 most crucial witnesses whose testimony will be critical to the outcome. For each, suggest a 3-step cross-examination strategy focusing on contradictions, omissions, and leading questions, citing Bharateeya Sakshya Adhiniyam, 2023."],
            ],
            '_all' => [
                ['icon' => '🧷', 'label' => 'Find contradictions',
                 'prompt' => "Find contradictions in the case file — between the FIR and the 161 statement, between two witness statements, between a document and oral testimony. List each with exact source page references and a leading question I can use to put the contradiction to the witness, citing Section 145 BSA, 2023."],
                ['icon' => '📜', 'label' => 'Sec 145 BSA setup',
                 'prompt' => "For each contradiction we want to use against the witness, write the formal Sec 145 Bharateeya Sakshya Adhiniyam, 2023, setup: (1) the exact text of the prior statement to put to the witness, (2) the leading question putting the contradiction, (3) the procedure if they admit it, (4) the procedure if they deny it (confront with signed statement)."],
            ],
            'criminal' => [
                ['icon' => '🎯', 'label' => 'Cross plan for IO',
                 'prompt' => "Generate a full cross-examination plan for the Investigating Officer in this case. Structure: setup questions (commit IO to obvious facts), Section 145 BSA contradictions between case diary and testimony, lapses in investigation (non-collection of evidence, delayed seizure, procedural irregularities), and closing questions to lock in admissions. Cite BNSS, 2023, and BSA, 2023."],
                ['icon' => '🤥', 'label' => 'PW contradictions strategy',
                 'prompt' => "Outline a strategy for cross-examining prosecution witnesses by highlighting contradictions between their statements under Section 180 BNSS (earlier: 161 CrPC) and their evidence in court. Provide examples of leading questions to expose falsehoods, citing Section 145 BSA, 2023. Use the specific statement content from the case file."],
                ['icon' => '💉', 'label' => 'Medical expert cross',
                 'prompt' => "Develop a cross-examination plan for the medical expert witness in this case (doctor or forensic expert). Focus on inconsistencies in reports, alternative interpretations of injuries or cause of death, and limitations of medical science. Frame questions to challenge their conclusion or expertise under BSA, 2023."],
                ['icon' => '🔀', 'label' => 'Plan A / B / C branches',
                 'prompt' => "Pick the single most important question to ask in cross based on the case file. Map all three ways the witness might answer it (A: the answer we want, B: the answer that hurts us, C: 'I don't recall'). For each branch, write the next 2–3 follow-up questions — what locks them in, what confronts them, what concedes nothing."],
            ],
            'civil' => [
                ['icon' => '📄', 'label' => 'Affidavit contradictions',
                 'prompt' => "Pinpoint contradictions between the opposing party's affidavit-in-evidence and their earlier pleadings or documentary evidence in the case file. Draft specific questions to expose these inconsistencies and undermine their credibility, citing Section 145 BSA, 2023."],
                ['icon' => '🖋️', 'label' => 'Document authenticity cross',
                 'prompt' => "Develop a cross-examination plan to challenge the authenticity or admissibility of a crucial document presented by the opponent in this case. Focus on the mode of proof, authorship, registration, and any suspicious alterations or interpolations. Frame questions based on Chapter V of Bharateeya Sakshya Adhiniyam, 2023."],
                ['icon' => '🏘️', 'label' => 'Property witness cross',
                 'prompt' => "Prepare a cross-examination strategy for a witness testifying on property ownership, boundaries, or transactions in this case. Focus on their source of knowledge, memory, bias, and consistency with revenue records or title deeds. Elicit admissions or expose falsehoods by confronting with specific documents in the case file."],
                ['icon' => '💰', 'label' => 'Financial witness credibility',
                 'prompt' => "Outline a cross-examination plan for the witness testifying on financial matters (income, expenses, valuation) in this civil case. Focus on challenging the basis of their figures, lack of documentary support, or expertise. Frame questions to expose overstatement or understatement, with reference to bank records or IT returns in the case file."],
            ],
            'family' => [
                ['icon' => '💔', 'label' => 'Cruelty allegation cross',
                 'prompt' => "Develop a cross-examination plan for the witness alleging cruelty in this divorce or DV case. Focus on specific instances (date, place, nature), corroboration, any prior complaints filed (or not filed), and possible motive for false implication. Frame questions to show exaggeration, inconsistency, or fabrication, citing BSA, 2023."],
                ['icon' => '💲', 'label' => 'Income/means discrepancies',
                 'prompt' => "Prepare a cross-examination strategy for the party or witness testifying on income and financial means in the maintenance case. Focus on discrepancies between stated income and actual lifestyle, tax returns, bank statements, and undisclosed assets or property. Frame questions to expose under-reporting of income under BSA, 2023."],
                ['icon' => '👶', 'label' => 'Child welfare witness cross',
                 'prompt' => "Outline a cross-examination plan for the witness presented in support of the opposing party's claim for child custody. Focus on their relationship with the child, any bias or conflict of interest, suitability of the opposing parent, and impact on the child's well-being. Elicit admissions that favor the applicant."],
                ['icon' => '🏠', 'label' => 'DV Act witness cross',
                 'prompt' => "Develop a cross-examination plan for a witness testifying in the Domestic Violence Act case. Focus on specific incidents of violence (date, place, presence at scene), inconsistencies with the complaint or medical reports in the case file, and potential for false implication. Frame questions to discredit the allegations under BSA, 2023."],
            ],
            'revenue' => [
                ['icon' => '🗺️', 'label' => 'Survey officer cross',
                 'prompt' => "Prepare a cross-examination plan for the Survey Officer or Patwari in this revenue case. Focus on the methodology of the survey, instruments used, correctness of measurements, and adherence to the Chhattisgarh Land Revenue Code. Frame questions to challenge the accuracy of their report or map."],
                ['icon' => '📜', 'label' => 'Revenue record witness cross',
                 'prompt' => "Outline a cross-examination strategy for a witness testifying on entries in revenue records (Khasra, Khatauni, Jamabandi) in this case. Focus on their knowledge of the record-keeping process, source of information, and any discrepancies with original documents or established facts. Frame questions to challenge the veracity of the entries."],
                ['icon' => '🚧', 'label' => 'Acquisition valuation cross',
                 'prompt' => "Develop a cross-examination plan for the valuation expert or government official testifying on land acquisition compensation in this case. Focus on the basis of their valuation, comparable sales data used, method of assessment, and any bias or conflict of interest. Frame questions to challenge the fairness and adequacy of the offered compensation under RFCTLARR Act, 2013."],
                ['icon' => '🚜', 'label' => 'Mutation witness cross',
                 'prompt' => "Prepare a cross-examination plan for a witness testifying about the mutation entry at issue in this case. Focus on their personal knowledge of the transfer, consent of all parties, procedural compliance by the Patwari, and any alleged fraud or misrepresentation. Frame questions to expose any irregularity in the mutation process under BSA, 2023."],
            ],
            'special_act' => [
                ['icon' => '🏢', 'label' => 'RERA promoter cross',
                 'prompt' => "Develop a cross-examination plan for the promoter or developer's witness in this RERA complaint. Focus on project delays, deviations from the approved plan, quality of construction shortfalls, and non-compliance with the RERA Act, 2016. Frame questions to elicit admissions about contractual breaches and statutory violations, citing BSA, 2023."],
                ['icon' => '🛒', 'label' => 'Consumer service provider cross',
                 'prompt' => "Prepare a cross-examination strategy for the service provider's or manufacturer's representative in this consumer complaint. Focus on the specific deficiency in service or product defect, their response to complaints, and adherence to quality standards. Frame questions to establish negligence or unfair trade practice under Consumer Protection Act, 2019."],
                ['icon' => '🚗', 'label' => 'Accident witness cross (MACT)',
                 'prompt' => "Outline a cross-examination plan for an eye-witness to the motor accident in this MACT claim. Focus on their position, ability to observe, speed of vehicles, and any inconsistencies with the FIR or other statements in the case file. Frame questions to establish negligence or contributory negligence, citing BSA, 2023."],
                ['icon' => '💼', 'label' => 'Management witness cross (Labour)',
                 'prompt' => "Develop a cross-examination plan for the management witness in this labour dispute (wrongful termination or unfair labour practice). Focus on the termination process, reasons given, adherence to standing orders and service rules, and any procedural lapses. Frame questions to challenge the legitimacy of the management's actions under Industrial Disputes Act, 1947, and BSA, 2023."],
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
