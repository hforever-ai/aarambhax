#!/usr/bin/env python3
"""
Aarambhax Legal — Content Generator (Gemini 2.5 Flash, free tier)
==================================================================

Generates the 10 launch blog posts and 60 FAQs by calling Gemini 2.5 Flash.
Output is written to JSON files in `database/content/` which the Laravel
seeder consumes via `php artisan db:seed --class=GeneratedContentSeeder`.

WHY THIS LIVES OUTSIDE LARAVEL:
  - Long-running generation (~10-15 minutes for 10 posts) shouldn't block PHP
  - Easier retry logic, easier prompt iteration
  - Vikash bhai can re-run for one specific post without rebuilding everything

PREREQUISITES:
  pip install google-genai
  export GEMINI_API_KEY="<your rotated key from aistudio.google.com/apikey>"

USAGE:
  # Generate all 10 launch posts (English) — uses ~25 free-tier requests
  python scripts/generate_content.py posts

  # Generate one specific post by index
  python scripts/generate_content.py posts --only 3

  # Generate Hindi translations for posts that exist in English
  python scripts/generate_content.py translate --to hi

  # Generate all 60 FAQs
  python scripts/generate_content.py faqs

  # Test that your key works without burning quota
  python scripts/generate_content.py ping

OUTPUT:
  database/content/posts/<slug>.json
  database/content/faqs/<topic>.json

After generation, run from Laravel:
  php artisan db:seed --class=GeneratedContentSeeder
"""
from __future__ import annotations

import argparse
import json
import os
import re
import sys
import time
from dataclasses import asdict, dataclass
from pathlib import Path
from typing import Optional

try:
    from google import genai
    from google.genai import types
except ImportError:
    print("ERROR: pip install google-genai", file=sys.stderr)
    sys.exit(1)


# ─────────────────────────────────────────────────────────────────────────
# Configuration
# ─────────────────────────────────────────────────────────────────────────

PROJECT_ROOT = Path(__file__).parent.parent
OUTPUT_DIR = PROJECT_ROOT / "database" / "content"
POSTS_DIR = OUTPUT_DIR / "posts"
FAQS_DIR = OUTPUT_DIR / "faqs"

MODEL_FAST = "gemini-2.5-flash"
MODEL_PRO = "gemini-2.5-pro"

RATE_LIMIT_DELAY_SEC = 4  # ~15 RPM safety margin for Flash


# ─────────────────────────────────────────────────────────────────────────
# Launch posts (10) — locked from blog architecture spec
# ─────────────────────────────────────────────────────────────────────────

@dataclass
class PostSpec:
    slug: str
    title: str
    category_slug: str
    archetype: str
    target_words: int
    primary_keyword: str
    notes: str

LAUNCH_POSTS = [
    PostSpec(
        slug="ipc-to-bns-section-mapping",
        title="IPC to BNS: Section Mapping for the 100 Most-Cited Sections",
        category_slug="new-criminal-codes",
        archetype="section_mapping",
        target_words=1800,
        primary_keyword="IPC to BNS section mapping",
        notes="Side-by-side table for at least 30 most-cited sections. Cover top criminal sections (302, 307, 376, 420, 498A) plus procedural CrPC→BNSS (41, 161, 437, 438, 482).",
    ),
    PostSpec(
        slug="crpc-to-bnss-criminal-lawyer-2026",
        title="CrPC to BNSS: What Every Criminal Lawyer Must Know in 2026",
        category_slug="new-criminal-codes",
        archetype="comparison",
        target_words=2000,
        primary_keyword="CrPC to BNSS",
        notes="Focus on procedural changes that affect daily practice: arrest, bail, charge-sheet timelines, electronic evidence, video conferencing, mandatory forensic investigation.",
    ),
    PostSpec(
        slug="anticipatory-bail-bnss-482",
        title="How to Draft Anticipatory Bail Under BNSS §482",
        category_slug="drafting-walkthroughs",
        archetype="drafting_walkthrough",
        target_words=2500,
        primary_keyword="anticipatory bail BNSS 482",
        notes="Full step-by-step walkthrough. Include sample format. Cite Arnesh Kumar v. State of Bihar, Sushila Aggarwal v. State (NCT of Delhi). Common mistakes section mandatory.",
    ),
    PostSpec(
        slug="bnss-drafting-mistakes-5-common",
        title="5 Most-Common Drafting Mistakes Under BNSS (And How to Avoid Them)",
        category_slug="new-criminal-codes",
        archetype="checklist",
        target_words=1100,
        primary_keyword="BNSS drafting mistakes",
        notes="Listicle format. Each mistake gets ~200 words: what advocates do wrong, why it gets returned at filing counter, how to fix. Examples: citing IPC sections in post-July-2024 FIR drafts, wrong section prefix, missing electronic-evidence provisions, BNSS §35 arrest-procedure references.",
    ),
    PostSpec(
        slug="naamantaran-cg-lrc-109",
        title="Naamantaran Application Under CG LRC §109: Complete Drafting Guide",
        category_slug="revenue-court",
        archetype="drafting_walkthrough",
        target_words=2200,
        primary_keyword="naamantaran CG LRC 109",
        notes="Hindi-first reasoning. Include sample format with khasra/khata/khatauni fields. Cover sale, inheritance, gift basis. Explain Tehsildar process and appeal hierarchy.",
    ),
    PostSpec(
        slug="cg-hc-efiling-checklist-2026",
        title="CG HC e-Filing: Step-by-Step Checklist for First-Time Filers (2026)",
        category_slug="court-tech",
        archetype="checklist",
        target_words=1500,
        primary_keyword="CG HC e-filing checklist",
        notes="Numbered checklist. Cover DSC requirements, PDF/A format, file size limits, mandatory rules under CG HC Rules 2007. Reference highcourt.cg.gov.in filing rules.",
    ),
    PostSpec(
        slug="batwara-cg-lrc-178",
        title="Batwara (Partition) Application Under CG LRC §178: Format and Procedure",
        category_slug="revenue-court",
        archetype="drafting_walkthrough",
        target_words=2000,
        primary_keyword="batwara CG LRC 178",
        notes="Hindi-first. Distinguish from civil partition suit. Cover co-owner rights, Tehsildar jurisdiction, mandatory fields, supporting documents (khasra, B-1, P-II).",
    ),
    PostSpec(
        slug="cg-hc-recent-drafting-relevant-judgments",
        title="Chhattisgarh High Court — 3 Recent Judgments That Affect Your Drafting",
        category_slug="court-tech",
        archetype="case_study",
        target_words=1200,
        primary_keyword="CG High Court recent judgments",
        notes="Roundup format. Pick 3 recent (last 3-6 months) CG HC or SC judgments that practically affect drafting. For each: 1-line ratio, what changed in practice, sample updated phrasing. Choose judgments on bail, quashing, or revenue matters. Use [VERIFY] for any specific case names — Vikash bhai will fill real recent judgments.",
    ),
    PostSpec(
        slug="quashing-fir-bnss-528",
        title="Quashing of FIR under BNSS §528 (formerly CrPC §482): Drafting Guide",
        category_slug="new-criminal-codes",
        archetype="drafting_walkthrough",
        target_words=2400,
        primary_keyword="quashing FIR BNSS 528",
        notes="Cite State of Haryana v. Bhajan Lal grounds. Sample format. When HC will quash vs decline. Difference under new code if any. Mandatory section on inherent powers preserved.",
    ),
    PostSpec(
        slug="ni-act-138-complaint-format",
        title="NI Act §138 Complaint: Updated Format Post-2024 Amendments",
        category_slug="drafting-walkthroughs",
        archetype="format_sample",
        target_words=1600,
        primary_keyword="NI Act 138 complaint format",
        notes="Cover post-2024 procedural updates if any. Sample complaint format. Cheque return memo references. Statutory notice requirements (15+15 days).",
    ),
]


# ─────────────────────────────────────────────────────────────────────────
# FAQ topics (60 across 5 pillars × 12 each)
# ─────────────────────────────────────────────────────────────────────────

FAQ_TOPICS = {
    "bns-bnss": "the new criminal codes (BNS / BNSS / BSA) — section mappings, transitions, procedural changes",
    "drafting": "general legal drafting for Indian advocates — formats, vakalatnama, plaints, written statements, affidavits",
    "revenue": "Revenue Court practice in Chhattisgarh — naamantaran, batwara, vyapvartan, khasra/khata records, CG LRC sections",
    "court-tech": "Indian court technology — eCourts portal, CG HC e-filing, digital signatures, case status",
    "product": "the Aarambhax Legal product — features, pricing, languages, supported courts, the Verifier",
}


# ─────────────────────────────────────────────────────────────────────────
# Prompt templates
# ─────────────────────────────────────────────────────────────────────────

POST_SYSTEM = """You are a legal writer for Aarambhax Legal, a publication serving Indian advocates practicing in Chhattisgarh.

VOICE:
- Direct, professional, second-person ("you draft", not "one drafts")
- No greetings, no "in conclusion", no fluff
- Short paragraphs (2-4 sentences)
- Concrete examples over abstract principles

FORMAT:
- Markdown with H2 (##) and H3 (###) headings
- Cite statutes inline as "BNSS §482" (no spaces around §)
- Cite judgments as "Party v. State, (Year) Vol Reporter Page"
- Use markdown tables for comparisons
- Use code blocks for actual draft samples

LEGAL ACCURACY (CRITICAL):
- Use post-July-2024 codes (BNS / BNSS / BSA) for new matters
- Reference IPC / CrPC / Indian Evidence Act only when comparing or for pre-July-2024 cases
- NEVER invent section numbers, judgment names, or case citations
- If uncertain about a citation, write "[VERIFY]" inline — the editor will check
- Use Devanagari for Hindi legal terms even in English text: "vakalatnama (वकालतनामा)", "naamantaran (नामांतरण)"

OUTPUT:
- Pure markdown body only — no front-matter, no code-fence wrapper
- Start directly with the first paragraph (no #-level title; we add that separately)
- End with a blockquote CTA: "> **Generate this draft instantly with Aarambhax →**"
"""

POST_USER_TEMPLATE = """Write a complete blog post for Aarambhax Legal.

Title: {title}
Category: {category}
Archetype: {archetype}
Primary keyword: {primary_keyword}
Target length: {target_words} words (±15%)
Specific notes: {notes}

Structure required:
1. Opening (2-3 paragraphs setting up the problem)
2. Main content sections with H2 headings (at least 3)
3. A "Common Mistakes" section near the end (if archetype is drafting_walkthrough or section_mapping)
4. CTA blockquote at the very end

Return only the markdown body. Do not wrap in code blocks. Do not include a top-level title.
"""

FAQ_SYSTEM = """You are writing FAQs for Aarambhax Legal, a publication serving Indian advocates.

Each FAQ has a question and answer. The answer should:
- Be 80-200 words
- Answer the question directly in the first sentence
- Use markdown for emphasis when needed
- Cite specific sections (BNS / BNSS / BSA / CG LRC) when relevant
- Use Devanagari for Hindi legal terms inline
- Never invent statutes or judgments
- Be written for working advocates (assume legal knowledge)

OUTPUT: Strict JSON array of {{question, answer, related_statute_code, related_section_no}}.
"""

FAQ_USER_TEMPLATE = """Write 12 FAQs about: {topic_description}

Return strict JSON array. Each item has:
- question: string (≤200 chars, real practitioner-style question)
- answer: string (markdown, 80-200 words)
- related_statute_code: string or null (e.g. "BNSS", "CG LRC", null)
- related_section_no: string or null (e.g. "482", "109", null)

Mix difficulty: 4 beginner, 6 intermediate, 2 advanced. Cover the most-searched real practitioner questions, not theoretical ones.
"""


# ─────────────────────────────────────────────────────────────────────────
# Client
# ─────────────────────────────────────────────────────────────────────────

def get_client() -> genai.Client:
    key = os.environ.get("GEMINI_API_KEY")
    if not key:
        print("ERROR: set GEMINI_API_KEY", file=sys.stderr)
        print("       free key at https://aistudio.google.com/apikey", file=sys.stderr)
        sys.exit(1)
    return genai.Client(api_key=key)


def call_gemini(client: genai.Client, system: str, user: str, model: str = MODEL_FAST) -> str:
    response = client.models.generate_content(
        model=model,
        contents=user,
        config=types.GenerateContentConfig(
            system_instruction=system,
            temperature=0.7,
        ),
    )
    return response.text or ""


# ─────────────────────────────────────────────────────────────────────────
# Commands
# ─────────────────────────────────────────────────────────────────────────

def cmd_ping(client: genai.Client) -> int:
    print("Pinging Gemini 2.5 Flash...")
    out = call_gemini(client, "You are a test.", "Return only the literal word 'pong'.")
    print(f"Response: {out.strip()!r}")
    if "pong" in out.lower():
        print("✓ Gemini API key works.")
        return 0
    print("⚠ Unexpected response — key works but output strange.")
    return 0


def cmd_posts(client: genai.Client, only_index: Optional[int] = None) -> int:
    POSTS_DIR.mkdir(parents=True, exist_ok=True)
    posts = LAUNCH_POSTS if only_index is None else [LAUNCH_POSTS[only_index - 1]]

    for i, spec in enumerate(posts, start=1):
        out_path = POSTS_DIR / f"{spec.slug}.json"
        if out_path.exists() and only_index is None:
            print(f"[{i}/{len(posts)}] [skip] {spec.slug} already exists")
            continue

        print(f"[{i}/{len(posts)}] Generating: {spec.title[:60]}...")
        user = POST_USER_TEMPLATE.format(
            title=spec.title,
            category=spec.category_slug,
            archetype=spec.archetype,
            primary_keyword=spec.primary_keyword,
            target_words=spec.target_words,
            notes=spec.notes,
        )
        try:
            body = call_gemini(client, POST_SYSTEM, user)
        except Exception as e:
            print(f"  [err] {e}")
            continue

        # Build excerpt and meta automatically
        excerpt = " ".join(body.split()[:40])
        excerpt = re.sub(r"[#*_>\[\]`]", "", excerpt).strip()[:200]

        record = {
            **asdict(spec),
            "language": "en",
            "body": body,
            "excerpt": excerpt,
            "meta_title": f"{spec.title} | Aarambhax Legal"[:70],
            "meta_description": excerpt[:160],
            "reading_time_minutes": max(1, len(body.split()) // 220),
        }
        out_path.write_text(json.dumps(record, ensure_ascii=False, indent=2))
        print(f"  ✓ saved → {out_path.relative_to(PROJECT_ROOT)}")

        time.sleep(RATE_LIMIT_DELAY_SEC)

    print(f"\nDone. {len(posts)} post(s) generated in {POSTS_DIR.relative_to(PROJECT_ROOT)}/")
    print("Next: php artisan db:seed --class=GeneratedContentSeeder")
    return 0


def cmd_faqs(client: genai.Client) -> int:
    FAQS_DIR.mkdir(parents=True, exist_ok=True)

    for topic, description in FAQ_TOPICS.items():
        out_path = FAQS_DIR / f"{topic}.json"
        if out_path.exists():
            print(f"[skip] {topic} already exists")
            continue

        print(f"Generating FAQs for topic: {topic}...")
        user = FAQ_USER_TEMPLATE.format(topic_description=description)
        try:
            raw = call_gemini(client, FAQ_SYSTEM, user)
        except Exception as e:
            print(f"  [err] {e}")
            continue

        # Try to extract a JSON array even if Gemini wrapped it in markdown
        match = re.search(r"\[\s*\{.*\}\s*\]", raw, re.DOTALL)
        if not match:
            print(f"  [warn] no JSON array found in output for {topic}")
            (FAQS_DIR / f"{topic}.raw.txt").write_text(raw)
            continue

        try:
            faqs = json.loads(match.group(0))
        except json.JSONDecodeError as e:
            print(f"  [warn] JSON parse failed for {topic}: {e}")
            (FAQS_DIR / f"{topic}.raw.txt").write_text(raw)
            continue

        record = {"topic": topic, "language": "en", "faqs": faqs}
        out_path.write_text(json.dumps(record, ensure_ascii=False, indent=2))
        print(f"  ✓ saved {len(faqs)} FAQs → {out_path.relative_to(PROJECT_ROOT)}")

        time.sleep(RATE_LIMIT_DELAY_SEC)

    print(f"\nDone. FAQs in {FAQS_DIR.relative_to(PROJECT_ROOT)}/")
    return 0


def cmd_translate(client: genai.Client, target_lang: str = "hi") -> int:
    if target_lang != "hi":
        print(f"Only Hindi (hi) translation supported in v1.")
        return 1

    sources = sorted(POSTS_DIR.glob("*.json"))
    sources = [s for s in sources if not s.stem.endswith(".hi")]
    if not sources:
        print("No source posts found. Run `posts` first.")
        return 1

    SYSTEM_HI = """You are a legal translator for Aarambhax Legal. Translate English legal articles to Hindi (Devanagari) for Indian advocates.

Use vakeel-grade legal Hindi register matching CG District Court Hindi pleadings. NOT Sanskritized literary Hindi, NOT Hinglish.

DO NOT translate: statute names (BNSS, BNS, BSA, CG LRC, NI Act), case citations, section numbers, court names (use Hindi name then English in parentheses).

Standard vocabulary: petitioner=याचिकाकर्ता, respondent=प्रत्यर्थी, complainant=परिवादी, accused=अभियुक्त, plaintiff=वादी, defendant=प्रतिवादी, applicant=आवेदक, Hon'ble Court=माननीय न्यायालय, section=धारा, Act=अधिनियम, appeal=अपील, petition=याचिका, writ=रिट.

Preserve all markdown structure 1:1 (## headings, lists, tables, blockquotes). Translate only the content.
Return only the translated markdown."""

    for src in sources:
        target = POSTS_DIR / f"{src.stem}.hi.json"
        if target.exists():
            print(f"[skip] {src.stem} already translated")
            continue

        record = json.loads(src.read_text())
        print(f"Translating: {record['slug']}...")
        try:
            translated_body = call_gemini(client, SYSTEM_HI, record["body"], model=MODEL_PRO)
        except Exception as e:
            print(f"  [err] {e}")
            continue

        record["body"] = translated_body
        record["language"] = "hi"
        record["slug"] = record["slug"]  # keep same slug; controller distinguishes by language
        target.write_text(json.dumps(record, ensure_ascii=False, indent=2))
        print(f"  ✓ saved → {target.relative_to(PROJECT_ROOT)}")
        time.sleep(RATE_LIMIT_DELAY_SEC * 2)  # Pro tier has tighter limits

    return 0


# ─────────────────────────────────────────────────────────────────────────

def main() -> int:
    parser = argparse.ArgumentParser(description="Aarambhax content generator (Gemini 2.5 Flash)")
    sub = parser.add_subparsers(dest="cmd", required=True)

    sub.add_parser("ping", help="Test API key works")

    p_posts = sub.add_parser("posts", help="Generate the 10 launch posts (English)")
    p_posts.add_argument("--only", type=int, help="Generate only post N (1-10)")

    sub.add_parser("faqs", help="Generate the 60 FAQs (English)")

    p_tr = sub.add_parser("translate", help="Translate generated posts")
    p_tr.add_argument("--to", default="hi", choices=["hi"])

    args = parser.parse_args()
    client = get_client()

    if args.cmd == "ping":
        return cmd_ping(client)
    if args.cmd == "posts":
        return cmd_posts(client, only_index=args.only)
    if args.cmd == "faqs":
        return cmd_faqs(client)
    if args.cmd == "translate":
        return cmd_translate(client, target_lang=args.to)
    return 1


if __name__ == "__main__":
    sys.exit(main())
