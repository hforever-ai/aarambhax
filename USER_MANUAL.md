# Aarambhax Legal — User Manual

For Vikash bhai and beta advocates.

## What Aarambhax does

- **Drafts** legal documents in Hindi or English for CG High Court, district / sessions courts, and revenue courts
- **Verifies** every cited section and judgment against known statutes
- **Edits by chat** — tell the AI what to change, it remembers everything
- **Tracks cases, hearings, and clients** with Telegram reminders before each listing
- **Free Verifier tool** at `/verifier` — paste any draft, get instant feedback

---

## First-time setup

### 1. Create your account

1. Visit `https://aarambhax.net/register`
2. Enter name, email, password
3. Click "Create account" → you're in

### 2. Fill your profile

1. Click **Settings** in the top nav
2. Add your Bar Council enrolment number
3. Add your chamber address
4. Paste your usual signature blocks (English and Hindi)
5. Click "Save"

These auto-fill into drafts wherever applicable.

### 3. Pair Telegram for hearing reminders (optional but recommended)

1. Open Telegram, search **@AarambhaxBot**
2. In Aarambhax, go to **Settings → Telegram → Generate code**
3. In Telegram, send: `/start <your-code>`
4. You'll get a confirmation. From now on, every hearing 24 hours away gets a reminder with Done / Reschedule buttons.

---

## Daily use

### Generate a new draft

1. Click **+ New Draft** (top-right)
2. Pick:
   - **Forum** — CG HC / District / Revenue / Tribunal
   - **Language** — Hindi / English / Bilingual
   - **Category** — civil / criminal / family / revenue
   - **Draft type** — anticipatory_bail / vakalatnama / naamantaran / etc.
3. Fill case basics: title, parties, court name, case number
4. Type the case in plain words in the **Key facts** box
5. For revenue matters, fill khasra / khata details
6. Click **Generate Draft**

The AI produces a court-ready first draft in 30 seconds.

### Edit a draft (the part Lawttorney gets wrong)

You have two ways to edit:

#### A. Highlight + button

1. **Select** any text in the draft body
2. A small toolbar appears with: **Tighten / Rewrite / Add citation / Ask AI…**
3. Click any button → enter your instruction → AI rewrites just that section
4. The AI **remembers** the case facts, parties, language, and every previous edit you've made

#### B. Chat sidebar (right side)

Type natural-language instructions in the chat box on the right, e.g.:
- *"add a ground about cooperation with investigation"*
- *"make paragraph 3 more formal"*
- *"add a citation supporting the bail being granted"*
- *"tighten the prayer clause"*

Press Enter / click Send. AI responds with a diff preview. Apply or discard.

### Citations panel

Below the draft body, every cited section gets a colored badge:

- 🟢 **Verified** — statute and section format are valid
- 🟡 **Suspect** — unknown statute or malformed section number
- ⏳ **Pending** — judgment, needs Indian Kanoon (Phase 4)

Click any flagged citation to replace or remove it.

### Snapshots (time-travel undo)

Every save creates a snapshot. The right sidebar lists the last 8 with a **Revert** button. Use this if AI took the draft in a wrong direction.

### Export

(Coming Phase 3b) — Word and PDF export with your signature block, court-ready format, and verifier disclaimer footer.

---

## Cases & hearings

### Create a case

1. Click **Cases → + New Case**
2. Fill title, forum, court name, case number, opposing party
3. For revenue: add khasra / khata / khatauni / gram / tehsil
4. Optionally link to a client
5. Save

### Add a hearing

1. From a case page, click **+ Add hearing** (or **Hearings → + Add hearing**)
2. Pick the case, date, time, purpose
3. Save → Telegram reminder is queued

### View today's hearings

- In the app: **Hearings** tab shows upcoming + recent
- In Telegram: send `/today` to the bot

---

## Free public Verifier (`/verifier`)

If you don't want to log in, you can verify any draft from `/verifier`:

1. Paste your draft (Hindi or English, markdown or plain text)
2. Click **Verify Citations**
3. See badges for every section / judgment cited

No signup, nothing stored. Useful for quickly sanity-checking a draft before filing.

---

## Tips for best results

1. **Use the new criminal codes (BNS / BNSS / BSA) for matters arising after 1 July 2024.** The AI knows the date split and uses correct codes if you mention the FIR date in key facts.
2. **Cite real judgments — don't ask the AI to invent them.** If you say "find a case supporting X," the AI will use `[VERIFY]` markers; replace those with real Indian Kanoon citations before filing.
3. **For revenue matters, fill khasra/khata fields exactly as on the record.** The AI uses these verbatim in the cause-title.
4. **Use the chat sidebar for refinements, not the form.** Once a draft is generated, never re-fill the form — that resets your edits. Use chat to iterate.
5. **Save snapshots before big rewrites.** If you ask the AI to "completely rewrite," save a snapshot first so you can revert if you don't like the result.

---

## Important disclaimers

- **AI-generated drafts require advocate review before filing.** Aarambhax is a drafting accelerator, not legal advice.
- **Citations may need verification.** Especially judgments — Phase 4 will add Indian Kanoon API integration; until then, manually verify any non-green-badge citation.
- **Confidentiality.** During beta, drafts are processed via Google Gemini's free tier. Free tier inputs may be used by Google to improve their models. We move to paid tier (private) before public launch. **Do not upload sensitive client identifiers (Aadhaar, etc.) without redaction during beta.**

---

## Getting help

- **Email:** `support@aarambhax.net`
- **Telegram:** `/help` to the bot
- **Bug or feature request:** Email with screenshot — we read every one.
