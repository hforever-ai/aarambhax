# Aarambhax structural refactor — design spec

**Date:** 2026-04-17
**Author:** Claude + ajayagrawal
**Status:** Approved — execution in progress

## Goal

Every page on aarambhax.ai shares one consistent structural system — same breakpoints, type scale, spacing rhythm, container widths, and file boundaries. Internal changes only; color and branding untouched.

## Scope

**In scope**
- Token system: breakpoints, typography, spacing, containers
- CSS file reorg: split `assets/css/site.css` (1,674 LOC) into named files
- Sweep all 14 HTML pages to adopt shared tokens and drop page-specific overrides
- Normalize section wrappers (`.page-hero`, `.product-section`, etc.) so they behave identically everywhere

**Out of scope**
- Color palette (matches logo — stays)
- Font families: Sora, Noto Sans Devanagari, Noto Sans Telugu (stay)
- Nav/footer markup (already consistent)
- Logo redesign or copy changes
- New features, JS behavior, animations

**Success criteria**
- Same `<h1>` renders the same size on every page
- Every page uses the same 4 breakpoints (no stray 600/700/900/1100/1240)
- `site.css` stops being a dumping ground; every rule lives in a named file
- Every page looks intentional at 375 / 768 / 1024 / 1440

## Token system

All live in `assets/css/tokens.css` under `:root`. Values researched against Tailwind, Vercel, Linear, Stripe, Apple, Material 3, Polaris, Atlassian, Primer, and W3C Devanagari guidance.

### Breakpoints — Tailwind-aligned

```css
--bp-sm:  640px;   /* small phone -> tablet */
--bp-md:  768px;
--bp-lg: 1024px;
--bp-xl: 1280px;
```

### Typography — fluid display, stepped body

```css
--fs-h1:    clamp(2.25rem, 1.25rem + 4.5vw, 4.5rem);   /* 36 -> 72 */
--fs-h2:    clamp(1.75rem, 1.25rem + 2.5vw, 3rem);     /* 28 -> 48 */
--fs-h3:    clamp(1.375rem, 1.1rem + 1.2vw, 1.875rem); /* 22 -> 30 */
--fs-h4:    clamp(1.125rem, 1rem + 0.6vw, 1.375rem);   /* 18 -> 22 */

--fs-body-lg: 1.125rem;  /* 18 — long-form, Hindi-friendly */
--fs-body:    1rem;      /* 16 — universal */
--fs-small:   0.875rem;  /* 14 */
--fs-micro:   0.75rem;   /* 12 — labels only */

/* Signature exception — the आरम्भ Devanagari hero */
--fs-devanagari: clamp(3.5rem, 1rem + 7vw, 7.5rem);   /* 56 -> 120 */
```

### Line-heights

```css
--lh-display: 1.05;   /* tight display */
--lh-heading: 1.2;    /* h3/h4 */
--lh-body:    1.55;   /* Latin body */
--lh-body-hi: 1.7;    /* html[data-lang=hi|mr] — Devanagari needs matra room */
```

### Spacing — 4px grid

```css
--space-1: 4px;    --space-2: 8px;    --space-3: 12px;    --space-4: 16px;
--space-6: 24px;   --space-8: 32px;   --space-12: 48px;   --space-16: 64px;
--space-24: 96px;  --space-32: 128px;

--section-y: clamp(4rem, 8vw, 8rem);   /* 64 -> 128 */
--hero-y:    clamp(5rem, 11vw, 9rem);  /* 80 -> 144 */
```

### Containers & gutters

```css
--shell-max:   1440px;   /* nav, home hero */
--content-max: 1200px;   /* text-heavy sections */
--narrow-max:   720px;   /* forms, CTAs */

--gutter-sm: 20px;       /* mobile */
--gutter-md: 24px;       /* tablet */
--gutter-lg: 32px;       /* desktop */
```

### Devanagari overrides

- `html[data-lang="hi"] body, html[data-lang="mr"] body` → `font-size: var(--fs-body-lg); line-height: var(--lh-body-hi);`
- `.hero-devanagari` → `line-height: 1.0; letter-spacing: 0` (never negative — tightening collapses conjuncts)
- Same rule applies to Telugu.

## File architecture

```
assets/css/
  tokens.css       NEW  — :root only. No rules.
  base.css         KEEP — reset, html/body, font-family switching.
  typography.css   NEW  — h1-h6, p, a, lists, .eyebrow, .lead.
  layout.css       KEEP — nav + footer.
  components.css   KEEP — buttons, .cosmos-bg, atoms.
  animations.css   KEEP — keyframes.
  sections.css     NEW  — .page-hero, .product-section, .sec-h2, .cta-section, .faq-section, .policy-section, .testi-section, .stats-section, .how-section.
  home.css         NEW  — .home-*, .hero-devanagari, .hero-en, .jnana-showcase, .brands-grid.
  blog.css         NEW  — blog index + post layouts.
  site.css         SHRINKS to a thin aggregator of @imports, eventually DELETED.
```

**Load strategy:** existing HTML pages link only to `site.css`. Site.css uses `@import` to chain the others — Phase 1 adds new files as `@import` entries here (no HTML edits needed for phases 1–4). Page-scoped loading (home.css only in `/index.html`) is a later optimization.

## Migration phases

| Phase | Files | Visible drift | Rollback |
|---|---|---|---|
| 1 | Add tokens.css + typography.css, @import from site.css | None | Revert PR |
| 2 | Add sections.css, move section rules from site.css, convert to tokens | Every page — h1 and section rhythm shift | Revert PR |
| 3 | Add home.css, move home-only rules | None | Revert PR |
| 4 | Add blog.css, move blog rules, shrink site.css to aggregator or delete | None | Revert PR |
| 5 | Per-page HTML normalization (14 pages, in 3 sub-PRs) | Minor per-page alignment shifts | Revert per-page |
| 6 | Remove legacy aliases from tokens.css (`--cosmos`, `--teal`, `--gold2`, `--white`, etc.) | None | Revert PR |

**Order is strict:** 1 → 2 → 3 → 4 → 5 → 6. Phase 2 must precede Phase 5 so HTML normalization consumes the new section classes. Phase 6 is last so in-flight code can rely on alias names during transit.

### Phase 2 — visible shifts (for review)

- Inner-page `<h1>` grows: 36px → 72px ceiling.
- Home text `<h1>` contracts: 120px → 72px ceiling. **Devanagari signature (`आरम्भ`) keeps 120px ceiling via `--fs-devanagari`.**
- Section padding becomes fluid `clamp(64px, 8vw, 128px)` instead of stepped 80/40.
- Hero padding scales smoothly between 640–1024 (eliminating the current dead zone).
- Body on Hindi pages bumps 15.5px → 18px with 1.7 line-height.

## Testing & verification

No unit tests (CSS-only refactor). Verification is manual at each phase.

**Per-phase checklist:**
1. Serve site locally (`python3 -m http.server` or equivalent) after changes.
2. Open each page in Chrome devtools responsive mode at 4 widths: **375px, 768px, 1024px, 1440px**.
3. Cross-check 4 reference pages: `/`, `/saavi/`, `/about/`, `/blog/`.
4. Language toggle: verify English + Hindi render correctly; Devanagari matras don't collide; `.hero-devanagari` looks intact.
5. Visual diff vs. pre-phase reference screenshots (manual eyeball; no automated regression tooling yet).
6. Browser smoke: Chrome latest + Safari latest.

**Phase 2 extra:** capture before/after screenshots of all 14 pages at 375 + 1440 before merging. This is the only phase with broad visible drift.

## Cache-busting

The existing `scripts/set_css_cache_bust.py` rewrites `site.css?v=...` with a hash of the whole `assets/css/*.css` bundle. No change needed — new files get hashed automatically and rotate the `v=` on every commit.

## Risks

1. **Phase 2 drift surprises** — some page has a CSS rule we didn't audit. **Mitigation:** grep for every class in sections.css before deleting from site.css; review all 14 pages post-sweep.
2. **Devanagari body bump breaks a tight layout** — increasing body size on Hindi pages may overflow a card or nav. **Mitigation:** check nav + hero + product cards specifically at `data-lang="hi"`.
3. **Cache-bust script regression** — if the script only targets `site.css?v=` and we change the `<link>` setup, cache invalidation could fail silently. **Mitigation:** Phase 1 keeps site.css as the only `<link>`; script continues working as-is.
4. **Home hero visual change lands badly** — the 120→72 shrink may feel quieter than desired. **Mitigation:** `--fs-devanagari` preserves the signature presence; if the Latin headline still feels small, we can bump `--fs-h1` ceiling to 80 or add a one-off `.home-hero-latin` class without breaking the system.

## Non-goals (explicit)

- No redesign. No new colors. No new fonts. No new animations.
- No change to site's information architecture or nav structure.
- No CMS, no build step beyond the existing Python cache-bust.
- No component library extraction — this is vanilla CSS on vanilla HTML.
