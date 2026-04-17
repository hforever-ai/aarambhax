#!/usr/bin/env python3
"""One-off: apply AARAMBHAX_MASTER_DESIGN nav + cosmos + fonts to all HTML."""
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]

OLD_FONT = re.compile(
    r'<link href="https://fonts\.googleapis\.com/css2\?family=Space\+Grotesk[^"]+" rel="stylesheet">'
)
NEW_FONT = (
    '<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800'
    "&family=Noto+Sans+Devanagari:wght@400;500;600;700;800"
    "&family=Noto+Sans+Telugu:wght@400;500;700&display=swap\" rel=\"stylesheet\">"
)

COSMOS = """<div class="cosmos-bg" aria-hidden="true">
  <div class="ring ring--a"></div>
  <div class="ring ring--b"></div>
  <div class="ring ring--c"></div>
</div>
"""

OLD_NAV_RE = re.compile(
    r'<nav role="navigation" aria-label="Main navigation">.*?</nav>\s*',
    re.DOTALL,
)


def active_href(path: Path):
    rel = path.relative_to(ROOT)
    if rel.name != "index.html":
        return None
    parts = rel.parts[:-1]
    if not parts:
        return None
    return "/" + "/".join(parts) + "/"


def nav_link(href, i18n, current):
    cur = current and href.split("#")[0].rstrip("/") == current.rstrip("/")
    if href.startswith("/#"):
        ac = ""
    else:
        ac = ' aria-current="page"' if cur else ""
    return f'      <a href="{href}" class="nav-link"{ac} data-i18n="{i18n}"></a>'


def mobile_link(href, i18n, current):
    cur = current and href.split("#")[0].rstrip("/") == current.rstrip("/")
    if href.startswith("/#"):
        ac = ""
    else:
        ac = ' aria-current="page"' if cur else ""
    return f'  <a href="{href}" class="nav-link"{ac} data-i18n="{i18n}"></a>'


def build_nav(current):
    desktop = []
    for href, key in [
        ("/shrutam/", "nav.product"),
        ("/about/", "nav.about"),
        ("/saavi/", "nav.saavi"),
        ("/blog/", "nav.blog"),
        ("/faq/", "nav.faq"),
        ("/contact/", "nav.contact_page"),
        ("/schools/", "nav.schools"),
        ("/#jnana", "nav.jnana"),
    ]:
        desktop.append(nav_link(href, key, current))
    desktop.append(
        '      <a href="https://shrutam.ai" class="nav-link" rel="noopener noreferrer" target="_blank" '
        'data-i18n="nav.launch_app" data-i18n-aria-label="nav.launch_aria"></a>'
    )

    mobile = []
    for href, key in [
        ("/shrutam/", "nav.product"),
        ("/about/", "nav.about"),
        ("/saavi/", "nav.saavi"),
        ("/blog/", "nav.blog"),
        ("/faq/", "nav.faq"),
        ("/contact/", "nav.contact_page"),
        ("/schools/", "nav.schools"),
        ("/#jnana", "nav.jnana"),
        ("https://shrutam.ai", "nav.launch_app"),
    ]:
        if href == "https://shrutam.ai":
            mobile.append(
                '  <a href="https://shrutam.ai" class="nav-link" rel="noopener noreferrer" target="_blank" '
                'data-i18n="nav.launch_app"></a>'
            )
        else:
            mobile.append(mobile_link(href, key, current))

    return f"""<nav id="main-nav" role="navigation" aria-label="Main navigation">
  <div class="nav-inner">
    <a href="/" class="logo-wrap">
      <img src="/assets/images/aarambha-logo-nav.png" alt="Aarambha" class="nav-logo-img" width="160" height="44">
    </a>
    <div class="nav-desktop-only" role="menubar">
{chr(10).join(desktop)}
    </div>
    <div class="nav-actions">
      <div class="lang-switcher nav-lang-desktop" role="group" aria-label="Language">
        <button type="button" class="lang-btn" data-lang="hi" aria-pressed="false">हि</button>
        <button type="button" class="lang-btn" data-lang="en" aria-pressed="false">En</button>
        <button type="button" class="lang-btn" data-lang="mr" aria-pressed="false">म</button>
        <button type="button" class="lang-btn" data-lang="te" aria-pressed="false">తె</button>
      </div>
      <a href="/waitlist/" class="nav-btn nav-waitlist-cta" data-i18n="nav.cta"></a>
      <button type="button" class="hamburger" id="nav-hamburger" aria-label="Open menu" aria-expanded="false" aria-controls="mobile-menu">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>
</nav>
<div id="menu-overlay" class="menu-overlay" aria-hidden="true"></div>
<div id="mobile-menu" class="mobile-menu" role="dialog" aria-modal="true" aria-label="Navigation menu">
{chr(10).join(mobile)}
  <div class="lang-switcher mobile-lang" role="group" aria-label="Language">
    <button type="button" class="lang-btn" data-lang="hi" aria-pressed="false">हिंदी</button>
    <button type="button" class="lang-btn" data-lang="en" aria-pressed="false">EN</button>
    <button type="button" class="lang-btn" data-lang="mr" aria-pressed="false">मराठी</button>
    <button type="button" class="lang-btn" data-lang="te" aria-pressed="false">&#x0C24;&#x0C47;&#x0C32;&#x0C41;&#x0C17;&#x0C41;</button>
  </div>
  <a href="/waitlist/" class="nav-btn mobile-menu-cta btn-primary" data-i18n="nav.cta"></a>
</div>
"""


def patch_file(path: Path):
    text = path.read_text(encoding="utf-8")
    orig = text

    text = OLD_FONT.sub(NEW_FONT, text, count=1)

    if '<div class="cosmos-bg"' not in text:
        text = text.replace(
            '<div class="stars" aria-hidden="true"></div>',
            COSMOS + '<div class="stars" aria-hidden="true"></div>',
            1,
        )

    cur = active_href(path)
    m = OLD_NAV_RE.search(text)
    if m:
        text = OLD_NAV_RE.sub(build_nav(cur) + "\n", text, count=1)

    if "/assets/js/nav.js" not in text and "site.js" in text:
        text = text.replace(
            '<script defer src="/assets/js/site.js"></script>',
            '<script defer src="/assets/js/site.js"></script>\n<script defer src="/assets/js/nav.js"></script>',
            1,
        )

    if text != orig:
        path.write_text(text, encoding="utf-8")
        return True
    return False


def main():
    changed = 0
    for p in sorted(ROOT.rglob("*.html")):
        if patch_file(p):
            print("patched", p.relative_to(ROOT))
            changed += 1
    print("done,", changed, "files")


if __name__ == "__main__":
    main()
