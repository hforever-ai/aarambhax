#!/usr/bin/env python3
"""
Aarambhax Legal — Vision / About / Privacy / Contact content generator.

Uses Gemini 2.5 Flash to draft legally-defensible markdown content for:
  - Vision (parent company AI Aarambh + Shrutam.ai education + Aarambhax legal)
  - About Us
  - Privacy Policy (legal-AI-grade — confidentiality, advocate-client privilege, AI processing)
  - Contact Us

Run:
  export GEMINI_API_KEY=<key>
  python scripts/generate_legal_pages.py

Output:
  database/content/pages/{vision,about,privacy,contact}.md
"""
from __future__ import annotations

import os
import sys
import time
from pathlib import Path

try:
    from google import genai
    from google.genai import types
except ImportError:
    print("ERROR: pip install google-genai", file=sys.stderr)
    sys.exit(1)

PROJECT_ROOT = Path(__file__).parent.parent
OUTPUT_DIR = PROJECT_ROOT / "database" / "content" / "pages"

MODEL = "gemini-2.5-flash"

# ── Brand context every prompt receives ────────────────────────────────────
BRAND_CONTEXT = """COMPANY CONTEXT (use this exactly, do NOT invent details):

Parent vision: AI Aarambh — a family of AI-powered tools for India.

Sister product (already launched): Shrutam.ai
  - Free, basic education for grades 1-10
  - Covers all Indian boards (CBSE, ICSE, state boards)
  - All major Indian languages (Hindi, English, Marathi, Telugu, Hinglish, etc.)
  - Voice-first character-driven learning ("Saavi didi" persona)
  - Mission: every Indian child gets quality education, in their language, free

This product: Aarambhax Legal
  - AI-powered legal drafting tool for Indian advocates
  - Initial focus: Chhattisgarh High Court (Bilaspur), district & sessions courts, revenue courts
  - Supports new criminal codes (BNS / BNSS / BSA from 1 July 2024) plus IPC/CrPC for older cases
  - Hindi + English drafting, citation Verifier, conversation-memory editor
  - Multi-agent pipeline (Ingestor → Fact Architect → Law Researcher → Drafter+Critic)
  - Beta phase, free during beta

The thread connecting both: Aarambh = "beginning". Both Shrutam.ai and Aarambhax
exist to give Indians a fair beginning — children to education, advocates to professional-grade tools.

Brand voice: professional, warm, honest, restrained. NEVER overclaim. Acknowledge AI
limitations openly. Indian English (not American). No emojis. No hyperbole.

LEGAL SENSITIVITY (critical for THIS product):
- We handle advocate-client privileged information
- We process PII (FIR numbers, accused names, addresses, photographs, judgments)
- We are NOT a law firm; we provide drafting tools, not legal advice
- Bar Council of India ethics rules apply to our advocate users
- AI-generated drafts ALWAYS require advocate review before filing
- We must be transparent about which AI provider we use (Google Gemini via Vertex AI)
- Free tier vs paid tier privacy distinction matters
- DPDP Act 2023 (India's data-protection law) compliance required
- Retention, deletion, and consent must be clearly stated"""


# ── 4 page prompts ─────────────────────────────────────────────────────────

PAGES = {
    "vision": """Write the **Vision** page for Aarambhax Legal.

Structure:
1. Headline (one line) — the core idea of "Aarambh" (beginning) connecting both products
2. Two-paragraph intro: parent company AI Aarambh, our belief about access + AI in India
3. ## Shrutam.ai — what we already do
   2-3 paragraphs about Shrutam.ai. It's already launched. Free education grades 1-10, all boards, all languages. Voice-first. Mission: every Indian child gets quality education in their language, free. Mention Saavi as the character voice.
4. ## Aarambhax Legal — what we're building now
   2-3 paragraphs. Why we extended into legal. Indian advocates spend hours on drafting that AI can accelerate without replacing their judgment. CG High Court Bilaspur is our wedge. Hindi + Revenue Court support that no other Indian legal AI offers. Beta.
5. ## What unites them
   2 paragraphs. The "beginning" thread. Children get a beginning into knowledge; advocates get a beginning into faster, more confident drafting. Both stay free or low-cost. Both built in India for Indians.
6. ## What we will not do
   - Not a law firm
   - Not a substitute for legal advice
   - Not a black box — every AI step is auditable
   - Not careless with privacy (this is legal data)
7. Closing: short, sincere paragraph about being early-stage and learning from users.

Output PURE markdown. ~700 words. Indian English. Honest, restrained tone. No emojis. No marketing-speak.""",

    "about": """Write the **About Us** page for Aarambhax Legal.

Structure:
1. Lead paragraph: who we are in plain words. Parent: AI Aarambh. Two products: Shrutam.ai (live, education, free, all boards/languages, grades 1-10) and Aarambhax Legal (this one, beta, legal drafting for Indian advocates).
2. ## Why Aarambhax exists
   2 paragraphs. Indian advocates spend disproportionate time on formulaic parts of drafts (cause-titles, synopsis, list of dates, prayer, affidavit). AI can do those in seconds, leaving advocates to focus on the legal reasoning that needs human judgment. We started with Chhattisgarh because the founder is connected to a practising advocate at CG HC Bilaspur.
3. ## What makes us different
   Bullet list of 6 points:
   - Hindi + English drafting (most legal AI is English-only)
   - Revenue Court support (zero competitors do this)
   - CG-specific formats (CG HC Rules 2007, Mahanadi Bhawan addresses)
   - Citation Verifier with green/amber/red badges
   - Conversation memory across edits (fixes the "context lost" bug other tools have)
   - Multi-agent pipeline (Ingestor + Fact Architect + Law Researcher + Drafter)
4. ## Our team
   Honest one-liner: small team, beta phase. We're learning from every advocate who uses it.
5. ## Backing & technology
   We use Google Gemini (via Google's Vertex AI for paid/private routing of any client data) and Google Imagen for editorial images. We do not sell client data. We do not use free-tier AI for any document containing PII.
6. ## Where we are
   Based in India. Built for Indian courts. Initial focus: Chhattisgarh.

Output PURE markdown. ~600 words. Indian English. No emojis. No buzzwords. Honest about beta-stage maturity.""",

    "privacy": """Write a **Privacy Policy** for Aarambhax Legal — a legal-AI tool that handles
advocate-client privileged information.

This must be defensible under India's DPDP Act 2023. Cover at minimum:

1. **Effective date** + last-updated stamp (use placeholder `Last updated: [DATE]`)
2. **Identity of data fiduciary**: AI Aarambh (parent) and contact email `privacy@aarambhax.net`
3. ## Data we collect
   - Account information: name, email, password (hashed), Bar Council enrolment number (optional), phone (optional), chamber address (optional), signature blocks
   - Case data: case titles, party names, FIR numbers, court names, dates, sections, key facts, uploaded documents (PDFs, judgments, FIRs, khasra/khata records, photos)
   - Usage analytics: counts only, never content (which routes/features used, draft counts, error logs)
   - Cookies: only essential session cookies; no third-party trackers
4. ## How we use this data
   - Generate drafts via AI agents
   - Send hearing reminders via Telegram (only if user opts in)
   - Improve our prompts and pipeline (only over anonymised aggregates)
   - We do NOT sell data, do NOT share with advertisers, do NOT use it to train Google's general models
5. ## Where data is processed (CRITICAL — list explicitly)
   - Stored in our database on a private VPS in India (Hostinger)
   - AI processing routed through Google Vertex AI on a paid project (which means Google does NOT use our prompts to train models, per their paid-tier privacy commitment)
   - We never send PII through the AI Studio free tier (which may use prompts for training)
   - Telegram bot: hearing-reminder notifications only; no case data shared
6. ## Advocate-client confidentiality
   Plain commitment: we treat every uploaded document as privileged. Access logs are kept. We disclose to authorities only on a valid court order, and we will inform the user unless legally prohibited.
7. ## Retention
   - Drafts and case data: kept until you delete the case OR ask us to delete (DPDP §9)
   - Account data: kept while account is active + 90 days after deletion request
   - Backups: encrypted, rotated every 90 days
   - You can request a full export of your data at any time
8. ## Your rights under DPDP Act 2023
   - Right to access your data
   - Right to correction
   - Right to erasure
   - Right to grievance redressal
   - Right to nominate (next of kin access on death)
   - How to exercise: email `privacy@aarambhax.net` (response within 30 days, per the Act)
9. ## Children
   We do not knowingly collect data from anyone under 18.
10. ## Security
    - HTTPS everywhere
    - Database backups encrypted
    - Passwords hashed with bcrypt
    - VPS hardened, access logs kept
    - We will notify users + the Data Protection Board of India of any personal-data breach within 72 hours per DPDP §8(6)
11. ## Changes to this policy
    Posted on this page; material changes notified by email
12. ## Contact
    Data Protection Officer: `privacy@aarambhax.net`
    Grievance Officer (DPDP §13): name + email + designation (use placeholder `[Grievance Officer Name], grievance@aarambhax.net`)
    Postal address: `[Add registered office address]`

Output PURE markdown. ~1500-2000 words. Plain English. No legalese where avoidable. India-specific (DPDP Act, not GDPR/CCPA). No emojis.""",

    "contact": """Write the **Contact Us** page for Aarambhax Legal.

Keep it short and useful. Structure:

1. Lead paragraph: encourage advocates to reach out — feedback is how we improve.
2. ## Get in touch
   - General questions: `hello@aarambhax.net`
   - Beta feedback or feature requests: `feedback@aarambhax.net`
   - Privacy / data requests: `privacy@aarambhax.net`
   - Press / partnerships: `press@aarambhax.net`
3. ## Where we are
   - Based in India
   - Building for Chhattisgarh courts first, then expanding
   - Parent company: AI Aarambh (also operates Shrutam.ai for free K-10 education)
4. ## Office hours
   Honest line about being a small team — we read every email; reply within 1-2 working days.
5. ## Form below
   One paragraph telling them they can also use the form on this page (form is rendered separately by Laravel — just reference it).
6. ## Other ways to follow us
   - Blog: `aarambhax.net/blog`
   - Free Citation Verifier: `aarambhax.net/verifier`
   - Sister product Shrutam.ai for free school education

Output PURE markdown. ~250-300 words. No emojis. Friendly but professional.""",
}


def get_client() -> genai.Client:
    key = os.environ.get("GEMINI_API_KEY")
    if not key:
        sys.exit("ERROR: set GEMINI_API_KEY")
    return genai.Client(api_key=key)


def generate_page(client: genai.Client, name: str, prompt: str) -> str | None:
    print(f"\n→ Generating {name}.md ...")
    full_prompt = f"{BRAND_CONTEXT}\n\n---\n\n{prompt}"
    try:
        response = client.models.generate_content(
            model=MODEL,
            contents=full_prompt,
            config=types.GenerateContentConfig(
                temperature=0.4,
            ),
        )
        text = response.text or ""
        # Strip code fences if Gemini wrapped output
        if text.startswith("```"):
            text = text.split("\n", 1)[-1]
            if text.endswith("```"):
                text = text.rsplit("```", 1)[0]
        text = text.strip()
        if len(text) < 200:
            print(f"  ✗ Output too short ({len(text)} chars)")
            return None
        return text
    except Exception as e:
        print(f"  ✗ Failed: {str(e)[:200]}")
        return None


def main() -> int:
    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    client = get_client()

    success = 0
    for name, prompt in PAGES.items():
        out = OUTPUT_DIR / f"{name}.md"
        if out.exists():
            print(f"\n[skip] {out.relative_to(PROJECT_ROOT)} already exists")
            success += 1
            continue
        text = generate_page(client, name, prompt)
        if text:
            out.write_text(text)
            print(f"  ✓ saved {out.relative_to(PROJECT_ROOT)} ({len(text)} chars)")
            success += 1
        time.sleep(3)

    print(f"\nDone. {success}/{len(PAGES)} pages generated.")
    return 0 if success == len(PAGES) else 1


if __name__ == "__main__":
    sys.exit(main())
