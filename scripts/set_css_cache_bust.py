#!/usr/bin/env python3
"""
Rewrite /assets/css/site.css links in HTML to include ?v=<cache-bust>.

Development (default, until you ship):
  v = "<time_ns_last10>-<css-sha256[:8]>"
  - time_ns changes on every script run (e.g. each pre-commit), so each commit
    gets a new query string even when only copy/HTML changes.
  - css segment still rotates when any assets/css/*.css file changes.

Production / stable URLs:
  AARAMBHAX_CSS_BUST_MODE=content  ->  v = first 12 hex chars of the css bundle sha256 only.

Run manually:
  python3 _external/aarambhax-website/scripts/set_css_cache_bust.py

Hook: scripts/pre-commit-aarambhax.sh
"""
from __future__ import annotations

import hashlib
import os
import re
import secrets
import sys
import time
from pathlib import Path

SITE_CSS_HREF = re.compile(r'href="/assets/css/site\.css(?:\?v=[^"#]*)?"')


def _webroot() -> Path:
    return Path(__file__).resolve().parent.parent


def _css_bundle_hex(webroot: Path) -> str:
    css_dir = webroot / "assets" / "css"
    if not css_dir.is_dir():
        return secrets.token_hex(16)
    h = hashlib.sha256()
    for path in sorted(css_dir.glob("*.css")):
        h.update(path.name.encode("utf-8"))
        h.update(b"\0")
        h.update(path.read_bytes())
    return h.hexdigest()


def _cache_bust_token(webroot: Path) -> str:
    full_css = _css_bundle_hex(webroot)
    mode = os.environ.get("AARAMBHAX_CSS_BUST_MODE", "dev").lower().strip()
    if mode in ("content", "release", "prod"):
        return full_css[:12]

    ns = str(time.time_ns())[-10:]
    return f"{ns}-{full_css[:8]}"


def main() -> int:
    webroot = _webroot()
    v = _cache_bust_token(webroot)
    replacement = f'href="/assets/css/site.css?v={v}"'

    html_paths = list(webroot.rglob("*.html"))
    changed = 0
    for path in sorted(html_paths):
        text = path.read_text(encoding="utf-8")
        new, n = SITE_CSS_HREF.subn(replacement, text)
        if n and new != text:
            path.write_text(new, encoding="utf-8")
            changed += 1
            print(f"updated {path.relative_to(webroot)} ({n}x) -> v={v}")
    if not changed:
        print(f"no changes (already v={v} or no matches)")
    return 0


if __name__ == "__main__":
    sys.exit(main())
