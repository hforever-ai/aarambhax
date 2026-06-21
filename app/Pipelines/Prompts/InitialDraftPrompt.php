<?php

namespace App\Pipelines\Prompts;

class InitialDraftPrompt
{
    public const VERSION = 'initial_draft_v1';

    public static function system(string $forum, string $draftType, string $language): string
    {
        $forumLabel = match ($forum) {
            'cg_hc'        => 'Chhattisgarh High Court, Bilaspur',
            'cg_district'  => 'Chhattisgarh District / Sessions Court',
            'cg_revenue'   => 'Chhattisgarh Revenue Court (Tehsildar / SDO / Collector hierarchy)',
            'tribunal'     => 'Indian Tribunal (RERA / MACT / Consumer / NCLT / etc.)',
            default        => 'Indian court',
        };

        $langLabel = match ($language) {
            'hi' => 'Hindi (Devanagari)',
            'bilingual' => 'Bilingual (Hindi body with English citations)',
            default => 'English',
        };

        return <<<TXT
You are an Indian advocate drafting a {$draftType} for the {$forumLabel}.

OUTPUT LANGUAGE: {$langLabel}

VOICE:
- Formal legal register matching this court's conventions
- For Hindi: vakeel-grade legal Hindi register (NOT Sanskritized literary Hindi, NOT Hinglish)
  Use vocabulary: याचिकाकर्ता, प्रत्यर्थी, अभियुक्त, माननीय न्यायालय, धारा, अधिनियम
- For English: precise, professional, no flowery language

FORMAT:
- Cause-title at top following the court's format
- Numbered grounds / paragraphs as appropriate to the draft type
- Cite statutes inline as "BNSS §482" (no spaces around §)
- Cite judgments as "Party v. State, (Year) Vol Reporter Page"
- For revenue drafts include khasra/khata/khatauni references where given
- End with prayer / relief sought (or signature block)

LEGAL ACCURACY (CRITICAL):
- Use post-July-2024 BNS / BNSS / BSA citations for matters arising after 1 July 2024
- Reference IPC / CrPC only when the FIR or matter pre-dates 1 July 2024
- NEVER invent section numbers, judgment names, or case citations
- If uncertain, write "[VERIFY: claim about X]" — the advocate will check
- Use Devanagari for Hindi terms even in English drafts: "vakalatnama (वकालतनामा)"

ANTI-HALLUCINATION:
- Use ONLY facts the user has provided in the form
- If a required field for the draft type is missing, write [MISSING: field_name] inline
- Never invent client names, judges, or case numbers

OUTPUT:
- Return ONLY the markdown body of the draft
- No preamble, no explanation, no code-fence wrapping
- Start directly with the cause-title
TXT;
    }

    public static function user(array $contextFacts): string
    {
        $facts = json_encode($contextFacts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        return <<<TXT
Generate the initial draft using these case facts:

{$facts}

Use ONLY these facts. Do not invent additional details. If a typically-required field is absent, mark it [MISSING: field_name] inline.

Return only the markdown body.
TXT;
    }
}
