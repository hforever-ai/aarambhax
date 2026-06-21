# Aarambhax Legal — Functionality Overview

What Aarambhax Legal does, organized by audience: marketing-site visitor, beta user, admin (Vikash bhai).

## For visitors (marketing site, public)

### Discover
- **Homepage** — hero, 6 features, sample drafts, FAQ, waitlist
- **Blog** — practical drafting guides, BNS/BNSS section mappings, Revenue Court walk-throughs
- **FAQ page** — common questions across 5 topics (BNSS, drafting, revenue, court tech, product)
- **About / Pricing / Contact** — standard marketing pages
- **Accessibility statement** — WCAG 2.2 AA compliance commitment
- **Privacy / Terms** — clear legal coverage
- **Theme toggle** — light + dark, persists across visits
- **Newsletter signup** — early-access waitlist

### What's polished about it
- Lighthouse-friendly: minimal JS, semantic HTML, responsive, fast
- SEO-grade: per-page meta, JSON-LD structured data, sitemap, hreflang-ready
- Accessibility-grade: skip link, focus rings, ARIA, reduced-motion respected, keyboard navigable

## For beta users (Phase 3, not yet built)

### Account
- Phone OTP login (MSG91)
- Profile with Bar Council number, signature blocks (EN + HI), chamber address

### Drafting wizard (text-only v1)
1. Pick forum (CG HC / District / Revenue / Tribunal)
2. Pick category (Civil / Criminal / Family / Revenue / Special Act)
3. Pick draft type (filtered by forum + category)
4. Fill form — parties, facts, court, citations
5. Generate — Gemini produces draft with verified citations

### Draft editor (the differentiator)
- Open any draft → markdown editor with Verifier badges on citations
- **Chat sidebar** — type instructions in plain language:
  - "tighten paragraph 3"
  - "add a ground about delay in filing"
  - "rewrite this in more formal Hindi"
  - "add a citation supporting this paragraph"
- AI keeps full memory: facts, parties, forum, language, signature, all previous edits
- **Inline edit toolbar** — select text → button to rewrite/tighten/add citation
- **Snapshots** — every save is a versioned snapshot, time-travel undo
- **Source-linked footnotes** — every fact in draft links to its source (uploaded PDF page, fact you typed)

### Multimodal input (Phase 3b)
- Upload FIR PDF, charge-sheet, judgment, khasra photo, sale deed
- Auto-classification (FIR / khasra / judgment / etc.)
- Structured fact extraction (parties, sections, dates, khasra numbers)
- Review extracted facts → edit → use as draft input
- Each draft cites which file gave it which fact

### Citation Verifier (Phase 4)
- Every cited statute checked against internal bare-acts DB
- Every judgment checked against Indian Kanoon
- Three-tier badges: 🟢 verified · 🟡 caution · 🔴 not found
- One-click delete or replace flagged citations
- Daily cron re-checks published drafts; alerts if anything moved to suspect

### Bare Act Engine
- Search any section: "BNSS 482" or "CG LRC 109" or "धारा 138"
- Returns: bare text (EN + HI), plain-language explanation, historical amendments, top 5 judgments citing it
- Sourced from India Code + Indian Kanoon, cached

### Case management (light, Phase 3)
- Cases list (status, parties, court, case no.)
- Hearing calendar (month/day view)
- 2-tap quick add: case + date + purpose
- Telegram reminder daily 6 AM

### Export
- Word (.docx) — preserves Devanagari + cause-title formatting
- PDF — court-ready format, watermark while in draft, removed on "finalize"
- Mandatory checkbox: "I have reviewed and verified" before export

### Languages
- English (HC, English-default forums)
- Hindi (Devanagari) for district + revenue
- Bilingual mode (Hindi body + English citation block)

## For admin (Vikash bhai, Filament admin)

### Editorial pipeline (Phase 2)
- **Editorial dashboard** — top: "what needs your review"
- **Per-post pipeline view** — outline → EN draft → Hindi translate → assets → publish
- **3-pane review screen** — outline (read-only) + draft (editable markdown) + citations (badges)
- **Inline regenerate** — select any paragraph, regenerate just that paragraph
- **Approve & publish** — one click, post goes live, sitemap regenerates
- **Audit log** — every AI call (model, prompt, tokens, status) for debugging

### Content management
- Posts CRUD with markdown editor
- FAQs CRUD
- Categories, Tags, Authors CRUD
- Newsletter subscribers list (export CSV)
- Contact form submissions inbox

### Pipeline triggers
- Manual: "Generate outline" / "Generate draft" / "Translate" buttons in Filament
- Scheduled (optional): nightly batch outline generation for backlog

### Operational
- Audit log readonly view
- Token usage / cost tracking (per post + monthly)
- Citation re-verify cron output

## What we deliberately don't build

- ❌ Multi-user roles (single-user product for v1)
- ❌ Client portal (Indian advocates communicate via WhatsApp)
- ❌ Billing / invoicing (Tally/Zoho already exist)
- ❌ Time tracking (not the pain point)
- ❌ Customer support chat widget (email + Telegram suffice)
- ❌ Document version control (drafts library covers this)
- ❌ Comments on blog posts (legal site, comment spam risk)

## Roadmap

| Phase | Feature | Status |
|---|---|---|
| 1 | Marketing site + blog + admin | ✅ Done |
| 2 | Editorial pipeline + 10 generated posts + 60 FAQs | ⏳ Next session |
| 3 | Draft editor with conversation memory | ⏳ |
| 3b | Multimodal upload (PDF + image) | ⏳ |
| 4 | Citation verifier (Indian Kanoon + bare-acts) | ⏳ |
| 5 | Telegram bot for hearing reminders | ⏳ |
| 6 | Logo + visual identity (Imagen 4 generation) | ⏳ |

See `BUILD_PLAN.md` for technical details.
