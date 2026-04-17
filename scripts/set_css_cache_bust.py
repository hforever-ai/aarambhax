#!/usr/bin/env python3
"""
Rewrite /assets/css/site.css links in HTML to include ?v=<cache-bust>.

?v= is the first 10 hex chars of sha256 over assets/css/*.css (sorted by path).
That tracks real stylesheet changes, avoids git-amend / HEAD mismatch, and
still busts CDN/browser caches whenever any bundled CSS file changes.

If the css directory is missing, falls back to secrets.token_hex(5).

Run after CSS changes (and commit the updated HTML with this script):
  python3 _external/aarambhax-website/scripts/set_css_cache_bust.py

Optional: install pre-commit (see scripts/pre-commit-aarambhax.sh).
"""
from __future__ import annotations

import hashlib
import re
import secrets
import sys
from pathlib import Path

SITE_CSS_HREF = re.compile(r'href="/assets/css/site\.css(?:\?v=[^"#]*)?"')


def _webroot() -> Path:
    return Path(__file__).resolve().parent.parent


def _css_bundle_version(webroot: Path) -> str:
    css_dir = webroot / "assets" / "css"
    if not css_dir.is_dir():
        return secrets.token_hex(5)
    h = hashlib.sha256()
    for path in sorted(css_dir.glob("*.css")):
        h.update(path.name.encode("utf-8"))
        h.update(b"\0")
        h.update(path.read_bytes())
    return h.hexdigest()[:10]


def main() -> int:
    webroot = _webroot()
    v = _css_bundle_version(webroot)
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
