#!/usr/bin/env python3
"""
Aarambhax Legal — Marketing & Blog Image Generator (Imagen 4 free tier)
========================================================================

Generates:
  - Homepage hero banner (16:9)
  - Open Graph social card (1200×630, manually overlay text later or use Imagick)
  - Blog post hero images (16:9, one per launch post)
  - Pillar landing thumbnails (square)

Locked brand prompt template ensures every image feels like the same brand.

PREREQUISITES:
  pip install google-genai
  export GEMINI_API_KEY="<rotated-key>"

USAGE:
  # Generate everything in one run (~22 free-tier requests)
  python scripts/generate_images.py all

  # Or one category at a time
  python scripts/generate_images.py hero       # Homepage banner
  python scripts/generate_images.py blog       # Blog post heroes (one per launch post)
  python scripts/generate_images.py pillars    # Category thumbnails (5)

OUTPUT:
  public/images/hero/...
  public/images/blog/<slug>.png
  public/images/pillars/<category-slug>.png
"""
from __future__ import annotations

import argparse
import os
import sys
from pathlib import Path

try:
    from google import genai
    from google.genai import types
except ImportError:
    print("ERROR: pip install google-genai", file=sys.stderr)
    sys.exit(1)

PROJECT_ROOT = Path(__file__).parent.parent
PUBLIC_DIR = PROJECT_ROOT / "public" / "images"

NAVY  = "#0B1F3A"
GOLD  = "#C8A24B"
CREAM = "#F8F6F0"

# Locked editorial style — every Aarambhax image follows this
EDITORIAL_STYLE = (
    f"Editorial illustration in a semi-flat vector style, deep navy {NAVY} "
    f"and warm gold {GOLD} accents on a warm cream {CREAM} background. "
    f"No photorealism. No people's faces. No text. No watermarks. Minimal, "
    f"professional, editorial quality. Clean lines, generous padding, "
    f"thoughtful composition. The visual language of a serious Indian legal "
    f"publication — heritage and modern at once."
)

NEGATIVE = (
    "text, letters, words, typography, watermark, signature, logos, "
    "human faces, hands, photorealistic, photographs, "
    "stock photography, generic AI imagery, robots, brain, circuit, "
    "scales of justice cliche, gavel, courthouse pillars, "
    "cluttered, busy, multiple unrelated objects, "
    "shadows that look 3d, lens flare, glow effects, "
    "saturated bright colors, neon"
)


# Hero banner for homepage (16:9)
HERO_PROMPTS = [
    {
        "name": "hero_v1",
        "prompt": (
            f"{EDITORIAL_STYLE} An open legal book viewed from a low angle on the left, "
            f"with a single page lifting and dissolving into geometric particles flowing "
            f"to the right side of the frame, suggesting AI-assisted drafting. The book "
            f"is rendered in deep navy line art with subtle warm gold highlights on the "
            f"lifting page. The right side has abstract geometric shapes — clean rectangles "
            f"and lines — suggesting structured output. Wide horizontal composition. "
            f"Plenty of negative space top-right for headline text overlay."
        ),
    },
    {
        "name": "hero_v2",
        "prompt": (
            f"{EDITORIAL_STYLE} Three layered scrolls of paper at gentle angles, each "
            f"with a single thin gold line representing structured text. The scrolls "
            f"overlap subtly. A small geometric dot pattern in gold flows above them like "
            f"data points. Wide horizontal composition. Deep navy and warm cream only. "
            f"Editorial poster aesthetic."
        ),
    },
]

# Blog hero per launch post — keyed by slug
BLOG_HERO_PROMPTS = {
    "ipc-to-bns-section-mapping": (
        f"{EDITORIAL_STYLE} Two stacks of legal books on either side of the frame, "
        f"connected by gold geometric lines suggesting transformation or mapping. "
        f"Left stack labeled abstractly with darker tones, right stack with subtle "
        f"warm gold accents. Wide horizontal composition. The visual metaphor is "
        f"section-to-section conversion."
    ),
    "crpc-to-bnss-criminal-lawyer-2026": (
        f"{EDITORIAL_STYLE} A long unfolded scroll across the frame with gentle "
        f"perspective. Geometric rectangular shapes representing chapters laid out "
        f"along its length, in deep navy with gold separators. Wide horizontal."
    ),
    "anticipatory-bail-bnss-482": (
        f"{EDITORIAL_STYLE} A symbolic shield shape rendered as clean geometric "
        f"navy outlines, with a small gold key suggested by a circle and line "
        f"across its center. Wide horizontal composition. Concept of legal protection."
    ),
    "vakalatnama-format-district-high-court": (
        f"{EDITORIAL_STYLE} A formal legal document outline at a slight angle, "
        f"with a clean navy quill resting on it. The signature line is highlighted "
        f"with a thin gold accent. Wide horizontal."
    ),
    "naamantaran-cg-lrc-109": (
        f"{EDITORIAL_STYLE} An aerial-view abstraction of agricultural land plots — "
        f"clean rectangles in different sizes representing khasra parcels, with "
        f"thin gold lines connecting two of them suggesting transfer. Deep navy "
        f"on cream. Wide horizontal."
    ),
    "cg-hc-efiling-checklist-2026": (
        f"{EDITORIAL_STYLE} A neat vertical column of small geometric document "
        f"icons on the left, each with a small gold checkmark mark. Plenty of "
        f"negative space on the right. Wide horizontal."
    ),
    "batwara-cg-lrc-178": (
        f"{EDITORIAL_STYLE} A single large rectangle being divided by clean "
        f"diagonal navy lines into smaller parcels of varying sizes. Thin gold "
        f"accents at each division point. Concept of legal partition. Wide horizontal."
    ),
    "bail-bnss-483-vs-482": (
        f"{EDITORIAL_STYLE} Two parallel paths rendered as clean navy lines "
        f"diverging from a single point on the left, with small gold geometric "
        f"markers along each. Concept of decision tree. Wide horizontal."
    ),
    "quashing-fir-bnss-528": (
        f"{EDITORIAL_STYLE} A formal document with a single gold diagonal slash "
        f"across it, rendered cleanly with no aggression. The slash suggests "
        f"quashing without violence. Deep navy and cream. Wide horizontal."
    ),
    "ni-act-138-complaint-format": (
        f"{EDITORIAL_STYLE} A stylized rectangular cheque shape in clean navy "
        f"line art with a small gold X mark in the corner suggesting dishonour. "
        f"Wide horizontal composition."
    ),
}

# Pillar thumbnails (square, for category landing pages later)
PILLAR_PROMPTS = {
    "new-criminal-codes": (
        f"{EDITORIAL_STYLE} Three vertical legal book spines in gentle parallel "
        f"perspective, deep navy with thin gold accent lines. Square 1:1."
    ),
    "drafting-walkthroughs": (
        f"{EDITORIAL_STYLE} A clean fountain pen at an angle with a single gold "
        f"line representing the stroke. Deep navy on cream. Square 1:1."
    ),
    "revenue-court": (
        f"{EDITORIAL_STYLE} An abstract aerial view of geometric land parcels in "
        f"different rectangle sizes. Navy outlines, gold connection lines. Square 1:1."
    ),
    "court-tech": (
        f"{EDITORIAL_STYLE} A simplified document icon overlapping with a circle "
        f"suggesting digital signature. Navy and gold. Square 1:1."
    ),
    "product": (
        f"{EDITORIAL_STYLE} A minimal abstract representation of stacked layers — "
        f"three rectangles offset like document versions. Navy with gold accent on "
        f"the top one. Square 1:1."
    ),
}

MODEL = "imagen-4.0-generate-001"  # Standard quality for all marketing assets


def get_client():
    key = os.environ.get("GEMINI_API_KEY")
    if not key:
        sys.exit("ERROR: set GEMINI_API_KEY")
    return genai.Client(api_key=key)


def generate_one(client, prompt, out_path: Path, aspect: str = "16:9"):
    out_path.parent.mkdir(parents=True, exist_ok=True)
    # Free-tier Imagen 4 doesn't support negative_prompt — bake exclusions into prompt itself.
    full_prompt = prompt + " EXCLUDE: text, letters, watermarks, logos, human faces, photorealistic, stock photography, gavel, scales of justice cliche, cluttered design, neon colors."
    try:
        response = client.models.generate_images(
            model=MODEL,
            prompt=full_prompt,
            config=types.GenerateImagesConfig(
                number_of_images=1,
                aspect_ratio=aspect,
                person_generation="dont_allow",
            ),
        )
    except Exception as e:
        print(f"  [err] {e}")
        return False
    for gen in response.generated_images:
        with open(out_path, "wb") as f:
            f.write(gen.image.image_bytes)
        print(f"  [ok] {out_path.relative_to(PROJECT_ROOT)}")
        return True
    return False


def cmd_hero(client):
    print("Generating homepage hero banners (16:9)...")
    for spec in HERO_PROMPTS:
        out = PUBLIC_DIR / "hero" / f"{spec['name']}.png"
        if out.exists():
            print(f"  [skip] {out.name} exists")
            continue
        generate_one(client, spec["prompt"], out, aspect="16:9")


def cmd_blog(client):
    print("Generating blog post heroes (16:9)...")
    for slug, prompt in BLOG_HERO_PROMPTS.items():
        out = PUBLIC_DIR / "blog" / f"{slug}.png"
        if out.exists():
            print(f"  [skip] {slug} exists")
            continue
        generate_one(client, prompt, out, aspect="16:9")


def cmd_pillars(client):
    print("Generating pillar thumbnails (1:1)...")
    for slug, prompt in PILLAR_PROMPTS.items():
        out = PUBLIC_DIR / "pillars" / f"{slug}.png"
        if out.exists():
            print(f"  [skip] {slug} exists")
            continue
        generate_one(client, prompt, out, aspect="1:1")


def cmd_all(client):
    cmd_hero(client)
    cmd_blog(client)
    cmd_pillars(client)
    print("\nDone. Update posts/categories with hero_image_url paths in Filament admin.")
    print("Or run: php artisan db:seed --class=ImagePathSeeder")


def main():
    parser = argparse.ArgumentParser()
    sub = parser.add_subparsers(dest="cmd", required=True)
    sub.add_parser("all")
    sub.add_parser("hero")
    sub.add_parser("blog")
    sub.add_parser("pillars")
    args = parser.parse_args()
    client = get_client()
    {"all": cmd_all, "hero": cmd_hero, "blog": cmd_blog, "pillars": cmd_pillars}[args.cmd](client)


if __name__ == "__main__":
    main()
