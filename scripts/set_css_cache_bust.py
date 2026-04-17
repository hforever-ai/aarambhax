#!/usr/bin/env python3
"""
Rewrite /assets/css/site.css and /assets/js/*.js references in HTML to
include ?v=<cache-bust>.

Rationale: the CDN in front of aarambhax.ai serves assets with
cache-control: max-age=604800 (1 week). Without a query string, browsers
and edge caches keep stale JS for up to a week — that was the exact
cause of the Company + language dropdown outage we hit.

Development (default, until you ship):
  v = "<time_ns_last10>-<asset-sha256[:8]>"
  - time_ns changes on every script run (e.g. each pre-commit), so each
    commit gets a new query string even when only copy/HTML changes.
  - asset segment rotates when any assets/css/*.css or assets/js/*.js file changes.

Production / stable URLs:
  AARAMBHAX_CSS_BUST_MODE=content  ->  v = first 12 hex chars of the bundle sha256 only.

Run manually:
  python3 scripts/set_css_cache_bust.py

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

CSS_HREF = re.compile(r'href="/assets/css/site\.css(?:\?v=[^"#]*)?"')
# Match any script tag that references /assets/js/<name>.js with optional ?v=
JS_SRC = re.compile(r'src="/assets/js/([a-zA-Z0-9_\-]+)\.js(?:\?v=[^"#]*)?"')
# Match @import url("name.css") inside site.css — needs busting too because
# the CDN caches these downstream files for a week and the HTML ?v= doesn't
# cascade into CSS-level imports.
IMPORT_URL = re.compile(r'@import\s+url\(\s*"([a-zA-Z0-9_\-]+)\.css(?:\?v=[^"#]*)?"\s*\)\s*;')


def _webroot() -> Path:
    return Path(__file__).resolve().parent.parent


def _asset_bundle_hex(webroot: Path) -> str:
    """Hash over every CSS + JS asset so any one change rotates the bust."""
    h = hashlib.sha256()
    for sub in ("css", "js"):
        d = webroot / "assets" / sub
        if not d.is_dir():
            continue
        for path in sorted(d.glob("*.*")):
            if path.suffix.lower() not in (".css", ".js"):
                continue
            # Skip underscore-prefixed tooling files (_i18n_patch.json etc.)
            if path.name.startswith("_"):
                continue
            h.update(path.name.encode("utf-8"))
            h.update(b"\0")
            h.update(path.read_bytes())
    digest = h.hexdigest()
    return digest if digest else secrets.token_hex(16)


def _cache_bust_token(webroot: Path) -> str:
    full = _asset_bundle_hex(webroot)
    mode = os.environ.get("AARAMBHAX_CSS_BUST_MODE", "dev").lower().strip()
    if mode in ("content", "release", "prod"):
        return full[:12]
    ns = str(time.time_ns())[-10:]
    return f"{ns}-{full[:8]}"


def _bust_site_css_imports(webroot: Path, v: str) -> int:
    """Rewrite @import url("x.css") in site.css to @import url("x.css?v=<v>").
    Returns the number of imports updated. The site.css entry-point carries
    the ?v= on its HTML <link>, but its CSS-level @imports must carry their
    own ?v= to defeat CDN cache on downstream files (tokens.css, home.css...).
    """
    site_css = webroot / "assets" / "css" / "site.css"
    if not site_css.exists():
        return 0

    def sub(m: re.Match) -> str:
        return f'@import url("{m.group(1)}.css?v={v}");'

    text = site_css.read_text(encoding="utf-8")
    new, n = IMPORT_URL.subn(sub, text)
    if n and new != text:
        site_css.write_text(new, encoding="utf-8")
        print(f"updated assets/css/site.css (@import {n}x) -> v={v}")
    return n


def main() -> int:
    webroot = _webroot()
    v = _cache_bust_token(webroot)

    # First, rewrite site.css @import URLs. This must happen BEFORE the
    # bundle hash influences the token (but since we already computed v
    # above, just note: the hash will shift next run to reflect this edit,
    # which is fine — CDN still sees a unique URL per change).
    _bust_site_css_imports(webroot, v)

    css_replacement = f'href="/assets/css/site.css?v={v}"'

    def js_sub(m: re.Match) -> str:
        return f'src="/assets/js/{m.group(1)}.js?v={v}"'

    html_paths = list(webroot.rglob("*.html"))
    changed = 0
    for path in sorted(html_paths):
        text = path.read_text(encoding="utf-8")
        new_css, n_css = CSS_HREF.subn(css_replacement, text)
        new_both, n_js = JS_SRC.subn(js_sub, new_css)
        if (n_css or n_js) and new_both != text:
            path.write_text(new_both, encoding="utf-8")
            changed += 1
            print(
                f"updated {path.relative_to(webroot)} "
                f"(css {n_css}x, js {n_js}x) -> v={v}"
            )
    if not changed:
        print(f"no changes (already v={v} or no matches)")
    return 0


if __name__ == "__main__":
    sys.exit(main())
