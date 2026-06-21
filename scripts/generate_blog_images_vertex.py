#!/usr/bin/env python3
"""
Aarambhax Legal — Blog Hero Image Generator (Imagen 4 on Vertex AI)
====================================================================

Generates one 16:9 hero image per launch blog post using Imagen 4 on
Vertex AI (paid tier — works because shrutam-academic has billing enabled).

Why Vertex (not AI Studio):
  - Imagen is paid-only on the AI Studio free tier
  - Vertex bills ~$0.04 per 1024×1024 Standard image — 10 posts = ~$0.40
  - Auth via gcloud Application Default Credentials, no API key needed

Setup (one-time):
  gcloud auth application-default login
  # Make sure shrutam-academic project has Vertex AI API enabled + billing on

Run:
  python scripts/generate_blog_images_vertex.py

Output:
  public/images/blog/<post-slug>.png        (10 files, 16:9, ~600 KB each)

After generation, the seeder updates posts.hero_image_url to the new path.
"""
from __future__ import annotations

import base64
import json
import logging
import subprocess
import sys
import time
from pathlib import Path

import requests

logger = logging.getLogger(__name__)
logging.basicConfig(level=logging.INFO, format="%(asctime)s [%(levelname)s] %(message)s")

PROJECT_ROOT = Path(__file__).parent.parent
OUTPUT_DIR = PROJECT_ROOT / "public" / "images" / "blog"

# GCP project + location with Vertex AI enabled and billing on
GCP_PROJECT = "shrutam-academic"
GCP_LOCATION = "us-central1"
IMAGEN_MODEL = "imagen-4.0-generate-001"

# Locked editorial style — every Aarambhax image follows this
EDITORIAL_STYLE = (
    "Editorial illustration in a semi-flat vector style. Deep navy #0B1F3A "
    "and warm gold #C8A24B accents on a warm cream #F8F6F0 background. "
    "No photorealism. No people's faces. No text on the image. No watermarks. "
    "Minimal, professional, restrained, editorial quality. Clean lines, generous "
    "padding, thoughtful composition. The visual language of a serious Indian "
    "legal publication — heritage and modern at once. Vector aesthetic."
)

# 10 blog posts — one prompt each, tuned to the topic
BLOG_HEROES = {
    "ipc-to-bns-section-mapping": (
        f"{EDITORIAL_STYLE} Two tall stacks of legal books on either side of the "
        f"frame. Connected by gold geometric arrows or lines suggesting transformation. "
        f"Left stack in darker navy tone, right stack with subtle warm gold accents. "
        f"Wide horizontal 16:9 composition. The metaphor is section-by-section "
        f"conversion between two codes."
    ),
    "crpc-to-bnss-criminal-lawyer-2026": (
        f"{EDITORIAL_STYLE} A long unfolded parchment scroll across the frame at gentle "
        f"perspective, with geometric rectangular blocks representing chapters laid out "
        f"along its length, navy with gold separators between sections. Wide 16:9."
    ),
    "anticipatory-bail-bnss-482": (
        f"{EDITORIAL_STYLE} A symbolic shield rendered as clean geometric navy outlines, "
        f"with a small gold key floating across its center. Minimal flat vector style. "
        f"Concept of legal protection / anticipatory shield. Wide 16:9."
    ),
    "bnss-drafting-mistakes-5-common": (
        f"{EDITORIAL_STYLE} A row of five small geometric document icons in navy, with "
        f"the third one outlined in red as if marked for correction. Subtle gold "
        f"checkmarks on the others. Wide 16:9 with generous space."
    ),
    "naamantaran-cg-lrc-109": (
        f"{EDITORIAL_STYLE} Aerial-view abstraction of agricultural land plots — "
        f"clean rectangles in different sizes representing khasra parcels, with thin "
        f"gold lines connecting two of them suggesting land transfer / mutation. "
        f"Deep navy plots on cream. Wide 16:9 horizontal."
    ),
    "cg-hc-efiling-checklist-2026": (
        f"{EDITORIAL_STYLE} A neat vertical stack of small geometric document icons "
        f"on the left side, each with a small gold checkmark beside it. Lots of "
        f"clean cream space on the right. Wide 16:9."
    ),
    "batwara-cg-lrc-178": (
        f"{EDITORIAL_STYLE} A single large rectangle being divided by clean diagonal "
        f"navy lines into smaller parcels of varying sizes. Thin gold accents at each "
        f"division line. Concept of legal partition / batwara. Wide 16:9."
    ),
    "cg-hc-recent-drafting-relevant-judgments": (
        f"{EDITORIAL_STYLE} Three stacked horizontal navy bars of varying lengths "
        f"representing judgments, the topmost one slightly longer with a small gold "
        f"accent. Like a horizontal bar chart, minimal. Wide 16:9."
    ),
    "quashing-fir-bnss-528": (
        f"{EDITORIAL_STYLE} A formal document outline in navy with a single gold "
        f"diagonal slash across it — clean, refined, not aggressive. The slash "
        f"suggests quashing / striking down. Wide 16:9 horizontal."
    ),
    "ni-act-138-complaint-format": (
        f"{EDITORIAL_STYLE} A stylized cheque rectangle in clean navy line art with a "
        f"small gold X mark in the lower corner suggesting dishonour. Minimalist, "
        f"flat vector style. Wide 16:9."
    ),
}


def get_token() -> str:
    """Fetch ADC access token via gcloud."""
    try:
        result = subprocess.run(
            ["gcloud", "auth", "application-default", "print-access-token"],
            capture_output=True, text=True, check=True, timeout=15,
        )
        return result.stdout.strip().split("\n")[-1]
    except Exception as e:
        sys.exit(f"ERROR: could not fetch ADC token via gcloud: {e}")


def generate_one(token: str, slug: str, prompt: str) -> bool:
    """Call Imagen 4 and save the resulting image."""
    out_path = OUTPUT_DIR / f"{slug}.png"
    if out_path.exists():
        logger.info("[skip] %s already exists", out_path.name)
        return True

    url = (
        f"https://{GCP_LOCATION}-aiplatform.googleapis.com/v1/projects/"
        f"{GCP_PROJECT}/locations/{GCP_LOCATION}/publishers/google/models/"
        f"{IMAGEN_MODEL}:predict"
    )
    payload = {
        "instances": [{"prompt": prompt}],
        "parameters": {
            "sampleCount": 1,
            "aspectRatio": "16:9",
            "personGeneration": "dont_allow",
        },
    }
    try:
        resp = requests.post(
            url,
            headers={
                "Authorization": f"Bearer {token}",
                "Content-Type": "application/json",
            },
            json=payload,
            timeout=120,
        )
    except Exception as e:
        logger.error("[%s] HTTP error: %s", slug, e)
        return False

    if resp.status_code != 200:
        logger.error("[%s] HTTP %s — %s", slug, resp.status_code, resp.text[:300])
        return False

    data = resp.json()
    predictions = data.get("predictions", [])
    if not predictions:
        logger.error("[%s] No predictions in response", slug)
        return False

    b64 = predictions[0].get("bytesBase64Encoded")
    if not b64:
        logger.error("[%s] No bytesBase64Encoded in prediction", slug)
        return False

    out_path.parent.mkdir(parents=True, exist_ok=True)
    out_path.write_bytes(base64.b64decode(b64))
    logger.info("[ok] %s (%.1f KB)", out_path.name, out_path.stat().st_size / 1024)
    return True


def main() -> int:
    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    token = get_token()
    logger.info("Got ADC token: %s...", token[:20])

    success_count = 0
    fail_count = 0
    for i, (slug, prompt) in enumerate(BLOG_HEROES.items(), start=1):
        logger.info("[%d/%d] Generating: %s", i, len(BLOG_HEROES), slug)
        if generate_one(token, slug, prompt):
            success_count += 1
        else:
            fail_count += 1
        time.sleep(2)  # gentle pacing

    logger.info("Done. %d succeeded, %d failed.", success_count, fail_count)
    logger.info("Next: run `php artisan db:seed --class=BlogImageSeeder` to wire URLs into posts.")
    return 0 if fail_count == 0 else 1


if __name__ == "__main__":
    sys.exit(main())
