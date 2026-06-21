#!/usr/bin/env python3
"""
Aarambhax Legal — Creative SVG Logo Generator
==============================================

Asks Gemini 2.5 Pro to design 5 distinct creative SVG logo marks for
Aarambhax Legal. Each concept is a different visual direction.

Why SVG (not Imagen):
  - Imagen is paid-only on the free AI Studio tier
  - SVG is theme-adaptive (uses currentColor)
  - SVG scales perfectly, works inline, < 1KB each
  - Gemini Pro is excellent at vector design via SVG markup

Setup:
  export GEMINI_API_KEY="<key from .env>"

Run:
  python scripts/generate_logo_svgs.py

Output:
  public/images/logo-concepts/concept-1.svg
  public/images/logo-concepts/concept-2.svg
  ... (5 files)
  public/images/logo-concepts/index.json    (concept metadata)

Then visit /logo-gallery to compare and pick.
"""
from __future__ import annotations

import json
import os
import re
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
OUTPUT_DIR = PROJECT_ROOT / "public" / "images" / "logo-concepts"

NAVY = "#0B1F3A"
GOLD = "#C8A24B"


# ─── Brand brief shared across all concepts ─────────────────────────────────

BRAND_BRIEF = """Aarambhax Legal is an AI-powered legal drafting tool for Indian advocates,
specifically tuned for the Chhattisgarh High Court at Bilaspur, district courts, and revenue
courts. Tagline: "Drafts for every court."

Brand personality: heritage + modern, professional, trustworthy, distinctly Indian, restrained.

Brand colors:
- Deep Navy: #0B1F3A (authority, primary)
- Warm Gold: #C8A24B (accent — like a court emblem, NOT loud yellow)
- Cream: #F8F6F0 (background)

Typography vibe: Fraunces serif for the wordmark — modern Indian legal heritage, NOT
British Empire heavy.

The mark must work at:
- 24×24 pixel favicon
- 48×48 pixel nav icon
- 128×128 pixel marketing
- 1:1 square aspect ratio for all uses

Avoid clichés: NO scales of justice, NO gavels, NO Lady Justice statues, NO British
Empire columns, NO generic "AI brain", NO circuit boards.

Should feel: like the visual mark of a well-respected Indian advocate's chamber. Heritage,
restraint, dignity. The mark should hint at the meaning of "Aarambhax" — beginning, foundation,
opening — without being literal."""


# ─── 5 distinct visual directions ────────────────────────────────────────────

CONCEPTS = [
    {
        "id": "concept-1-monogram",
        "name": "Monogram A — Geometric Serif",
        "direction": (
            "A single bold capital letter A in a custom geometric serif typeface. The A "
            "should feel like a classical column — narrow, tall, elegant. Solid deep navy. "
            "The horizontal crossbar of the A is rendered in warm gold as a thin, refined "
            "line. Optional: subtle serifs at the base of the legs. Total composition is "
            "minimal — just the letterform on a white background."
        ),
    },
    {
        "id": "concept-2-arch",
        "name": "Heritage Arch + Negative Space A",
        "direction": (
            "A subtle architectural arch (the kind you see at heritage courthouses in "
            "India — semicircular at the top, vertical sides) rendered in deep navy. "
            "Inside the arch, the negative space forms a stylized capital letter A or "
            "the inverted-V shape suggesting it. A single warm gold horizontal element "
            "anchors the bottom of the arch. The composition feels like both a doorway "
            "(beginning, aarambhax) and a letterform."
        ),
    },
    {
        "id": "concept-3-quill-stroke",
        "name": "Single Stroke Quill / Fountain Pen Tip",
        "direction": (
            "A single elegant brush-stroke that resolves into a fountain pen / quill tip "
            "at one end. Deep navy stroke. The nib catches a small gold dot of ink, OR a "
            "thin gold line trails behind the stroke as if writing. Composition is "
            "minimal, calligraphic, suggests writing the first word of a legal draft. "
            "Avoid making it look like a feathered quill — keep it modern, clean."
        ),
    },
    {
        "id": "concept-4-folded-page",
        "name": "Folded Manuscript Page",
        "direction": (
            "An abstract geometric representation of a folded sheet of paper — like a "
            "legal manuscript with a corner deliberately turned over. The base sheet is "
            "deep navy (or its outline is), the folded corner reveals a triangular "
            "shape. A single thin gold line runs across the base sheet suggesting the "
            "first line of text. The fold is precisely geometric — rendered as clean "
            "triangles, not soft curves."
        ),
    },
    {
        "id": "concept-5-emblem-circle",
        "name": "Circular Emblem with Devanagari A",
        "direction": (
            "A perfectly circular emblem-style mark in deep navy with a thin gold inner "
            "border ring. At the center, a single Devanagari character अ (the first "
            "letter of the Devanagari alphabet, also literally the 'aa' in Aarambhax) "
            "rendered in a clean modern style — NOT calligraphic flourishes, more like "
            "a contemporary Devanagari typeface. The character is gold against the navy "
            "background. The emblem feels like the seal of a respected legal chamber."
        ),
    },
]


SYSTEM_PROMPT = f"""You are a senior brand designer specialising in legal-tech and Indian
heritage brands. You design logo marks as clean, hand-coded SVG.

{BRAND_BRIEF}

OUTPUT REQUIREMENTS for every concept:
1. Pure SVG only — no PNG, no embedded images, no external references.
2. Single root <svg> element with viewBox="0 0 64 64" (so it renders crisp at any size).
3. Use ONLY these colors:
   - Deep navy: {NAVY}  (or use currentColor for theme-adaptiveness when dark mode flips)
   - Warm gold: {GOLD}
4. No <text> elements (the wordmark is rendered separately by the website).
5. No filters, no gradients with more than 2 stops, no animations, no scripts.
6. Total file size under 1.5 KB.
7. The mark must be visually distinguishable at 24×24 pixels.
8. Must work as a favicon (no thin lines under 1px stroke at 64×64 base).
9. Provide ONLY the SVG markup — no commentary, no markdown code fences, no explanation.
   Start your response with <svg and end with </svg>.

Quality bar: this should look professionally designed, not AI-generated."""


def get_client() -> genai.Client:
    key = os.environ.get("GEMINI_API_KEY")
    if not key:
        sys.exit("ERROR: set GEMINI_API_KEY (free key from https://aistudio.google.com/apikey)")
    return genai.Client(api_key=key)


def clean_svg(text: str) -> str | None:
    """Extract just the SVG markup — strip code fences, prose, etc."""
    text = text.strip()
    # Strip ``` fences
    fence_match = re.search(r"```(?:svg|xml|html)?\s*(<svg[\s\S]+?</svg>)\s*```", text, re.IGNORECASE)
    if fence_match:
        return fence_match.group(1).strip()
    # Direct match
    direct_match = re.search(r"<svg[\s\S]+?</svg>", text, re.IGNORECASE)
    if direct_match:
        return direct_match.group(0).strip()
    return None


def generate_concept(client: genai.Client, concept: dict) -> dict:
    """Ask Gemini to generate one SVG concept. Returns metadata + SVG content."""
    user_prompt = f"""Design SVG logo concept #{concept['id']}: "{concept['name']}".

Visual direction:
{concept['direction']}

Return ONLY the <svg>...</svg> markup. Nothing else."""

    print(f"  → Generating: {concept['name']}")
    try:
        response = client.models.generate_content(
            model="gemini-2.5-pro",
            contents=user_prompt,
            config=types.GenerateContentConfig(
                system_instruction=SYSTEM_PROMPT,
                temperature=0.8,
            ),
        )
        text = response.text or ""
    except Exception as e:
        # Pro might be quota-limited; fall back to Flash
        print(f"     [Pro failed: {e}] — retrying on Flash")
        try:
            response = client.models.generate_content(
                model="gemini-2.5-flash",
                contents=user_prompt,
                config=types.GenerateContentConfig(
                    system_instruction=SYSTEM_PROMPT,
                    temperature=0.8,
                ),
            )
            text = response.text or ""
        except Exception as e2:
            return {**concept, "svg": None, "error": str(e2)}

    svg = clean_svg(text)
    if not svg:
        return {**concept, "svg": None, "error": "No <svg> markup found in response", "raw": text[:500]}

    # Sanity check — minimum viable SVG
    if len(svg) < 80 or "</svg>" not in svg.lower():
        return {**concept, "svg": None, "error": "SVG too short or malformed", "raw": svg[:300]}

    return {**concept, "svg": svg, "size_bytes": len(svg)}


def main() -> int:
    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    client = get_client()

    results: list[dict] = []
    for concept in CONCEPTS:
        result = generate_concept(client, concept)
        if result.get("svg"):
            out_path = OUTPUT_DIR / f"{concept['id']}.svg"
            out_path.write_text(result["svg"])
            print(f"     ✓ saved {out_path.relative_to(PROJECT_ROOT)} ({result['size_bytes']} bytes)")
            result["svg_path"] = f"images/logo-concepts/{concept['id']}.svg"
            # Don't write full SVG in metadata — just path
            del result["svg"]
        else:
            print(f"     ✗ failed: {result.get('error')}")
        results.append(result)
        time.sleep(2)  # rate-limit courtesy

    # Write metadata for the gallery page
    index_path = OUTPUT_DIR / "index.json"
    index_path.write_text(json.dumps(results, indent=2, ensure_ascii=False))
    print(f"\n✓ Wrote metadata to {index_path.relative_to(PROJECT_ROOT)}")
    print(f"\nNow visit:  http://127.0.0.1:8000/logo-gallery  to compare and pick.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
