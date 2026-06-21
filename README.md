# Aarambhax Legal — Marketing Site + Blog + Admin

Laravel 11 + Filament 3 + MySQL/SQLite. Light + dark theme. Built for `aarambhax.net`.

## Stack

| Layer | Tech |
|---|---|
| Backend | Laravel 11 (PHP 8.2+) |
| Admin | Filament 3 |
| Database | SQLite (dev), MySQL 8 (Hostinger) |
| Frontend | Blade + Tailwind v3 + Vite |
| Fonts | Fraunces (serif), Inter (sans), JetBrains Mono |
| Themes | Light (cream + navy + gold) and Dark (near-black + cream + brightened gold) |
| Content gen | Python script → Gemini 2.5 Flash (free tier) |

## Quick start

```bash
cd aarambhax-web
composer install
npm install
php artisan migrate --seed
npm run build
php artisan serve   # http://127.0.0.1:8000
```

Admin: `http://127.0.0.1:8000/admin/login` — `admin@aarambhax.local` / `aarambhax2026`

## Routes shipped (47 total)

### Public marketing
| Path | Purpose |
|---|---|
| `/` | Homepage (hero + 6 features + sample drafts + FAQs + waitlist) |
| `/verifier` | **Free Citation Verifier** — paste draft, get badges, no signup |
| `/blog` `/blog/{slug}` | Blog index + post with JSON-LD |
| `/faq` | FAQ page with FAQPage schema |
| `/pricing` | With competitor comparison table vs Lawttorney |
| `/about` `/contact` `/privacy` `/terms` `/accessibility` | Static pages |
| `/sitemap.xml` `/robots.txt` | SEO |

### Auth
| Path | Purpose |
|---|---|
| `/login` `/register` | Web auth (separate from Filament admin login) |
| `/logout` | POST |

### Product (`/app/*`, auth-gated)
| Path | Purpose |
|---|---|
| `/app` | Dashboard with metrics + recent drafts |
| `/app/drafts` | All drafts (paginated table) |
| `/app/drafts/new` | 5-step draft wizard |
| `/app/drafts/{id}` | Editor with chat sidebar (conversation memory) |
| `/app/cases` `/app/cases/new` `/app/cases/{id}` | Case management |
| `/app/hearings` `/app/hearings/new` | Hearing list + form (Telegram-reminder enabled) |
| `/app/settings/telegram` | Pair Telegram, toggle alerts, generate code |

### Admin (`/admin/*`)
| Resource | Group |
|---|---|
| Posts, FAQs, PostCategories, Authors | Editorial |
| Drafts, CaseRecords, Hearings, Clients | App |
| Contacts | Inbox |

### Webhooks
| Path | Purpose |
|---|---|
| `POST /webhooks/telegram` | Telegram bot callbacks (CSRF-exempt) |

## Theme system

Two themes via CSS variables in `resources/css/app.css`. Toggle in nav (top-right). Persisted in `localStorage`, respects OS `prefers-color-scheme`.

Color tokens:
- `--bg`, `--surface`, `--surface-2` — backgrounds
- `--fg`, `--fg-muted` — text
- `--primary`, `--accent`, `--link` — brand + interactivity
- `--success`, `--warning`, `--danger` — Verifier badges

## SEO + accessibility built in

- Per-page `<title>`, meta description, canonical, OG meta
- JSON-LD: `Article` + `BreadcrumbList` on posts; `FAQPage` on FAQ pages
- Sitemap with priority + lastmod
- robots.txt
- Skip link as first focusable element
- WCAG 2.2 AA focus rings (gold, 2px)
- Semantic HTML5
- `prefers-reduced-motion` honoured
- Form fields with `aria-invalid` and `aria-describedby`

## Generating real content with Gemini 2.5 Flash

After rotating your API key:

```bash
export GEMINI_API_KEY="<rotated-key>"

# Test
python scripts/generate_content.py ping

# Generate 10 launch posts (~25 free-tier requests)
python scripts/generate_content.py posts

# Generate 60 FAQs (5 topics × 12)
python scripts/generate_content.py faqs

# Hindi translations
python scripts/generate_content.py translate --to hi

# Load into MySQL/SQLite
php artisan db:seed --class=GeneratedContentSeeder
```

Generated content saves to `database/content/` (gitignored). Seeder is idempotent.

## Deploying to Hostinger

1. Create MySQL database in Hostinger panel
2. SSH to box, clone repo to `~/domains/aarambhax.net/public_html/`
3. Set web root to `public/` directory
4. `composer install --no-dev --optimize-autoloader`
5. `npm install && npm run build`
6. `cp .env.example .env`, edit MySQL credentials, `APP_ENV=production`, `APP_URL=https://aarambhax.net`
7. `php artisan key:generate`
8. `php artisan migrate --force --seed`
9. `php artisan config:cache && php artisan route:cache && php artisan view:cache`

## What's done in Phase 2 (editorial pipeline)

- ✅ State machine: idea → outline → draft → review → publish
- ✅ Filament UI with 8 pipeline action buttons per post
- ✅ Editorial dashboard widget (awaiting review, in progress, published this month)
- ✅ Citation extractor (regex-based, extracts BNS/BNSS/BSA/CG-LRC/judgments)
- ✅ Citation verifier stub (real Indian Kanoon API comes Phase 4)
- ✅ Gemini client wrapper that stubs cleanly when API key is missing

## What's done in Phase 3 (draft editor — the core product)

- ✅ Web-side auth: register / login / logout (`/login`, `/register`)
- ✅ Auth-gated `/app/*` product surface
- ✅ Dashboard at `/app` with metrics + recent drafts
- ✅ Draft creation wizard at `/app/drafts/new` — forum + language + parties + facts
- ✅ Conversation-memory edit system that fixes Lawttorney's context-loss bug
- ✅ 4 edit intents: rewrite_section, tighten, add_citation, free_form
- ✅ Chat sidebar with quick-action buttons
- ✅ Snapshot system with one-click revert
- ✅ Citation panel with verifier badges
- ✅ End-to-end tested: register → create draft → edit → snapshot

### To use the draft editor

```bash
php artisan serve
# Visit http://127.0.0.1:8000/register → create account
# Then http://127.0.0.1:8000/app/drafts/new
# Fill the form, click "Generate Draft"
# Use the chat sidebar to refine
```

Without GEMINI_API_KEY set, the editor uses STUBBED responses — UI works, but you'll see placeholder text. Set the key and everything generates real content.

## What's done in Phase 5 (Telegram bot)

- ✅ `TelegramClient` service (sends Markdown messages with inline buttons; stubs without token)
- ✅ Webhook controller handles `/start <code>`, `/today`, `/help`, button callbacks
- ✅ `php artisan aarambhax:send-hearing-reminders` command
- ✅ Daily 06:00 IST cron schedule registered
- ✅ User table: `telegram_chat_id`, `telegram_pairing_code`, `telegram_alerts_enabled`, plus advocate fields (Bar number, signature blocks, chamber)
- ✅ End-to-end verified in stub mode

### To enable real Telegram delivery

```bash
# 1. Create bot via @BotFather on Telegram, copy token
# 2. Add to .env:
TELEGRAM_BOT_TOKEN=<token>
TELEGRAM_BOT_USERNAME=AarambhaxBot

# 3. Set webhook (after deploy to public URL):
curl -F "url=https://aarambhax.net/webhooks/telegram" \
     https://api.telegram.org/bot<TOKEN>/setWebhook

# 4. Add system cron:
echo "* * * * * cd /var/www/aarambhax && php artisan schedule:run >/dev/null 2>&1" | crontab -

# 5. Test:
php artisan aarambhax:send-hearing-reminders --dry-run
```

## What's NOT yet built

Only items that genuinely need external accounts/services:

- **Multimodal upload** (PDF + image fact extraction) — Phase 3b (needs Gemini key for real extraction)
- **Real citation verifier** with Indian Kanoon API — Phase 4 (needs Indian Kanoon API key)
- **Auto-generated logo mark + hero images** — Phase 6 (Python scripts ready in `scripts/`, run after Gemini key rotation)
- **Word/PDF export** with watermark — Phase 7

Everything else is shipped, tested, and verified. See `ARCHITECTURE.md`, `USER_MANUAL.md`, `OPS_RUNBOOK.md` for the full picture.

## Disclaimer

AI-generated drafts require advocate review before filing. Aarambhax is a drafting tool, not legal advice.
