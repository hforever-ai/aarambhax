{{-- Draft tab — chat workspace for court-filable drafts. PAID tier (Flash 2.5)
     because output is a court-filable artefact (no PII redaction). --}}
@php
    $config = [
        'title' => 'Draft for filing',
        'subtitle' => 'Court-filable pleadings — replies, applications, petitions. Para-wise structure with [TO BE FILLED] markers.<br>Paid tier · ~₹0.05–0.15 per turn · uses real names + section numbers.',
        'placeholder' => 'e.g. "Draft a Civil Revision under §115 CPC against the lower court order"',
        'tier_label' => 'PAID tier',
        'tier_color_var' => 'accent',
        'send_url' => route('app.cases.chat.send', ['case' => $case, 'kind' => 'draft']),
        'show_lang_picker' => true,
        'default_lang' => in_array($case->forum ?? '', ['cg_hc', 'sc']) ? 'en' : 'hi',
        'quick_actions' => [
            '_identify' => [
                ['icon' => '❓', 'label' => 'Identify case & plan draft',
                 'prompt' => "Analyze the uploaded case documents thoroughly. First, determine the precise category and sub-category of this case (e.g., criminal/bail, civil/injunction, family/divorce). Second, identify the key parties and core legal issues involved. Third, propose a 3-step action plan for drafting the initial pleading, outlining required documents and relevant statutory provisions under BNS/BNSS/BSA."],
            ],
            '_all' => [
                ['icon' => '✂️', 'label' => 'Tighten the latest draft',
                 'prompt' => "Tighten the latest draft I produced — same content, fewer words, more court-room voice. Keep all section citations and structure exactly the same. Just sharpen the prose."],
                ['icon' => '🛡️', 'label' => 'Preliminary objections only',
                 'prompt' => "Draft only the preliminary objections section for a reply — jurisdiction, locus, limitation, maintainability — based on what's in the case file. Each objection must cite the specific document or section that supports it. Skip merits."],
            ],
            'criminal' => [
                ['icon' => '🆓', 'label' => 'Bail application',
                 'prompt' => "Draft a regular bail application under the relevant section of BNSS, 2023. Cause-title, applicant's particulars, brief facts of FIR, grounds for bail (false implication, period of custody, cooperation with investigation, no flight risk, twin conditions if special law applies), prayer. Mark all sureties + police-station name as [TO BE FILLED]."],
                ['icon' => '🛡️', 'label' => 'Anticipatory bail u/s 480 BNSS',
                 'prompt' => "Draft an anticipatory bail application under Section 480 of BNSS, 2023. Include grounds such as false implication, no previous antecedents, willingness to cooperate with investigation, and compliance with recent Supreme Court guidelines on anticipatory bail. Mark FIR number and offence sections as [TO BE FILLED] if not in documents."],
                ['icon' => '🚨', 'label' => 'Quash FIR u/s 482 BNSS',
                 'prompt' => "Prepare a petition to quash FIR No. [FIR Number] under Section 482 of Bharateeya Nagarik Suraksha Sanhita, 2023. Focus on grounds such as no prima facie case, abuse of process of law, or settlement between parties. Use the documents in the case file to establish all factual grounds. Ensure all necessary affidavits and annexures are indicated."],
                ['icon' => '⚖️', 'label' => 'Reply to opposing notice',
                 'prompt' => "Draft a para-wise reply to the most recent legal notice or opposite party's pleading in the case file. Include preliminary objections (only if they actually apply), para-wise denials/admissions, and reliefs prayed. Use [TO BE FILLED] for facts I need to verify (signatures, dates). Mark unverified citations as [CITATION NEEDED — verify]."],
                ['icon' => '📖', 'label' => 'Reply to charge-sheet',
                 'prompt' => "Draft a comprehensive reply to the charge-sheet filed by the prosecution, based on the documents in the case file. Address each allegation specifically, highlight contradictions between the charge-sheet and witness statements, and point out deficiencies in the investigation. Emphasize the defense's position and any alibi."],
            ],
            'civil' => [
                ['icon' => '📜', 'label' => 'Civil revision under §115 CPC',
                 'prompt' => "Draft a Civil Revision petition under §115 CPC against the impugned order in the case file. Cause-title, parties, brief facts, grounds, prayer for stay, prayer for setting aside. Para-wise. [TO BE FILLED] for advocate's signature block. Include limitation calculation (90 days from order)."],
                ['icon' => '🚫', 'label' => 'Injunction O.39 R.1 CPC',
                 'prompt' => "Prepare an application for temporary injunction under Order XXXIX Rule 1 and 2 of the Code of Civil Procedure, 1908. Articulate the prima facie case, balance of convenience, and irreparable injury based on facts in the case file. Clearly state the specific injunctive relief sought against the defendant."],
                ['icon' => '✍️', 'label' => 'Written statement',
                 'prompt' => "Draft a written statement to the civil suit in the case file. Address each para of the plaint specifically, raise preliminary objections (jurisdiction, limitation, locus), and include specific denials and new facts constituting the defense. Ensure compliance with Order VIII Rule 1 CPC."],
                ['icon' => '⚖️', 'label' => 'Reply to opposing notice',
                 'prompt' => "Draft a para-wise reply to the most recent legal notice or opposite party's pleading in the case file. Include preliminary objections (only if they actually apply), para-wise denials/admissions, and reliefs prayed. Use [TO BE FILLED] for facts I need to verify. Mark unverified citations as [CITATION NEEDED — verify]."],
            ],
            'family' => [
                ['icon' => '💔', 'label' => 'Divorce petition draft',
                 'prompt' => "Draft a petition for divorce under Section 13(1)(ia) and/or (ib) of the Hindu Marriage Act, 1955, on grounds of cruelty and/or desertion, based on the facts in the case file. Detail specific instances with dates and locations. Include prayers for ancillary reliefs like maintenance and custody if applicable. [TO BE FILLED] for marriage certificate details if not in documents."],
                ['icon' => '💸', 'label' => 'Maintenance u/s 125 BNSS',
                 'prompt' => "Prepare a petition for maintenance under Section 125 of the Bharateeya Nagarik Suraksha Sanhita, 2023, for wife and/or minor child. Detail the applicant's inability to maintain herself/themselves, respondent's means and capacity to pay, and grounds for neglect or refusal. Specify the monthly maintenance sought. Ground all facts in the case documents."],
                ['icon' => '🏘️', 'label' => 'DV Act petition u/s 12',
                 'prompt' => "Draft a petition under Section 12 of the Protection of Women from Domestic Violence Act, 2005, based on the facts in the case file. Detail specific instances of domestic violence (physical, emotional, economic, sexual) and seek protection orders, residence orders, monetary relief, and custody orders as applicable. Ensure all necessary affidavits are indicated with [TO BE FILLED] markers."],
                ['icon' => '👶', 'label' => 'Child custody application',
                 'prompt' => "Draft an application for interim or permanent child custody under the Guardians and Wards Act, 1890, or Hindu Minority and Guardianship Act, 1956, based on the case file. Focus on the 'welfare of the child' principle, detailing the applicant's suitability and respondent's unsuitability. Include specific parenting plan if applicable."],
            ],
            'revenue' => [
                ['icon' => '🌳', 'label' => 'Mutation application draft',
                 'prompt' => "Draft an application for mutation of land records based on the registered sale deed or inheritance documents in the case file. Include details of the property (khasra number, area, location), previous owner, new owner, and the basis of transfer. Specify the revenue authority and list all required supporting documents."],
                ['icon' => '🚧', 'label' => 'Land acquisition protest',
                 'prompt' => "Prepare a detailed protest petition against the land acquisition notification based on documents in the case file, under the Right to Fair Compensation and Transparency in Land Acquisition, Rehabilitation and Resettlement Act, 2013. Highlight objections regarding public purpose, adequacy of compensation, and any procedural non-compliance."],
                ['icon' => '🚜', 'label' => 'Appeal against khasra entry',
                 'prompt' => "Draft an appeal challenging the erroneous entry in the Khasra or Khatauni record before the appropriate Revenue Appellate Authority, based on documents in the case file. Clearly identify the specific incorrect entry, the correct information, and the legal basis for correction. Cite relevant provisions of the Chhattisgarh Land Revenue Code."],
                ['icon' => '📜', 'label' => 'Title declaration suit',
                 'prompt' => "Draft a civil suit for declaration of title and permanent injunction concerning the agricultural land parcel in the case file. Detail the plaintiff's chain of title, how the defendant's claim is invalid, and seek declaration of ownership and restraint from interference. Specify khasra number, area, and district."],
            ],
            'special_act' => [
                ['icon' => '🏢', 'label' => 'RERA complaint draft',
                 'prompt' => "Draft a complaint before the Real Estate Regulatory Authority (RERA) against the promoter, based on the documents in the case file. Detail the agreement for sale, RERA registration number, delay in possession or deviation from plan, and specific reliefs sought (refund, interest, compensation). Cite relevant sections of the RERA Act, 2016."],
                ['icon' => '🛒', 'label' => 'Consumer complaint draft',
                 'prompt' => "Prepare a consumer complaint before the District Consumer Disputes Redressal Commission for deficiency in service or unfair trade practice, based on the case file. Clearly state the facts, the specific deficiency or unfair practice, amount of compensation/relief sought, and list documents to attach. Cite the Consumer Protection Act, 2019."],
                ['icon' => '🚗', 'label' => 'MACT claim petition',
                 'prompt' => "Draft a claim petition before the Motor Accidents Claims Tribunal (MACT) for compensation due to injury or death in the motor vehicle accident described in the case file. Include details of the accident, vehicles, parties, nature of injuries/loss, and the specific compensation claimed under various heads (medical, loss of earning, pain & suffering). Cite the Motor Vehicles Act, 1988."],
                ['icon' => '💼', 'label' => 'Labour dispute statement',
                 'prompt' => "Draft a statement of claim for an industrial dispute before the Labour Court or Industrial Tribunal, based on the facts in the case file. Detail the employment facts, nature of dispute (wrongful termination, unfair labour practice, non-payment of dues), and specific reliefs sought. Cite the Industrial Disputes Act, 1947."],
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

{{-- Past Karya drafts + finalised Drafts (read-only links) --}}
@if(($pastWorks ?? collect())->isNotEmpty() || ($drafts ?? collect())->isNotEmpty())
    <div class="draft-archive" style="margin-top: 1.5rem;">
        <h3 class="draft-archive-title">Past drafts on this case</h3>
        <div class="draft-archive-list">
            @foreach($pastWorks ?? [] as $w)
                <a href="{{ route('app.cases.karyas.show', [$case, $w]) }}" class="draft-archive-row">
                    <span class="draft-archive-icon">📄</span>
                    <div class="draft-archive-meta">
                        <div class="draft-archive-name">{{ \Illuminate\Support\Str::limit($w->title, 70) }}</div>
                        <div class="draft-archive-sub">Karya · {{ $w->updated_at->diffForHumans() }}</div>
                    </div>
                </a>
            @endforeach
            @foreach($drafts ?? [] as $d)
                <a href="{{ route('app.drafts.show', $d) }}" class="draft-archive-row">
                    <span class="draft-archive-icon">📝</span>
                    <div class="draft-archive-meta">
                        <div class="draft-archive-name">{{ \Illuminate\Support\Str::limit($d->title, 70) }}</div>
                        <div class="draft-archive-sub">Draft · {{ strtoupper($d->language) }} · {{ $d->updated_at->diffForHumans() }}</div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    <style>
        .draft-archive-title {
            font-size: 0.6875rem; text-transform: uppercase; letter-spacing: 0.08em;
            font-weight: 600; color: var(--fg-muted); margin: 0 0 0.625rem;
        }
        .draft-archive-list { display: flex; flex-direction: column; gap: 0.375rem; }
        .draft-archive-row {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 0.625rem 0.875rem;
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 8px; text-decoration: none; color: inherit;
            transition: border-color 150ms ease-out;
        }
        .draft-archive-row:hover { border-color: color-mix(in srgb, var(--accent) 40%, var(--border)); }
        .draft-archive-icon { font-size: 1rem; flex-shrink: 0; }
        .draft-archive-meta { min-width: 0; flex: 1; }
        .draft-archive-name {
            font-size: 0.875rem; font-weight: 500; color: var(--fg);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .draft-archive-sub {
            font-size: 0.6875rem; color: var(--fg-muted);
            text-transform: uppercase; letter-spacing: 0.04em; margin-top: 0.125rem;
        }
    </style>
@endif
