<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Faq;
use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AarambhaxSeeder extends Seeder
{
    public function run(): void
    {
        // Author
        $author = Author::firstOrCreate(
            ['slug' => 'aarambhax-editorial'],
            [
                'name' => 'Aarambhax Editorial',
                'designation' => 'Editorial Team',
                'bio_en' => 'The Aarambhax Editorial team curates practical drafting guides for Indian advocates, focused on Chhattisgarh practice and the new criminal codes (BNS / BNSS / BSA).',
                'is_active' => true,
            ],
        );

        // Categories (the 5 pillars)
        $categories = [
            ['slug' => 'new-criminal-codes', 'name_en' => 'New Criminal Codes', 'description_en' => 'BNS, BNSS, and BSA — section mappings, transition guides, practical drafting under the post-July-2024 codes.', 'display_order' => 1],
            ['slug' => 'drafting-walkthroughs', 'name_en' => 'Drafting Walkthroughs', 'description_en' => 'Step-by-step guides to drafting bail applications, plaints, writs, appeals, and notices.', 'display_order' => 2],
            ['slug' => 'revenue-court', 'name_en' => 'Revenue Court Practice', 'description_en' => 'Naamantaran, batwara, vyapvartan and CG Land Revenue Code applications. Hindi-first.', 'display_order' => 3],
            ['slug' => 'court-tech', 'name_en' => 'Court Tech & Process', 'description_en' => 'CG HC e-filing, eCourts, digital signatures, and the practical mechanics of Indian court technology.', 'display_order' => 4],
            ['slug' => 'product', 'name_en' => 'Product', 'description_en' => 'How to use Aarambhax effectively for your daily practice.', 'display_order' => 5],
        ];

        foreach ($categories as $c) {
            PostCategory::firstOrCreate(['slug' => $c['slug']], $c);
        }

        // 1 sample blog post
        $newCodesId = PostCategory::where('slug', 'new-criminal-codes')->value('id');
        $sampleBody = <<<'MD'
The criminal justice system in India underwent a fundamental shift on **1 July 2024**. Three new statutes — the Bharatiya Nyaya Sanhita (BNS), the Bharatiya Nagarik Suraksha Sanhita (BNSS), and the Bharatiya Sakshya Adhiniyam (BSA) — replaced the IPC, CrPC, and Indian Evidence Act respectively. This is not a cosmetic rebrand. Section numbers have changed, some procedures have been overhauled, and certain offences have been redefined.

This article maps the **100 most-cited sections** between the old and new codes to help advocates transition cleanly.

## Why this matters today

Even in 2026, district court advocates routinely cite IPC and CrPC sections out of habit. Two problems with that:

1. **FIRs registered after 1 July 2024** must cite BNS sections, not IPC. Drafts that cite the wrong code get returned at the filing counter.
2. **Pre-July-2024 cases** continue under the old codes — so you genuinely need both maps in your head.

## Top 10 transitions

| Old (IPC / CrPC) | New (BNS / BNSS) | Notes |
| --- | --- | --- |
| IPC §302 (murder) | BNS §103 | Punishment unchanged |
| IPC §307 (attempt to murder) | BNS §109 | Punishment unchanged |
| IPC §376 (rape) | BNS §63 | Definition expanded |
| IPC §420 (cheating) | BNS §319(2) | Renumbered, definition similar |
| IPC §498A (cruelty) | BNS §85 | Renumbered |
| CrPC §41 (arrest without warrant) | BNSS §35 | Procedural safeguards strengthened |
| CrPC §161 (witness statement) | BNSS §180 | Audio-video record now allowed |
| CrPC §437 (regular bail) | BNSS §480 | — |
| CrPC §438 (anticipatory bail) | BNSS §482 | — |
| CrPC §482 (inherent powers) | BNSS §528 | Quashing jurisdiction unchanged |

## Practical drafting tips

- **Cite the version that was in force when the FIR was registered.** A bail application under BNSS §483 cannot reference an FIR registered in 2023 under IPC §302 — you need IPC sections.
- **Don't trust shadow PDFs.** Several "BNS Bill" PDFs circulating online have outdated section numbers. Always verify against the [official Gazette of India](https://egazette.gov.in/) version.
- **Aarambhax automatically picks the right code** based on the FIR date you provide. No mental gymnastics required.

> **Generate an updated draft instantly with Aarambhax →**

## What's next

We'll publish the next tranche of mappings (BSA → Indian Evidence Act for digital evidence, and BNSS forensic-investigation provisions) over the coming weeks. Subscribe to the Aarambhax newsletter to be notified.
MD;

        Post::firstOrCreate(
            ['slug' => 'ipc-to-bns-section-mapping'],
            [
                'language'     => 'en',
                'category_id'  => $newCodesId,
                'archetype'    => 'section_mapping',
                'title'        => 'IPC to BNS: Section Mapping for the 100 Most-Cited Sections',
                'subtitle'     => 'A practical reference for advocates transitioning to the post-July-2024 criminal codes.',
                'excerpt'      => 'A practical mapping of the 100 most-cited IPC, CrPC, and Indian Evidence Act sections to their BNS, BNSS, and BSA equivalents.',
                'body'         => $sampleBody,
                'meta_title'   => 'IPC to BNS Section Mapping (2026 Guide) | Aarambhax',
                'meta_description' => 'Practical IPC → BNS, CrPC → BNSS, IEA → BSA mapping for the 100 most-cited sections. For Indian advocates transitioning to the new criminal codes.',
                'author_id'    => $author->id,
                'status'       => 'published',
                'published_at' => now()->subDay(),
                'reading_time_minutes' => 6,
            ],
        );

        // 8 launch FAQs
        $faqs = [
            ['topic' => 'bns-bnss', 'q' => 'What replaced the IPC, and when did it come into force?', 'a' => 'The Bharatiya Nyaya Sanhita (BNS) replaced the Indian Penal Code, 1860, on **1 July 2024**. Two companion statutes — the Bharatiya Nagarik Suraksha Sanhita (BNSS) replacing the CrPC, and the Bharatiya Sakshya Adhiniyam (BSA) replacing the Indian Evidence Act — came into force the same day. Pre-July-2024 cases continue under the old codes; new FIRs are registered under the new codes.', 'featured' => true, 'order' => 1],
            ['topic' => 'bns-bnss', 'q' => 'Is anticipatory bail still available under BNSS?', 'a' => 'Yes. Anticipatory bail under BNSS §482 is the direct successor to CrPC §438. The substantive grounds (reasonable apprehension of arrest, no flight risk, cooperation with investigation) remain unchanged. Procedural safeguards have been clarified.', 'featured' => true, 'order' => 2],
            ['topic' => 'bns-bnss', 'q' => 'How is bail under BNSS §483 different from CrPC §439?', 'a' => 'BNSS §483 is the renumbered successor to CrPC §439 (regular bail by High Court or Sessions Court). The substantive law is unchanged. The case-law interpreting CrPC §439 (Arnesh Kumar, Satender Kumar Antil, etc.) continues to apply.', 'featured' => false, 'order' => 3],
            ['topic' => 'drafting', 'q' => 'What is a vakalatnama and when do I need one?', 'a' => 'A vakalatnama (वकालतनामा) is the written authorisation by which a litigant appoints an advocate to represent them. It must be filed in every matter where the advocate appears, signed by the client, and accepted by the advocate. Format varies slightly between High Court (English) and district court (Hindi).', 'featured' => true, 'order' => 4],
            ['topic' => 'revenue', 'q' => 'What is naamantaran (mutation) and when is it required?', 'a' => 'Naamantaran (नामांतरण) is the formal entry of a change of ownership in the revenue records — Khasra, B-1, P-II, Khatauni — under the Chhattisgarh Land Revenue Code, 1959, primarily under §109. It is required after sale, inheritance, gift, or partition of agricultural land, and must be filed before the jurisdictional Tehsildar.', 'featured' => true, 'order' => 5],
            ['topic' => 'revenue', 'q' => 'What is khasra, khata, and khatauni?', 'a' => '**Khasra** is the unique number assigned to a parcel of land in the village revenue records. **Khata** (or khewat) is the holding number that groups parcels owned by the same person or family. **Khatauni** is the rights record showing current ownership and tenure status.', 'featured' => false, 'order' => 6],
            ['topic' => 'product', 'q' => 'Is Aarambhax suitable for revenue court matters?', 'a' => 'Yes — and this is where Aarambhax differs sharply from English-only legal AI tools. Aarambhax is the only AI drafting tool with native templates for naamantaran, batwara, seemankan, and bhumi vyapvartan applications, in Hindi, with khasra/khata fields built into the form structure.', 'featured' => true, 'order' => 7],
            ['topic' => 'product', 'q' => 'How does the Verifier work?', 'a' => 'After every draft is generated, the Verifier extracts every cited section and judgment, then checks each one against (1) our internal bare-acts database (BNS, BNSS, BSA, CG LRC, NI Act and more), and (2) the Indian Kanoon API for judgments. Each citation gets a coloured badge — green (verified), amber (caution / minor mismatch), or red (not found / likely hallucinated). You can replace or remove flagged citations with one click before exporting.', 'featured' => true, 'order' => 8],
        ];

        foreach ($faqs as $f) {
            Faq::firstOrCreate(
                ['slug' => Str::slug($f['q']) ],
                [
                    'language' => 'en',
                    'question' => $f['q'],
                    'answer'   => $f['a'],
                    'topic'    => $f['topic'],
                    'is_featured' => $f['featured'],
                    'is_published' => true,
                    'display_order' => $f['order'],
                ],
            );
        }

        $this->command->info('Aarambhax seeded: 1 author, 5 categories, 1 post, '.count($faqs).' FAQs.');
    }
}
