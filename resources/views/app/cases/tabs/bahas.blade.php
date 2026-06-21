{{-- Bahas tab — hearing prep + oral argument workspace. Free tier, PII-redacted. --}}
@php
    $config = [
        'title' => 'Prep for the next hearing',
        'subtitle' => 'Pocket brief, bench questions, opposing arguments — all grounded in the case file.<br>Free tier · PII-redacted · ₹0 per turn.',
        'placeholder' => 'e.g. Generate a pocket brief, or "what will the bench ask?"',
        'tier_label' => 'FREE tier',
        'tier_color_var' => 'success',
        'send_url' => route('app.cases.chat.send', ['case' => $case, 'kind' => 'bahas']),
        'show_lang_picker' => true,
        'quick_actions' => [
            '_identify' => [
                ['icon' => '❓', 'label' => 'Identify case & prep plan',
                 'prompt' => "Analyze the uploaded case documents to identify the precise category and sub-category of this case. Based on this, outline the most critical legal questions likely to arise during the next hearing. Then, suggest a 3-step preparation plan focusing on statutory provisions (BNS/BNSS/BSA), relevant case law, and potential bench queries."],
            ],
            '_all' => [
                ['icon' => '📄', 'label' => 'Pocket brief for next hearing',
                 'prompt' => "Build a one-page pocket brief I can carry into court for the next hearing: parties, today's prayer, last order, three strongest points, three weakest points, anticipated bench questions, citations to keep handy. Ground every point in the case documents."],
                ['icon' => '⚠️', 'label' => 'Procedural watch-outs',
                 'prompt' => "What procedural issues could trip me up at the next hearing? Limitation, locus, maintainability, jurisdiction — flag anything in the case file that could be raised against us and give me a one-line answer to each."],
            ],
            'criminal' => [
                ['icon' => '🚨', 'label' => 'Bail argument points',
                 'prompt' => "Prepare key argument points for the bail application in this case under BNSS, 2023. Focus on grounds like no flight risk, no tampering with evidence, no criminal antecedents, cooperation with investigation, and disproportionate punishment. Cite Section 480 BNSS and relevant Supreme Court guidelines on bail."],
                ['icon' => '⚖️', 'label' => 'Framing of charges prep',
                 'prompt' => "Outline essential arguments for or against the framing of charges in this criminal case. Identify the specific sections of BNS invoked and analyze whether a prima facie case exists based on the case file. Detail arguments to persuade the court on the sufficiency or insufficiency of evidence for framing."],
                ['icon' => '🕵️', 'label' => 'Weaknesses in prosecution evidence',
                 'prompt' => "Identify critical weaknesses and contradictions in the prosecution's evidence — cross-referencing witness testimonies, documentary evidence, and forensic reports in the case file. Propose how these can be exploited during arguments, citing provisions of Bharateeya Sakshya Adhiniyam, 2023."],
                ['icon' => '📖', 'label' => 'Final argument outline',
                 'prompt' => "Generate a comprehensive outline for final arguments in this criminal trial. Structure it with an introduction, summary of prosecution's failure or defense's success, analysis of key evidence (or lack thereof), legal points, and clear prayer for acquittal. Cite relevant BNS/BNSS/BSA provisions and evidence rules."],
            ],
            'civil' => [
                ['icon' => '📜', 'label' => 'Interim relief arguments',
                 'prompt' => "Prepare concise arguments for the application for interim relief (temporary injunction, stay, attachment) in this case. Focus on establishing prima facie case, balance of convenience, and irreparable injury from the case documents. Cite Order XXXIX CPC and relevant precedents."],
                ['icon' => '🗣️', 'label' => 'Issues for determination',
                 'prompt' => "Propose a precise set of 'Issues for Determination' based on the pleadings in the case file. Ensure these issues cover all material propositions of fact and law disputed, as required by Order XIV Rule 1 CPC. Justify each proposed issue with reference to the documents."],
                ['icon' => '🏛️', 'label' => 'Jurisdiction argument',
                 'prompt' => "Draft arguments concerning the court's territorial or pecuniary jurisdiction in this civil suit. Identify specific legal provisions of CPC, 1908, being invoked (Sections 15–20), and present facts from the case file that either support or negate the court's power to hear the case."],
                ['icon' => '🎯', 'label' => 'What will other side argue?',
                 'prompt' => "Predict the three strongest arguments opposing counsel will make at the next hearing, based on the case documents. For each, give a one-line counter that I can deliver standing up."],
            ],
            'family' => [
                ['icon' => '💖', 'label' => 'Divorce grounds arguments',
                 'prompt' => "Prepare essential arguments to establish or refute grounds for divorce (cruelty, desertion) under the Hindu Marriage Act, 1955, based on the facts in the case file. Cite specific statutory provisions and relevant Supreme Court or High Court judgments. Highlight key evidence to present at hearing."],
                ['icon' => '💲', 'label' => 'Maintenance quantum arguments',
                 'prompt' => "Outline arguments regarding the quantum of maintenance under Section 125 BNSS or Hindu Adoptions and Maintenance Act, 1956, based on the case file. Focus on respondent's income, lifestyle, and needs of the applicant. Cite relevant case law on determination of quantum."],
                ['icon' => '👨‍👩‍👧‍👦', 'label' => 'Child welfare arguments',
                 'prompt' => "Develop arguments emphasizing the 'welfare of the child' principle for custody or visitation matters in this case. Detail factors supporting the applicant as the better guardian, considering child's wishes, stability, and emotional development. Cite Guardians and Wards Act, 1890, and relevant precedents."],
                ['icon' => '🏠', 'label' => 'DV Act relief arguments',
                 'prompt' => "Prepare arguments for specific reliefs sought under the Domestic Violence Act, 2005 (protection order, residence order, monetary relief). Focus on substantiating instances of violence from the case file and the necessity of the orders for the aggrieved person's safety. Cite specific sections of the Act."],
            ],
            'revenue' => [
                ['icon' => '🗺️', 'label' => 'Land title arguments',
                 'prompt' => "Outline arguments to establish or challenge the specific land title in this case based on sale deeds, inheritance, or adverse possession documents in the case file. Cite relevant provisions of the Transfer of Property Act, 1882, and Chhattisgarh Land Revenue Code. Highlight key documentary evidence."],
                ['icon' => '💰', 'label' => 'Compensation valuation arguments',
                 'prompt' => "Prepare arguments concerning the fair market value and compensation under the Land Acquisition Act, 2013, for the property in this case. Address factors like land use, potential, comparable sales, and solatium. Outline how to challenge or defend the valuation report effectively."],
                ['icon' => '📜', 'label' => 'Mutation challenge arguments',
                 'prompt' => "Develop arguments to challenge or defend the mutation entry at issue in this case. Focus on procedural irregularities, lack of proper consent, or misinterpretation of inheritance laws. Cite relevant sections of the Chhattisgarh Land Revenue Code and the limitation period for correction."],
                ['icon' => '⛰️', 'label' => 'Boundary dispute arguments',
                 'prompt' => "Outline key arguments for the boundary dispute in this case. Focus on survey reports, ancient documents, khasra entries, and local customs found in the case file. Cite relevant sections of the Specific Relief Act, 1963 for declaration and injunction, and local land measurement standards."],
            ],
            'special_act' => [
                ['icon' => '🚧', 'label' => 'RERA non-compliance arguments',
                 'prompt' => "Prepare arguments demonstrating the promoter's non-compliance with RERA Act, 2016 provisions based on the case file (project delay, misrepresentation, lack of promised amenities). Cite specific sections of RERA and relevant clauses of the Agreement for Sale. Focus on calculation of interest and compensation."],
                ['icon' => '💳', 'label' => 'Consumer dispute argument points',
                 'prompt' => "Outline critical argument points for the consumer complaint in this case regarding deficiency of service or product defect. Focus on establishing the consumer-provider relationship, the nature of the deficiency, and the resulting loss. Cite the Consumer Protection Act, 2019, and relevant national commission rulings."],
                ['icon' => '🚑', 'label' => 'MACT liability arguments',
                 'prompt' => "Develop arguments concerning liability and negligence in the MACT claim in this case. Focus on establishing rash and negligent driving, contributory negligence, and the role of insurance companies. Cite the Motor Vehicles Act, 1988, and relevant Supreme Court judgments on tortious liability."],
                ['icon' => '👷', 'label' => 'Labour law contravention',
                 'prompt' => "Prepare arguments highlighting contraventions of labour laws in this case (illegal termination, non-payment of wages, unfair labour practice). Cite relevant sections of the Industrial Disputes Act, 1947, Minimum Wages Act, 1948, or other specific labour legislation applicable. Focus on proving the employer's default."],
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
