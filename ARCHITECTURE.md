# Aarambhax Legal — Architecture

## High-level data flow

```
                ┌───────────────────────────────────────────────────────┐
                │                   Public Internet                     │
                └────────────┬──────────────────────────┬───────────────┘
                             │                          │
                  HTTPS GET/POST                  Telegram webhook POST
                             │                          │
                ┌────────────▼──────────┐    ┌─────────▼──────────┐
                │   aarambhax.net       │    │ /webhooks/telegram │
                │   (Hostinger)         │    └─────────┬──────────┘
                │                       │              │
                │  ┌──────────────────┐ │              │
                │  │ Marketing site   │ │              │
                │  │  / /blog /faq    │ │              │
                │  │  /verifier /etc  │ │              │
                │  └────────┬─────────┘ │              │
                │           │           │              │
                │  ┌────────▼─────────┐ │              │
                │  │ Auth (web)       │ │              │
                │  │  /login /register│ │              │
                │  └────────┬─────────┘ │              │
                │           │           │              │
                │  ┌────────▼─────────┐ │   ┌──────────▼──────────┐
                │  │ Product /app/*   │◄┼───┤ TelegramController  │
                │  │  drafts cases    │ │   │  /start <code>      │
                │  │  hearings clients│ │   │  /today /help       │
                │  │  settings        │ │   │  inline btn callback│
                │  └────────┬─────────┘ │   └──────────┬──────────┘
                │           │           │              │
                │  ┌────────▼─────────┐ │              │
                │  │ EditOrchestrator │ │              │
                │  │  ContextBuilder  │ │              │
                │  │  CitationXtract  │ │              │
                │  │  CitationVerify  │ │              │
                │  └────────┬─────────┘ │              │
                │           │           │              │
                │  ┌────────▼─────────┐ │              │
                │  │  GeminiClient    │ │              │
                │  │  (stub if no key)│ │              │
                │  └────────┬─────────┘ │              │
                │           │           │              │
                │           │           │   ┌──────────▼──────────┐
                │  ┌────────▼─────────┐ │   │ MySQL (Hostinger)   │
                │  │  TelegramClient  │◄├───┤   28 tables         │
                │  │  (stub if no tkn)│ │   │   28 db indexes     │
                │  └────────┬─────────┘ │   └─────────────────────┘
                │           │           │
                │  ┌────────▼─────────┐ │
                │  │ Admin /admin/*   │ │
                │  │  Filament 3      │ │
                │  │  9 resources     │ │
                │  │  Editorial dash  │ │
                │  └──────────────────┘ │
                └────────────┬──────────┘
                             │
                       External APIs
                             │
        ┌────────────────────┼────────────────────┐
        │                    │                    │
   ┌────▼────┐      ┌────────▼────────┐    ┌──────▼──────┐
   │ Gemini  │      │  Telegram Bot   │    │ Indian      │
   │ 2.5 Pro │      │     API         │    │ Kanoon API  │
   │ + Flash │      │                 │    │ (Phase 4)   │
   │ + Imagen│      │                 │    │             │
   └─────────┘      └─────────────────┘    └─────────────┘
```

## Request lifecycle (draft generation)

```
User opens /app/drafts/new
            │
            ▼
DraftController::create() → renders form
            │
User fills facts, clicks "Generate Draft"
            │
            ▼
POST /app/drafts (DraftController::store)
            │
            ├─ Validate input
            ├─ Create Draft row with context_facts JSON
            └─ Call EditOrchestrator::generateInitial(draft)
                       │
                       ├─ Build InitialDraftPrompt::system(forum, type, lang)
                       ├─ Call GeminiClient->generate() (Pro tier)
                       ├─ Save DraftMessage(role=assistant, intent=initial_draft)
                       ├─ Save DraftSnapshot for time-travel undo
                       └─ Run CitationExtractor on output
                              ├─ Extract statute sections + judgments via regex
                              ├─ For each: create DraftCitation(status=pending)
                              └─ CitationVerifier::verifyAll() classifies each
                                     └─ verified / suspect / pending
            │
Redirect to /app/drafts/{id}
            │
            ▼
DraftController::show() → renders editor with chat sidebar + citation panel
```

## Edit lifecycle (the conversation memory)

```
User selects text in editor → toolbar appears
            │
User clicks "Tighten" / "Rewrite" / "Add citation" / types free-form
            │
            ▼
POST /app/drafts/{id}/edit
            │
            ▼
DraftController::applyEdit() → EditOrchestrator::applyEdit()
            │
            ├─ Verify intent in [rewrite_section, tighten, add_citation, free_form]
            ├─ ContextBuilder::build(draft, selection):
            │     ┌─ context_facts (parties, dates, sections from form)
            │     ├─ context_legal (cited sections, judgments)
            │     ├─ context_user_prefs (signature, banned phrases)
            │     ├─ full_draft (current_content_md)
            │     └─ summarised last 20 DraftMessages (the conversation)
            ├─ EditIntentPrompt::user(intent, context) builds full prompt
            │     containing EVERYTHING above
            ├─ Log DraftMessage(role=user, intent=...)
            ├─ Call Gemini Pro
            ├─ Splice response into current_content_md at selection_start..end
            ├─ Log DraftMessage(role=assistant)
            ├─ Save DraftSnapshot
            └─ Re-run citation extraction:
                   ├─ Mark removed citations as removed_in_message_id
                   └─ Add new citations with added_in_message_id
```

This is what fixes Lawttorney's bug: every edit gets the full history. The AI literally cannot forget.

## Editorial pipeline (blog posts)

```
[idea]
   ↓ Vikash bhai clicks "Generate outline"
[outline_review]   ← human checks outline JSON
   ↓ approve
[outline_approved]
   ↓ click "Generate draft (EN)"
[en_review]        ← human checks markdown body
   ↓ approve
[en_approved]
   ↓ optional translate
[hi_review]        ← human checks Hindi
   ↓ approve
[both_approved]
   ↓ click "Approve & Publish"
[published]        ← post.status='published', appears in /blog and sitemap
```

Implemented in:
- `App\Pipelines\PipelineOrchestrator` (state machine)
- `App\Models\PostPipelineRun` + `PostPipelineStep`
- Filament `PostResource` with 8 contextual action buttons
- `EditorialDashboard` widget on `/admin`

## Telegram reminder lifecycle

```
1. User pairs (one-time):
     User opens bot → /start <pairing_code> → webhook saves chat_id

2. Daily cron (06:00 IST):
     SendHearingReminders::handle()
       └─ Hearing::whereBetween(date, today, tomorrow)
                   ->whereNull(reminded_at)
                   ->whereHas(user.telegram_chat_id)
       └─ For each: TelegramClient::sendMessage(chat_id, msg, [Done, Reschedule])
       └─ Mark reminded_at = now()

3. User clicks button in Telegram:
     POST /webhooks/telegram (callback_query)
       └─ TelegramController::handleCallback()
       └─ Hearing::find($id)->update(['outcome' => 'completed'])
```

## Database (28 tables)

```
Users + Auth                 Editorial                       Product
─────────────────            ─────────────────               ─────────────────
users                        post_categories                 drafts
sessions                     posts                           draft_messages
password_reset_tokens        post_tag                        draft_snapshots
                             tags                            draft_citations
                             authors                         case_records
                             post_pipeline_runs              hearings
                             post_pipeline_steps             clients
                             post_citations
                             faqs                            Misc
                             post_faq                        ─────────────────
                                                             contacts
Communications               Cache & Jobs                    sample_drafts (none — hardcoded)
─────────────────            ─────────────────
newsletter_subscribers       cache, cache_locks
newsletter_broadcasts        jobs, job_batches, failed_jobs
                             migrations
```

All 28 tables migrate on SQLite (dev) or MySQL 8 (production / Hostinger).

## Static dependencies

```
PHP 8.2+ → Laravel 11.31 → Filament 3 + Vite + Tailwind 3
Node 20 → Vite assets only
Python 3.10+ → scripts/generate_*.py (optional, Gemini calls)
```

## External services (configurable, all optional in dev)

```
GEMINI_API_KEY        → real AI generation, else stub mode
TELEGRAM_BOT_TOKEN    → real reminder delivery, else log-only
INDIAN_KANOON_API_KEY → real judgment verification (Phase 4)
```

Without these, the app **still runs end-to-end** — every service has a stub fallback that logs intent and returns a sentinel response.
