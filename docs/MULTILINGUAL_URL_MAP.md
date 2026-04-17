# Multilingual URLs + `.com`-only (SEO playbook)

## Decisions (locked for this doc)

| Topic | Choice | Rationale |
|--------|--------|-----------|
| **Primary marketing host** | `https://www.aarambhax.com` | Matches current canonicals and `sitemap.xml`. |
| **`x-default` (hreflang)** | `https://www.aarambhax.com/` | India-first product; current homepage is Hindi-primary (`lang="hi-IN"`). Use as fallback when Google has no better locale match. |
| **Locale in URL** | **Path prefix** `/hi/`, `/en/`, `/mr/`, `/te/` | Best balance of clarity, one property in GSC, and indexable per-language URLs. |
| **`.ai` domain** | **301 → `www.aarambhax.com`** (same path) | Avoid duplicate indexing; keep `.ai` for email/brand only if you want. |

---

## 1. Domain and host redirects (DNS / CDN — not in this repo)

Implement **301** at Cloudflare (or your DNS proxy) / host:

| From | To |
|------|-----|
| `http://www.aarambhax.com/*` | `https://www.aarambhax.com/$1` |
| `http://aarambhax.com/*` | `https://www.aarambhax.com/$1` |
| `https://aarambhax.com/*` | `https://www.aarambhax.com/$1` |
| `https://aarambhax.ai/*` | `https://www.aarambhax.com/$1` |
| `https://www.aarambhax.ai/*` | `https://www.aarambhax.com/$1` |

Pick **one** of `www` vs apex as canonical; the table assumes **`www`** (aligned with HTML today).

---

## 2. Target URL map (phased)

### Phase 0 — Today (no URL change)

- All locales share one URL; `hreflang` alternates point to the same URL → OK for UX, **weak for per-language ranking**.
- Keep until Phase 1 ships.

### Phase 1 — High-traffic surfaces (recommended first)

Duplicate **static HTML** per locale **or** add a small **build step** that emits four trees from one template + `translations.js` / JSON.

| Logical page | Hindi (default path) | English | Marathi | Telugu |
|--------------|------------------------|---------|---------|--------|
| Home | `/` **or** `/hi/` | `/en/` | `/mr/` | `/te/` |
| Waitlist | `/waitlist/` **or** `/hi/waitlist/` | `/en/waitlist/` | `/mr/waitlist/` | `/te/waitlist/` |
| Blog hub | `/blog/` **or** `/hi/blog/` | `/en/blog/` | `/mr/blog/` | `/te/blog/` |
| Post: CG Board 10 Science | `/blog/cg-board-class-10-science-2026-guide/` | `/en/blog/cg-board-class-10-science-2026-guide/` | … | … |
| Post: SAAVI vs tuition | `/blog/saavi-vs-tuition-hindi-medium/` | `/en/blog/...` | … | … |
| Post: Photosynthesis | `/blog/photosynthesis-class-6-hindi/` | `/en/blog/...` | … | … |

**Pick one structure and stick to it:**

- **A — Minimal churn:** Keep existing URLs as **Hindi** (`/`, `/blog/...`). Add **`/en/...` only** first; add `mr`/`te` when you have real copy.
- **B — Fully symmetric:** Move Hindi under **`/hi/`** and **301** old paths → `/hi/...` (cleanest long-term, more redirects once).

Recommendation: start with **A** (add **`/en/`** mirror for Phase 1 pages only), then optionally migrate Hindi to `/hi/` in a later cut.

### Phase 2 — Rest of marketing site

Mirror under each locale: `/shrutam/`, `/saavi/`, `/about/`, `/faq/`, `/contact/`, `/schools/`, `/privacy/`, `/terms/`.

### Phase 3 — App / product

`shrutam.ai` stays separate product domain; marketing canonicals remain `aarambhax.com`.

---

## 3. `hreflang` on each indexed page

For every indexable URL, `<head>` should include **self + all other locale URLs + x-default**:

Example when English post exists at `/en/blog/photosynthesis-class-6-hindi/`:

```html
<link rel="alternate" hreflang="hi-IN" href="https://www.aarambhax.com/blog/photosynthesis-class-6-hindi/" />
<link rel="alternate" hreflang="en-IN" href="https://www.aarambhax.com/en/blog/photosynthesis-class-6-hindi/" />
<link rel="alternate" hreflang="mr-IN" href="https://www.aarambhax.com/mr/blog/photosynthesis-class-6-hindi/" />
<link rel="alternate" hreflang="te-IN" href="https://www.aarambhax.com/te/blog/photosynthesis-class-6-hindi/" />
<link rel="alternate" hreflang="x-default" href="https://www.aarambhax.com/blog/photosynthesis-class-6-hindi/" />
```

Rules:

- **Omit** a line if that locale **does not exist yet** (don’t point hreflang to 404).
- **`link rel="canonical"`** on each page = **that page’s own** absolute URL.

---

## 4. HTML `lang` attribute

Each file: `<html lang="hi">` / `lang="en">` / `lang="mr">` / `lang="te">` matching the **visible** primary language of that document.

---

## 5. Language switcher (replace JS-only toggle for SEO locales)

For pages that exist in multiple locales:

- Each button becomes a **link** to the same path under another prefix, e.g.  
  `pathname.replace(/^\/en(\/|$)/, '/hi$1')` or a small map `{ hi: '/hi/...', en: '/en/...' }`.
- Keep **client-side i18n** only for **rare** microcopy if needed; **main content** should be in static HTML for the crawler.

---

## 6. `sitemap.xml`

- Either **one sitemap** listing all locale URLs, or **sitemap index** + per-locale sitemaps.
- When Phase 1 goes live, add every new `/en/...` URL and update **`lastmod`** when content changes.

---

## 7. Build / repo integration (when you implement)

1. **Source of truth:** keep strings in [`assets/js/translations.js`](../assets/js/translations.js) or split JSON by locale.
2. **Generator:** Node script (or Eleventy / similar) reads template + locale → writes `en/blog/foo/index.html`, etc.
3. **CI:** run generator before deploy; fail if a key page is missing in a locale you claim in hreflang.

---

## 8. GSC / GA4

- **GSC:** one **Domain** property on `aarambhax.com` covers all paths; monitor **International targeting** and **hreflang** errors after launch.
- **GA4:** optional `language` / `locale` custom dimension from URL path prefix for reporting.

---

## Summary

- **`.com` only:** enforce with **301**s; don’t serve a parallel indexed site on **`.ai`**.
- **Language for SEO:** **separate URLs per language** (path prefix); **`x-default`** = Hindi homepage `https://www.aarambhax.com/`; add **`/en/`** first, then `mr`/`te` when copy exists.
- **Phase 1 URL set:** home, waitlist, blog hub, three posts — then expand.

When you’re ready to implement Phase 1 in the repo, say whether you prefer **A** (keep `/` as Hindi, add `/en/...`) or **B** (move Hindi to `/hi/`, 301 old URLs).
