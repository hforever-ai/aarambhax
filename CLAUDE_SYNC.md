# Claude Coordination File
> Two Claude instances working on same repo — read this before touching any file.

## How to use
1. **Before starting:** Check "Currently locked" below
2. **While working:** Add your files to "Currently locked"
3. **When done:** Move to "Recently changed" + unlock

---

## Currently locked
_None_

---

## Recently changed
| File | What changed | Tab |
|------|-------------|-----|
| `app/Services/StudentNoteAiService.php` | Added `fetchYoutubeTranscript()` + `organiseTranscript()` | Tab A |
| `app/Http/Controllers/App/StudentNoteController.php` | Added `storeFromYoutube()` | Tab A |
| `routes/web.php` | Added `/student-notes/from-youtube` route | Tab A |
| `resources/views/app/dashboard_student.blade.php` | Added YouTube tab UI + JS | Tab A |
| `database/migrations/2026_05_09_000000_add_youtube_url_to_student_notes.php` | New migration | Tab A |

---

## Tab assignments (to avoid conflicts)
| Area | Tab |
|------|-----|
| AI Services (`app/Services/`) | — |
| Controllers (`app/Http/Controllers/`) | — |
| Views / Blade (`resources/views/`) | — |
| Routes | — |
| Migrations | — |
| Models | — |

> Update the tab column above when you start working in an area.
