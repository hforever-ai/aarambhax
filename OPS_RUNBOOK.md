# Aarambhax Legal — Operations Runbook

For deploying, monitoring, and debugging Aarambhax in production.

---

## Deploy to Hostinger (first time)

### 1. Provision

- Hostinger Business / Premium hosting OR KVM VPS
- MySQL 8 database created via Hostinger panel — note the credentials
- SSH access to the box
- DNS for `aarambhax.net` pointed to Hostinger's IP

### 2. Code deploy

```bash
ssh u<your-user>@<hostinger-host>
cd ~/domains/aarambhax.net/public_html
git clone https://github.com/<you>/aarambhax-web.git .
# OR upload code via SFTP / scp

composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

### 3. Configure `.env`

```bash
cp .env.example .env
nano .env
```

Set these values:

```
APP_NAME=Aarambhax Legal
APP_ENV=production
APP_KEY=                  # leave blank for now
APP_DEBUG=false
APP_URL=https://aarambhax.net

LOG_CHANNEL=stack
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=<your-mysql-db>
DB_USERNAME=<your-mysql-user>
DB_PASSWORD=<your-mysql-password>

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

# Optional — enable real Gemini once key is ready
GEMINI_API_KEY=
GEMINI_MODEL_FAST=gemini-2.5-flash
GEMINI_MODEL_PRO=gemini-2.5-pro

# Optional — enable real Telegram once bot is created
TELEGRAM_BOT_TOKEN=
TELEGRAM_BOT_USERNAME=AarambhaxBot

# Optional — Indian Kanoon (Phase 4)
INDIAN_KANOON_API_KEY=

# Mail (for newsletter + transactional)
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=587
MAIL_USERNAME=hello@aarambhax.net
MAIL_PASSWORD=<smtp-password>
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=hello@aarambhax.net
MAIL_FROM_NAME="Aarambhax Legal"
```

### 4. Generate app key + migrate + cache

```bash
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=AarambhaxSeeder --force

# Production caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 5. Set web root

In Hostinger panel:
- Set document root for `aarambhax.net` to `~/domains/aarambhax.net/public_html/public/`
- Enable HTTPS (Let's Encrypt / Hostinger SSL)

### 6. Add system cron

Hostinger panel → Cron Jobs → add:

```
* * * * * cd /home/<user>/domains/aarambhax.net/public_html && php artisan schedule:run >> /dev/null 2>&1
```

This fires the daily 06:00 IST hearing reminders.

### 7. Telegram webhook (one-time, after deploy)

Once `TELEGRAM_BOT_TOKEN` is set in `.env`, run from your laptop:

```bash
curl -F "url=https://aarambhax.net/webhooks/telegram" \
     "https://api.telegram.org/bot<TOKEN>/setWebhook"
```

Verify: `https://api.telegram.org/bot<TOKEN>/getWebhookInfo`

### 8. Verify

Visit:
- `https://aarambhax.net/` — homepage loads
- `https://aarambhax.net/sitemap.xml` — XML sitemap
- `https://aarambhax.net/admin/login` — Filament admin
- `https://aarambhax.net/verifier` — Citation Verifier

---

## Subsequent deploys

```bash
ssh u<user>@<host>
cd ~/domains/aarambhax.net/public_html
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

(GitHub Actions in `.github/workflows/ci.yml` runs tests on every push — never deploy a red branch.)

---

## Generating real content (after key rotation)

```bash
ssh u<user>@<host>
cd ~/domains/aarambhax.net/public_html
export GEMINI_API_KEY="<rotated-key>"
pip install google-genai

# Test
python scripts/generate_content.py ping

# Generate
python scripts/generate_content.py posts        # 10 launch blog posts
python scripts/generate_content.py faqs         # 60 FAQs
python scripts/generate_content.py translate --to hi   # Hindi versions

python scripts/generate_logo.py explore
# Pick winner, vectorize, drop into public/logo-mark.svg

python scripts/generate_images.py all           # hero + blog + pillar images

# Load into DB
php artisan db:seed --class=GeneratedContentSeeder --force

# Re-cache
php artisan view:cache
```

---

## Monitoring

### Logs

```bash
tail -f storage/logs/laravel.log               # Live errors
tail -f /var/log/nginx/error.log               # Web server
```

### Telegram delivery health

```bash
php artisan aarambhax:send-hearing-reminders --dry-run
```

Should list pending reminders without errors. If list is empty but you expected hearings, check:
- User has `telegram_chat_id` set?
- User has `telegram_alerts_enabled = true`?
- Hearing date is within the next 24 hours?
- Hearing's `reminded_at` is null?

### Newsletter health

```bash
php artisan aarambhax:send-broadcast <id> --dry-run
```

### Database health

```bash
php artisan tinker
>>> \DB::select('SHOW TABLE STATUS');
>>> \App\Models\Draft::count();
>>> \App\Models\Hearing::where('reminded_at', null)->where('date', '>=', today())->count();
```

---

## Common issues

### Pages return 500 after deploy

```bash
tail -50 storage/logs/laravel.log
```

Most common causes:
1. `.env` missing values → run `php artisan config:cache` again
2. Wrong file permissions → `chmod -R 775 storage bootstrap/cache`
3. Composer didn't install dev deps but app needs them → `composer install` (without `--no-dev`) just for the missing package, then re-`--no-dev`

### Migrations stuck

```bash
php artisan migrate:status
# If a migration is "Pending" but won't run:
php artisan migrate --force
# If something is corrupted:
php artisan migrate:fresh --force --seed   # ⚠ destroys all data
```

### Tests failing in CI

CI runs SQLite. Most failures are because of:
- Migrations diverged between SQLite and MySQL syntax → ensure migrations use Laravel schema builder, not raw SQL
- Missing `.env.example` value → add it

### Gemini calls failing

Check `.env`:
```bash
php artisan tinker
>>> env('GEMINI_API_KEY');
```

If null, the app falls back to stub mode automatically. Stub responses are clearly labelled "STUBBED".

If set but failing, check Gemini quota at https://aistudio.google.com/apikey.

### Telegram webhook not firing

```bash
curl https://api.telegram.org/bot<TOKEN>/getWebhookInfo
```

If `last_error_message` is set, fix the URL. If `pending_update_count > 0`, the webhook is queued but not delivering — check your firewall/SSL.

---

## Backup strategy

### Database

```bash
mysqldump -u <user> -p <db> > /tmp/aarambhax-$(date +%F).sql
gzip /tmp/aarambhax-*.sql
# Upload to Cloudflare R2 or S3 nightly via cron
```

Recommended: a daily cron at 03:00 IST that dumps + uploads.

### Files

User-uploaded files (Phase 3b) should go to `storage/app/private/u_<user_id>/` — back up the entire `storage/app/` nightly.

---

## Security checklist (before public launch)

- [ ] `APP_DEBUG=false` in production `.env`
- [ ] `APP_ENV=production`
- [ ] HTTPS enforced, HSTS header set
- [ ] All secrets in `.env`, never committed
- [ ] Database user has minimum needed privileges (no `DROP`, `GRANT`)
- [ ] `storage/` and `bootstrap/cache/` writable by web user only
- [ ] Cron uses non-interactive shell, no secret-leak via `>> /var/log`
- [ ] CSRF protection enabled (Laravel default — already on)
- [ ] Rate-limit on `/login`, `/register`, `/contact`, `/newsletter/subscribe`
- [ ] Rotate `APP_KEY` if ever leaked
- [ ] Rotate Gemini key after every staff change
- [ ] Rotate Telegram bot token if ever leaked

---

## Phase 4+ deployment notes

When Phase 4 (real Indian Kanoon API) ships:

1. Add `INDIAN_KANOON_API_KEY` to `.env`
2. `php artisan db:seed --class=BareActsSeeder --force` (seeds India Code data)
3. `CitationVerifier` automatically switches from stub to real verification

When Phase 3b (multimodal upload) ships:

1. Ensure PHP `fileinfo`, `pdo_mysql`, `gd` or `imagick` extensions installed
2. Configure `FILESYSTEM_DISK=local` for uploads
3. Add ClamAV via Hostinger or self-host: `clamscan` in upload pipeline
4. Set per-user storage quota (default 5 GB) in `config/aarambhax.php`
