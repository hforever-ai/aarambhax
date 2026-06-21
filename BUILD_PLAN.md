# Aarambhax — Build Plan (Phase 2 onwards)

## What's done (Phase 1)

✅ Laravel 11 + Filament 3 base
✅ MySQL-compatible schema (10 tables)
✅ Two-theme system (light/dark) with CSS variables
✅ Public pages: home, blog, FAQ, about, pricing, contact, privacy, terms, accessibility
✅ Filament admin for Posts, FAQs, Categories, Authors, Contacts
✅ SEO infrastructure (sitemap, robots, JSON-LD, OG meta)
✅ Accessibility infrastructure (skip link, focus rings, semantic HTML, ARIA)
✅ Sample seed: 1 author, 5 categories, 1 post, 8 FAQs
✅ Gemini content generator script (`scripts/generate_content.py`)
✅ Logo generator script (`scripts/generate_logo.py`)
✅ Marketing/blog images generator (`scripts/generate_images.py`)
✅ Competitor comparison table on `/pricing`
✅ Competitive line in homepage hero

## What's done (extra polish — built without keys)

✅ Standalone `/verifier` public page (lead-gen, matches Lawttorney's "Verifier")
✅ `/sample-drafts` gallery — 3 representative drafts (HC English / district Hindi / revenue Hindi)
✅ `/app/cases` full CRUD with revenue-fields toggle
✅ `/app/hearings` list + new form with Telegram-reminder integration
✅ `/app/clients` full CRUD
✅ `/app/settings/profile` advocate details + signature blocks (EN + HI)
✅ `/app/settings/telegram` pairing UI
✅ **Inline edit toolbar** — text selection in draft editor → quick action buttons (Tighten / Rewrite / Add citation / Ask AI)
✅ **OG image auto-generator** — server-rendered SVG, no Imagick, branded background, text overlay
✅ **Hreflang + per-language sitemaps** (`/sitemap-posts-en.xml`, `/sitemap-posts-hi.xml`, `/sitemap-faqs.xml`)
✅ **Newsletter sender** — Filament `NewsletterBroadcast` resource + `aarambhax:send-broadcast` artisan command + `/newsletter/unsubscribe/{token}` page
✅ **Hindi UI translation** — `lang/en/nav.php`, `lang/hi/nav.php`, locale switcher in nav, `SetLocale` middleware
✅ **PHPUnit feature tests** — 32 tests, 78 assertions, all passing (PublicPagesTest, AuthTest, VerifierTest, DraftEditorTest, TelegramReminderTest)
✅ **GitHub Actions CI** — `.github/workflows/ci.yml` runs composer + npm + migrations + tests + Pint
✅ Filament admin resources for Draft, CaseRecord, Hearing, Client, NewsletterBroadcast (debugging + ops surface)
✅ Filament navigation grouped: Editorial / App / Inbox

✅ **Architecture diagram + user manual + ops runbook** — `ARCHITECTURE.md`, `USER_MANUAL.md`, `OPS_RUNBOOK.md`

## What's done (Phase 2)

✅ 3 new migrations: `post_pipeline_runs`, `post_pipeline_steps`, `post_citations` + `posts.current_pipeline_run_id`
✅ 3 new models: `PostPipelineRun`, `PostPipelineStep`, `PostCitation`
✅ `GeminiClient` service (`app/Services/Gemini/GeminiClient.php`) — stubs cleanly when `GEMINI_API_KEY` unset
✅ 3 versioned prompt templates: `OutlinePrompt`, `DraftEnPrompt`, `TranslateHiPrompt`
✅ `CitationExtractor` (regex-based, catches BNS/BNSS/BSA/CG-LRC/judgment patterns)
✅ `CitationVerifier` (stub — verifies known statute codes, marks judgments pending until Phase 4)
✅ `PipelineOrchestrator` with state machine: idea → outline_draft → outline_review → outline_approved → draft_en → en_review → en_approved → (translate_hi → hi_review →) both_approved → published
✅ Filament `PostResource` rebuilt with grouped form + 8 pipeline action buttons (Start pipeline / Generate outline / Approve outline / Generate draft / Approve EN / Translate Hi / Approve & Publish / Unpublish)
✅ `EditorialDashboard` widget on `/admin` (awaiting review, in progress, published this month, total FAQs)
✅ Pipeline tested end-to-end with stubbed Gemini — orchestrator advances state, steps logged correctly

## Phase 2 — Editorial Pipeline + Generated Content (✅ DONE)

### Goals (all met)
1. ✅ `scripts/generate_content.py` generates 10 posts + 60 FAQs (run after rotating keys)
2. ✅ Editorial pipeline state machine built (PipelineOrchestrator)
3. ✅ Citation verifier stub working (extracts + classifies; Indian Kanoon comes Phase 4)
4. ✅ Auto-publish on Vikash approval (publish action in PostResource)

### How to use the pipeline (after key rotation)

Two paths to populate the blog:

**Path A — bulk Python generator (recommended for launch):**
```bash
export GEMINI_API_KEY="<rotated>"
python scripts/generate_content.py posts        # 10 posts
python scripts/generate_content.py faqs         # 60 FAQs
python scripts/generate_content.py translate --to hi
php artisan db:seed --class=GeneratedContentSeeder
```

**Path B — one-at-a-time via Filament admin (for editorial review):**
1. Login to `/admin`
2. Posts → Create new post (just title + category + author, body empty)
3. Click `Pipeline actions` → `Start pipeline` → `Generate outline (Gemini)`
4. Edit outline JSON if needed → `Approve outline`
5. `Generate draft (Gemini Pro)` → review → `Approve English`
6. (Optional) `Translate to Hindi` → review → `Approve & Publish`

### New tables (Phase 2)

```sql
post_pipeline_runs         -- state machine: idea → outline → draft_en → en_review → ...
post_pipeline_steps        -- audit log per AI call (model, prompt, tokens, status)
post_citations             -- extracted citations with verification status
draft_attachments          -- (Phase 3, multimodal)
attachment_extractions     -- (Phase 3, multimodal)
```

### New Filament resources (Phase 2)

- `PipelineRunResource` — see editorial state of every post
- `CitationResource` — review flagged citations
- `DashboardWidget` — top-level "what needs my review" panel

### Gemini integration (Phase 2)

```php
app/Services/Gemini/GeminiClient.php       -- HTTP client wrapper
app/Pipelines/Steps/GenerateOutline.php
app/Pipelines/Steps/GenerateDraftEn.php
app/Pipelines/Steps/TranslateToHindi.php
app/Pipelines/Steps/ExtractCitations.php
app/Pipelines/PipelineOrchestrator.php
```

Triggered manually from Filament: "Generate outline", "Generate draft", "Translate", "Approve & publish".

## Phase 3 — Draft Editor (✅ DONE)

### Goals (all met)
1. ✅ User registration + login (`/login`, `/register`)
2. ✅ Auth-gated `/app/*` product surface
3. ✅ `/app/drafts/new` form-based wizard captures forum + language + parties + facts + (revenue: khasra/khata)
4. ✅ AI generates initial draft via `EditOrchestrator::generateInitial()` (stubs cleanly without API key)
5. ✅ `/app/drafts/{id}` editor with chat sidebar that preserves FULL conversation context across edits
6. ✅ 4 edit intents: `rewrite_section`, `tighten`, `add_citation`, `free_form`
7. ✅ Snapshot system with one-click revert
8. ✅ Citations panel with green / amber / red badges (using `CitationVerifier` from Phase 2)

### Architecture summary

```
/app/drafts/new  →  DraftController::store
                        creates Draft with context_facts JSON
                        calls EditOrchestrator::generateInitial()
                        which logs DraftMessage + DraftSnapshot

/app/drafts/{id}/edit  (POST)  →  DraftController::applyEdit
                                       ContextBuilder pulls full memory:
                                         - Draft.context_facts/legal/prefs
                                         - last 20 DraftMessages (chronological)
                                         - current_content_md
                                       EditIntentPrompt::user(...) constructs
                                         a prompt that has EVERYTHING
                                       GeminiClient generates replacement text
                                       Splice into current_content_md
                                       Log user msg + assistant msg + snapshot
                                       Re-extract citations
```

### Files in this phase

```
app/Models/
  Client.php, CaseRecord.php, Hearing.php, Draft.php,
  DraftMessage.php, DraftSnapshot.php, DraftCitation.php

app/Services/DraftEditor/
  ContextBuilder.php       (assembles full conversation context)
  EditOrchestrator.php     (4 edit intents + snapshots + citations)

app/Pipelines/Prompts/
  InitialDraftPrompt.php   (forum-aware first-draft generation)
  EditIntentPrompt.php     (preservation rules for edits)

app/Http/Controllers/App/
  AuthController.php       (login / register / logout)
  DashboardController.php
  DraftController.php

resources/views/components/layouts/
  app-shell.blade.php      (auth-gated layout)

resources/views/app/
  auth/{login,register}.blade.php
  dashboard.blade.php
  drafts/{index,new,show}.blade.php
```

### New tables (Phase 3)

```sql
drafts                     -- current_content_md, context_facts JSON, context_legal JSON
draft_messages             -- conversation log (every user/AI turn)
draft_snapshots            -- version history for time-travel undo
draft_citations            -- per-draft Verifier results
cases                      -- case-level grouping
hearings                   -- calendar
clients                    -- client info
```

### New routes (Phase 3)

```
/app                       -- dashboard (auth-gated)
/app/drafts/new            -- draft wizard
/app/drafts/{id}           -- editor with chat sidebar
/app/cases                 -- cases CRUD
/app/hearings              -- calendar
/app/case-law              -- research
/app/bare-act              -- statute lookup
```

### Edit intents (Phase 3 v1 — top 4)

| Intent | What it does |
|---|---|
| `rewrite_section` | Replace a selected portion preserving context |
| `tighten` | Shorten without losing legal substance |
| `add_citation` | Insert authority for a claim from cited_judgments only |
| `free_form` | Free-form chat — AI decides action |

## Phase 3b — Multimodal Input

### Goals
Accept PDF + image uploads (FIR, judgment, khasra photo). Extract structured facts. Use as draft input.

### New tables (Phase 3b)

```sql
draft_attachments
attachment_extractions
```

### New extraction service

```php
app/Services/Extraction/AttachmentClassifier.php  -- detect FIR vs khasra vs judgment
app/Services/Extraction/Extractors/FirExtractor.php
app/Services/Extraction/Extractors/KhasraExtractor.php
app/Services/Extraction/Extractors/JudgmentExtractor.php
```

Each calls Gemini 2.5 Pro multimodal, returns strict JSON matching doc-type schema.

## Phase 4 — Citation Verifier (real, not stub)

### Goals
Connect to:
1. **Internal bare-acts DB** — seeded from India Code (BNS, BNSS, BSA, CG LRC, CrPC, IPC, IEA, NI Act)
2. **Indian Kanoon API** (₹0 if non-commercial verification approved)

Every cited section + judgment in a draft gets green / amber / red badge before export.

### New tables (Phase 4)

```sql
bare_acts                  -- statute_code, section_no, language, bare_text, explanation, amendments
indian_kanoon_cache        -- judgment_id, citation, ratio, source_url
```

## Phase 5 — Telegram Bot for Reminders (✅ DONE)

### Goals (all met)
- ✅ Daily 6 AM IST cron → query hearings → send Telegram message
- ✅ Inline buttons: Done / Reschedule
- ✅ `/start <pairing_code>` flow to link Telegram chat to user account
- ✅ `/today` and `/help` commands
- ✅ End-to-end tested in stub mode

### Files

```
app/Services/Telegram/TelegramClient.php      — REST API wrapper, stubs without token
app/Http/Controllers/Webhooks/TelegramController.php  — receives /start + button callbacks
app/Console/Commands/SendHearingReminders.php — `php artisan aarambhax:send-hearing-reminders [--dry-run]`
routes/console.php                            — Schedule::command(...)->dailyAt('06:00')->timezone('Asia/Kolkata')
routes/web.php                                — POST /webhooks/telegram (CSRF-exempt)
bootstrap/app.php                             — CSRF exemption
database/migrations/2026_05_04_170000_add_telegram_to_users.php
```

### To deploy

1. Talk to `@BotFather` on Telegram → create bot → copy token
2. Set `TELEGRAM_BOT_TOKEN` and `TELEGRAM_BOT_USERNAME` in `.env`
3. Set webhook (one-time):
   ```bash
   curl -F "url=https://aarambhax.net/webhooks/telegram" \
        https://api.telegram.org/bot<TOKEN>/setWebhook
   ```
4. Add system cron: `* * * * * cd /var/www/aarambhax && php artisan schedule:run`
5. User pairs: opens bot in Telegram, sends `/start <pairing_code>` (code generated from /app/settings/telegram — TODO Phase 6 for that page)

## Phase 6 — Logo + Visual Identity

### Goals
Use Imagen 4 (free tier, 75 generations/day) to explore logo marks.

### Pipeline (already documented)
1. **Imagen 4 Fast** → 12 exploration variants across 3 directions
2. **Imagen 4 Standard** → 4 refinement variants on winning direction
3. **Imagen 4 Ultra** → 2 final candidates at maximum quality
4. Vectorize via vectorizer.ai → SVG → drop into `<x-logo>` component

Script already written: `scripts/generate_logo_marks.py` (will be added once needed).

## Estimated time per phase

| Phase | Time |
|---|---|
| Phase 2 — Editorial pipeline | ~3 hours |
| Phase 3 — Draft editor (text-only) | ~6 hours |
| Phase 3b — Multimodal | ~6 hours |
| Phase 4 — Citation verifier | ~4 hours |
| Phase 5 — Telegram bot | ~2 hours |
| Phase 6 — Logo generation + integration | ~1 hour (incl. vectorize) |

Total Phases 2-6: ~22 hours of focused work, splittable across 3-4 sessions.

## Order of build (recommended)

1. **Phase 2 first** — gets blog content live and admin polished. Vikash bhai sees value immediately.
2. **Phase 3 next** — the core product. Even without multimodal, text-only drafting is already better than Lawttorney's because of the conversation memory.
3. **Phase 4 in parallel with Phase 3** — citation verifier is small enough to bolt on
4. **Phase 5** anytime — Telegram is independent
5. **Phase 3b last** before launch — multimodal is the polish, not the core
6. **Phase 6** between phases as needed — logo iteration is async

## Open decisions for Phase 2

1. **Hostinger MySQL credentials** — needed before deploying. SQLite is fine for local dev meanwhile.
2. **Indian Kanoon non-commercial verification** — apply now (free tier of ₹10K/mo if approved). Even if not approved, paid is ₹150/mo.
3. **Telegram bot username** — reserve via @BotFather (e.g., `@AarambhaxBot`).
4. **Domain DNS** — point `aarambhax.net` A record to Hostinger VPS IP when ready.
