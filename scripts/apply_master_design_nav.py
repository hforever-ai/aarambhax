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


def flyout_link(href, i18n, current):
    cur = current and href.split("#")[0].rstrip("/") == current.rstrip("/")
    ac = ' aria-current="page"' if cur else ""
    return (
        f'          <a href="{href}" class="nav-flyout__link" role="menuitem"{ac} data-i18n="{i18n}"></a>'
    )


def build_nav(current):
    desktop = [
        nav_link("/shrutam/", "nav.product", current),
        nav_link("/saavi/", "nav.saavi", current),
    ]
    company_links = []
    for href, key in [
        ("/about/", "nav.about"),
        ("/blog/", "nav.blog"),
        ("/faq/", "nav.faq"),
        ("/contact/", "nav.contact_page"),
        ("/schools/", "nav.schools"),
    ]:
        company_links.append(flyout_link(href, key, current))
    desktop.append(
        "      <div class=\"nav-flyout\" data-nav-flyout>\n"
        "        <button type=\"button\" class=\"nav-flyout__trigger\" id=\"nav-company-trigger\" "
        'aria-expanded="false" aria-haspopup="true" aria-controls="nav-company-panel" '
        'data-i18n="nav.company_menu">Company</button>\n'
        '        <div class="nav-flyout__panel" id="nav-company-panel" role="menu" '
        'aria-labelledby="nav-company-trigger" hidden>\n'
        + "\n".join(company_links)
        + "\n        </div>\n      </div>"
    )
    desktop.append(nav_link("/#jnana", "nav.jnana", current))
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
      <div class="lang-dropdown nav-lang-desktop" data-lang-dropdown>
        <button type="button" class="lang-dropdown__btn" id="lang-dropdown-desktop" aria-haspopup="listbox" aria-expanded="false" aria-controls="lang-dropdown-menu-desktop">
          <span class="lang-dropdown__current" data-lang-current>हिंदी</span>
          <span class="lang-dropdown__chev" aria-hidden="true">▾</span>
        </button>
        <ul class="lang-dropdown__menu" id="lang-dropdown-menu-desktop" role="listbox" aria-labelledby="lang-dropdown-desktop" hidden>
          <li role="presentation"><button type="button" class="lang-btn lang-dropdown__opt" role="option" data-lang="hi" data-lang-label="हिंदी" aria-pressed="false">हिंदी</button></li>
          <li role="presentation"><button type="button" class="lang-btn lang-dropdown__opt" role="option" data-lang="en" data-lang-label="English" aria-pressed="false">English</button></li>
          <li role="presentation"><button type="button" class="lang-btn lang-dropdown__opt" role="option" data-lang="mr" data-lang-label="मराठी" aria-pressed="false">मराठी</button></li>
          <li role="presentation"><button type="button" class="lang-btn lang-dropdown__opt" role="option" data-lang="te" data-lang-label="తెలుగు" aria-pressed="false">తెలుగు</button></li>
        </ul>
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
  <div class="lang-dropdown mobile-lang" data-lang-dropdown>
    <button type="button" class="lang-dropdown__btn" id="lang-dropdown-mobile" aria-haspopup="listbox" aria-expanded="false" aria-controls="lang-dropdown-menu-mobile">
      <span class="lang-dropdown__current" data-lang-current>हिंदी</span>
      <span class="lang-dropdown__chev" aria-hidden="true">▾</span>
    </button>
    <ul class="lang-dropdown__menu" id="lang-dropdown-menu-mobile" role="listbox" aria-labelledby="lang-dropdown-mobile" hidden>
      <li role="presentation"><button type="button" class="lang-btn lang-dropdown__opt" role="option" data-lang="hi" data-lang-label="हिंदी" aria-pressed="false">हिंदी</button></li>
      <li role="presentation"><button type="button" class="lang-btn lang-dropdown__opt" role="option" data-lang="en" data-lang-label="English" aria-pressed="false">English</button></li>
      <li role="presentation"><button type="button" class="lang-btn lang-dropdown__opt" role="option" data-lang="mr" data-lang-label="मराठी" aria-pressed="false">मराठी</button></li>
      <li role="presentation"><button type="button" class="lang-btn lang-dropdown__opt" role="option" data-lang="te" data-lang-label="తెలుగు" aria-pressed="false">తెలుగు</button></li>
    </ul>
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
