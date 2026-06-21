#!/usr/bin/env python3
"""
Aarambhax Legal — Logo Mark Generator (Imagen 4 family, free tier)
==================================================================

3-stage pipeline to explore, refine, and finalize a logo mark using
the free Imagen 4 daily quota (25/day per model, 75 total).

PREREQUISITES:
  pip install google-genai
  export GEMINI_API_KEY="<rotated-key-from-aistudio.google.com/apikey>"

USAGE:
  # Stage 1 — explore 3 directions × 4 variants = 12 PNGs (Imagen Fast)
  python scripts/generate_logo.py explore

  # Browse logo_marks/01_explore/, pick the direction you like

  # Stage 2 — refine 1 direction × 4 variants (Imagen Standard)
  python scripts/generate_logo.py refine --direction monogram_a

  # Stage 3 — finalize 2 ultra-quality candidates (Imagen Ultra)
  python scripts/generate_logo.py finalize --direction monogram_a

After Stage 3:
  1. Vectorize chosen PNG via vectorizer.ai (free tier)
  2. Place SVG at public/logo-mark.svg
  3. Update components/logo.blade.php to include the mark
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
OUTPUT_DIR = PROJECT_ROOT / "logo_marks"

NAVY = "#0B1F3A"
GOLD = "#C8A24B"

NEGATIVE = (
    "text, letters, words, typography, watermark, signature, "
    "scales of justice, gavel, hammer, blindfolded woman, lady justice, "
    "courthouse building, columns, pillars, "
    "human figures, faces, hands, "
    "shadows, gradients, glow, lens flare, 3d, photorealistic, "
    "decorative flourishes, ornaments, embellishments, "
    "circuit board, brain, robot, AI imagery, "
    "cluttered, busy, complex, multiple colors"
)

DIRECTIONS = {
    "monogram_a": {
        "label": "Monogram A — geometric serif column",
        "prompt": (
            f"A minimal logo mark: a single capital letter A in a custom geometric serif "
            f"typeface. The A is tall, narrow, and elegant — like a classical column. "
            f"Solid deep navy color {NAVY}. The horizontal crossbar of the A is a thin "
            f"line in warm gold {GOLD}. Pure white background. Flat vector design. "
            f"Sharp clean edges. Generous symmetric padding. Professional brand mark "
            f"for a high-end legal services company. Centered composition. Minimal."
        ),
    },
    "abstract_scales": {
        "label": "Abstract scales — two horizontal bars",
        "prompt": (
            f"A minimalist legal brand mark: two solid horizontal rectangles stacked "
            f"vertically with negative space between them. The top rectangle is "
            f"slightly shorter than the bottom one. Both rectangles are deep navy "
            f"{NAVY}. A single thin vertical gold line {GOLD} connects the centers "
            f"of the two rectangles. Pure white background. Flat geometric vector "
            f"design. Sharp clean edges. Generous padding. Modern minimal."
        ),
    },
    "lifted_page": {
        "label": "Open book with lifting page",
        "prompt": (
            f"A minimal flat icon: an open book viewed from above as simple geometric "
            f"line art in deep navy {NAVY}. A single page on the right side gently "
            f"lifts upward, drawn as a thin curved line in warm gold {GOLD}. Clean "
            f"geometric outlines only. Pure white background. Flat vector design. "
            f"No detail inside the book. No shadows. Generous padding. Centered."
        ),
    },
}

MODELS = {
    "fast":     "imagen-4.0-fast-generate-001",
    "standard": "imagen-4.0-generate-001",
    "ultra":    "imagen-4.0-ultra-generate-001",
}


def get_client() -> genai.Client:
    key = os.environ.get("GEMINI_API_KEY")
    if not key:
        print("ERROR: set GEMINI_API_KEY (free at https://aistudio.google.com/apikey)", file=sys.stderr)
        sys.exit(1)
    return genai.Client(api_key=key)


def generate(client, model_key, prompt, count, out_prefix):
    out_prefix.parent.mkdir(parents=True, exist_ok=True)
    # Free-tier Imagen 4 doesn't support negative_prompt — bake exclusions into prompt itself.
    full_prompt = prompt + " EXCLUDE: text, letters, watermarks, scales of justice cliche, gavels, human figures, courthouse buildings, photorealistic style, 3d, gradients, neon, cluttered design."
    try:
        response = client.models.generate_images(
            model=MODELS[model_key],
            prompt=full_prompt,
            config=types.GenerateImagesConfig(
                number_of_images=count,
                aspect_ratio="1:1",
                person_generation="dont_allow",
            ),
        )
    except Exception as e:
        print(f"  [err] {e}")
        return 0
    saved = 0
    for i, gen in enumerate(response.generated_images, start=1):
        out = out_prefix.parent / f"{out_prefix.name}_v{i}.png"
        with open(out, "wb") as f:
            f.write(gen.image.image_bytes)
        print(f"  [ok]  {out.relative_to(PROJECT_ROOT)}")
        saved += 1
    return saved


def cmd_explore(client):
    out = OUTPUT_DIR / "01_explore"
    print(f"Stage 1 — Explore (Imagen 4 Fast) → {out.relative_to(PROJECT_ROOT)}/")
    for key, spec in DIRECTIONS.items():
        print(f"\n  Direction: {spec['label']}")
        generate(client, "fast", spec["prompt"], 4, out / key)


def cmd_refine(client, direction):
    if direction not in DIRECTIONS:
        sys.exit(f"Unknown direction. Choose: {list(DIRECTIONS)}")
    out = OUTPUT_DIR / "02_refine"
    spec = DIRECTIONS[direction]
    print(f"Stage 2 — Refine (Imagen 4 Standard): {spec['label']}")
    generate(client, "standard", spec["prompt"], 4, out / direction)


def cmd_finalize(client, direction):
    if direction not in DIRECTIONS:
        sys.exit(f"Unknown direction. Choose: {list(DIRECTIONS)}")
    out = OUTPUT_DIR / "03_final"
    spec = DIRECTIONS[direction]
    print(f"Stage 3 — Finalize (Imagen 4 Ultra): {spec['label']}")
    for i in range(1, 3):
        generate(client, "ultra", spec["prompt"], 1, out / f"{direction}_final{i}")


def main():
    parser = argparse.ArgumentParser()
    sub = parser.add_subparsers(dest="cmd", required=True)
    sub.add_parser("explore")
    p_r = sub.add_parser("refine"); p_r.add_argument("--direction", required=True)
    p_f = sub.add_parser("finalize"); p_f.add_argument("--direction", required=True)
    args = parser.parse_args()

    client = get_client()
    if args.cmd == "explore":  cmd_explore(client)
    elif args.cmd == "refine":   cmd_refine(client, args.direction)
    elif args.cmd == "finalize": cmd_finalize(client, args.direction)


if __name__ == "__main__":
    main()
