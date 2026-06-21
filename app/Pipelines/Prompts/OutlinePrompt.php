<?php

namespace App\Pipelines\Prompts;

class OutlinePrompt
{
    public const VERSION = 'outline_v1';

    public static function system(): string
    {
        return <<<'TXT'
You are an editorial planner for Aarambhax Legal, a publication serving Indian advocates practising in Chhattisgarh. You write outlines for practical drafting articles.

CRITICAL CONSTRAINTS:
1. Use post-July-2024 codes (BNS / BNSS / BSA), NOT IPC / CrPC / Indian Evidence Act, unless explicitly comparing.
2. Cite Chhattisgarh High Court / Supreme Court judgments only — never lower courts or hallucinated cases.
3. Do not invent statutes, sections, or case names. If unsure, write "[CITATION NEEDED]" and let the editor fill in.
4. Every drafting article must include a "Common Mistakes" section.
5. Output STRICT JSON matching the provided schema. No prose, no markdown wrapping.
TXT;
    }

    public static function user(array $params): string
    {
        $topic = $params['topic'] ?? '';
        $archetype = $params['archetype'] ?? 'general';
        $targetWords = $params['target_words'] ?? 1800;
        $languages = $params['languages'] ?? 'en';

        return <<<TXT
Generate an outline for an Aarambhax Legal blog post.

Topic: {$topic}
Archetype: {$archetype}
Target audience: Practising advocates in Chhattisgarh (CG HC, district, revenue)
Target length: {$targetWords} words
Languages: {$languages}

Return JSON in this exact shape:
{
  "title": "string (≤70 chars, primary keyword first)",
  "subtitle": "string (≤120 chars)",
  "meta_description": "string (≤160 chars, action-oriented)",
  "primary_keyword": "string",
  "secondary_keywords": ["string", "string"],
  "sections": [
    {
      "heading": "string",
      "purpose": "what this section accomplishes",
      "key_points": ["bullet", "bullet"],
      "citations_needed": ["BNSS §X", "judgment Y"],
      "estimated_words": 250
    }
  ],
  "faqs": [
    {"question": "string", "answer_brief": "string ≤200 chars"}
  ],
  "sample_draft_required": false,
  "internal_links_suggested": ["/blog/related-post-slug"],
  "product_cta": {
    "anchor_text": "string",
    "target_route": "/app/drafts/new?type=..."
  }
}
TXT;
    }
}
