<?php

use App\Http\Controllers\App\AuthController;
use App\Http\Controllers\App\CaseAnalysisController;
use App\Http\Controllers\App\CaseController;
use App\Http\Controllers\App\CatalogueController;
use App\Http\Controllers\App\ClientController;
use App\Http\Controllers\App\ConversationController;
use App\Http\Controllers\App\DashboardController;
use App\Http\Controllers\App\DraftController;
use App\Http\Controllers\App\DraftFromFilesController;
use App\Http\Controllers\App\CaseDocumentController;
use App\Http\Controllers\App\HearingController;
use App\Http\Controllers\App\KaryaController;
use App\Http\Controllers\App\RouterController;
use App\Http\Controllers\App\SettingsController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\SampleDraftsController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\VerifierController;
use Illuminate\Support\Facades\Route;

// Marketing
Route::get('/', [HomeController::class, 'index'])->name('home');

// Locale switcher (en/hi)
Route::get('/locale/{locale}', [\App\Http\Controllers\LocaleController::class, 'set'])->name('locale.set');

// Blog
Route::get('/blog',          [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}',   [BlogController::class, 'show'])->name('blog.show');

// FAQ
Route::get('/faq',           [FaqController::class, 'index'])->name('faq.index');

// Free public Verifier (lead-generation tool)
Route::get('/verifier',  [VerifierController::class, 'show'])->name('verifier.show');
Route::post('/verifier', [VerifierController::class, 'check'])->name('verifier.check');

// Sample drafts gallery
Route::get('/sample-drafts',         [SampleDraftsController::class, 'index'])->name('sample-drafts.index');
Route::get('/sample-drafts/{slug}',  [SampleDraftsController::class, 'show'])->name('sample-drafts.show');

// Logo gallery (internal — for picking the best concept)
Route::get('/logo-gallery', [\App\Http\Controllers\LogoGalleryController::class, 'index'])->name('logo-gallery');

// Markdown-rendered content pages (Vision / About / Privacy / Contact)
// — content lives in database/content/pages/{slug}.md
Route::get('/vision',  fn () => app(\App\Http\Controllers\MarkdownPageController::class)->show('vision'))->name('pages.vision');
Route::get('/about',   fn () => app(\App\Http\Controllers\MarkdownPageController::class)->show('about'))->name('pages.about');
Route::get('/privacy', fn () => app(\App\Http\Controllers\MarkdownPageController::class)->show('privacy'))->name('pages.privacy');
Route::get('/contact', fn () => app(\App\Http\Controllers\MarkdownPageController::class)->show('contact'))->name('pages.contact');

// Other static pages (still served from Blade for now)
Route::view('/pricing',       'pages.pricing')->name('pages.pricing');
Route::view('/terms',         'pages.terms')->name('pages.terms');
Route::view('/accessibility', 'pages.accessibility')->name('pages.accessibility');

// Contact form submission (markdown contact page also embeds this form)
Route::post('/contact', [ContactController::class, 'submit'])->name('contact.submit');

// Newsletter
Route::post('/newsletter/subscribe',         [NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
Route::get('/newsletter/unsubscribe/{token}',[NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');

// SEO — sitemap index + per-language children
Route::get('/sitemap.xml',          [SeoController::class, 'sitemapIndex']);
Route::get('/sitemap-pages.xml',    [SeoController::class, 'sitemapPages']);
Route::get('/sitemap-posts-en.xml', [SeoController::class, 'sitemapPostsEn']);
Route::get('/sitemap-posts-hi.xml', [SeoController::class, 'sitemapPostsHi']);
Route::get('/sitemap-faqs.xml',     [SeoController::class, 'sitemapFaqs']);
Route::get('/robots.txt',           [SeoController::class, 'robots']);

// OG image generator (SVG, no Imagick required)
Route::get('/og/post/{slug}.svg', [\App\Http\Controllers\OgImageController::class, 'post'])
    ->name('og.post')
    ->where('slug', '[A-Za-z0-9\-]+');
Route::get('/og/page/{title}.svg', [\App\Http\Controllers\OgImageController::class, 'page'])
    ->name('og.page')
    ->where('title', '[A-Za-z0-9=_\-]+');

// Telegram webhook (public — Telegram POSTs here)
Route::post('/webhooks/telegram', [\App\Http\Controllers\Webhooks\TelegramController::class, 'handle'])
    ->name('webhooks.telegram');

// ─── Authentication (web side, separate from Filament admin) ───
Route::get('/login',    [AuthController::class, 'showLogin'])->name('login');
Route::post('/login',   [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::get('/register',  [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/logout',  [AuthController::class, 'logout'])->name('logout');

// ─── Product (auth-gated) ───
Route::middleware(['auth', 'approved'])->prefix('app')->name('app.')->group(function () {
    Route::get('/',                       [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/student-notes',                         [\App\Http\Controllers\App\StudentNoteController::class, 'index'])->name('student-notes.index');
    Route::post('/student-notes',                        [\App\Http\Controllers\App\StudentNoteController::class, 'store'])->name('student-notes.store');
    Route::post('/student-notes/from-youtube',           [\App\Http\Controllers\App\StudentNoteController::class, 'storeFromYoutube'])->name('student-notes.from-youtube');
    Route::get('/student-notes/{note}',                  [\App\Http\Controllers\App\StudentNoteController::class, 'show'])->name('student-notes.show');
    Route::post('/student-notes/{note}/scan',            [\App\Http\Controllers\App\StudentNoteController::class, 'scan'])->name('student-notes.scan');
    Route::post('/student-notes/{note}/questions',       [\App\Http\Controllers\App\StudentNoteController::class, 'generateQuestions'])->name('student-notes.questions');
    Route::post('/student-notes/{note}/polish',          [\App\Http\Controllers\App\StudentNoteController::class, 'polish'])->name('student-notes.polish');
    Route::post('/student-notes/{note}/formula-sheet',  [\App\Http\Controllers\App\StudentNoteController::class, 'formulaSheet'])->name('student-notes.formula-sheet');
    Route::post('/student-notes/{note}/flashcards',     [\App\Http\Controllers\App\StudentNoteController::class, 'flashcards'])->name('student-notes.flashcards');
    Route::post('/student-notes/{note}/ask',            [\App\Http\Controllers\App\StudentNoteController::class, 'askAi'])->name('student-notes.ask');
    Route::post('/student-notes/{note}/pyqs/{pyq}/attempt', [\App\Http\Controllers\App\PYQAttemptController::class, 'store'])->name('student-notes.pyq.attempt');
    Route::delete('/student-notes/{note}',               [\App\Http\Controllers\App\StudentNoteController::class, 'destroy'])->name('student-notes.destroy');

    Route::get('/drafts',                 [DraftController::class, 'index'])->name('drafts.index');
    Route::get('/drafts/new',             [DraftController::class, 'create'])->name('drafts.create');
    Route::post('/drafts',                [DraftController::class, 'store'])->name('drafts.store');
    Route::get('/drafts/{draft}',         [DraftController::class, 'show'])->name('drafts.show');
    Route::post('/drafts/{draft}/edit',   [DraftController::class, 'applyEdit'])->name('drafts.edit');
    Route::post('/drafts/{draft}/revert', [DraftController::class, 'revert'])->name('drafts.revert');
    Route::delete('/drafts/{draft}',      [DraftController::class, 'destroy'])->name('drafts.destroy');

    // Cases
    Route::get('/cases',              [CaseController::class, 'index'])->name('cases.index');
    Route::get('/cases/new',          [CaseController::class, 'create'])->name('cases.create');
    Route::post('/cases',             [CaseController::class, 'store'])->name('cases.store');
    Route::get('/cases/{case}',       [CaseController::class, 'show'])->name('cases.show');
    // Premium tabbed shell — each tab has its own deep-linkable URL.
    Route::get('/cases/{case}/details',    [CaseController::class, 'tabDetails'])->name('cases.tab.details');
    Route::get('/cases/{case}/documents',  [CaseController::class, 'tabDocuments'])->name('cases.tab.documents');
    Route::get('/cases/{case}/bahas',      [CaseController::class, 'tabBahas'])->name('cases.tab.bahas');
    Route::get('/cases/{case}/cross-exam', [CaseController::class, 'tabCrossExam'])->name('cases.tab.cross_exam');
    Route::get('/cases/{case}/samjhao',    [CaseController::class, 'tabSamjhao'])->name('cases.tab.samjhao');
    Route::get('/cases/{case}/analysis',   [CaseController::class, 'tabAnalysis'])->name('cases.tab.analysis');
    Route::get('/cases/{case}/draft',      [CaseController::class, 'tabDraft'])->name('cases.tab.draft');
    Route::get('/cases/{case}/brainstorm', [CaseController::class, 'tabBrainstorm'])->name('cases.tab.brainstorm');
    Route::get('/cases/{case}/notes',      [CaseController::class, 'tabNotes'])->name('cases.tab.notes');
    Route::post('/cases/{case}/close',  [CaseController::class, 'close'])->name('cases.close');
    Route::post('/cases/{case}/reopen', [CaseController::class, 'reopen'])->name('cases.reopen');
    Route::delete('/cases/{case}',    [CaseController::class, 'destroy'])->name('cases.destroy');

    // Hearings
    Route::get('/hearings',           [HearingController::class, 'index'])->name('hearings.index');
    Route::get('/hearings/new',       [HearingController::class, 'create'])->name('hearings.create');
    Route::post('/hearings',          [HearingController::class, 'store'])->name('hearings.store');
    Route::delete('/hearings/{hearing}', [HearingController::class, 'destroy'])->name('hearings.destroy');

    // Clients
    Route::get('/clients',           [ClientController::class, 'index'])->name('clients.index');
    Route::get('/clients/new',       [ClientController::class, 'create'])->name('clients.create');
    Route::post('/clients',          [ClientController::class, 'store'])->name('clients.store');
    Route::get('/clients/{client}',  [ClientController::class, 'show'])->name('clients.show');
    Route::get('/clients/{client}/edit', [ClientController::class, 'edit'])->name('clients.edit');
    Route::patch('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
    Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');

    // Per-Case Analyses (war-room flow: docs → analysis → refine → convert to draft)
    Route::get('/cases/{case}/analyses/new',           [CaseAnalysisController::class, 'create'])->name('cases.analyses.create');
    Route::post('/cases/{case}/analyses',              [CaseAnalysisController::class, 'store'])->name('cases.analyses.store');
    Route::get('/cases/{case}/analyses/{analysis}',    [CaseAnalysisController::class, 'show'])->name('cases.analyses.show');
    Route::get('/cases/{case}/analyses/{analysis}/status', [CaseAnalysisController::class, 'status'])->name('cases.analyses.status');
    Route::post('/cases/{case}/analyses/{analysis}/edit',    [CaseAnalysisController::class, 'applyEdit'])->name('cases.analyses.edit');
    Route::post('/cases/{case}/analyses/{analysis}/convert-to-draft', [CaseAnalysisController::class, 'convertToDraft'])->name('cases.analyses.convert');
    Route::delete('/cases/{case}/analyses/{analysis}', [CaseAnalysisController::class, 'destroy'])->name('cases.analyses.destroy');

    // Per-Case free-form Chat (Conversation)
    Route::get('/cases/{case}/chat',           [ConversationController::class, 'show'])->name('cases.chat.show');
    Route::post('/cases/{case}/chat/messages', [ConversationController::class, 'send'])->name('cases.chat.send');
    Route::post('/cases/{case}/chat/convert-to-draft', [ConversationController::class, 'convertToDraft'])->name('cases.chat.convert');
    // Cross-tab Add to Draft / Add to Notes — seed target thread with any assistant message
    Route::post('/cases/{case}/messages/{message}/copy-to-draft', [ConversationController::class, 'copyToDraft'])->name('cases.chat.copy-to-draft');
    Route::post('/cases/{case}/messages/{message}/copy-to-notes', [ConversationController::class, 'copyToNotes'])->name('cases.chat.copy-to-notes');

    // Case-scoped Documents (upload-only — no analysis triggered)
    Route::post('/cases/{case}/documents',                   [CaseDocumentController::class, 'store'])->name('cases.documents.store');
    Route::delete('/cases/{case}/documents/{document}',      [CaseDocumentController::class, 'destroy'])->name('cases.documents.destroy');

    // Per-Case Karyas (work items: case_brief, timeline, samjhao, ...)
    Route::get('/cases/{case}/karyas/new',              [KaryaController::class, 'create'])->name('cases.karyas.create');
    Route::post('/cases/{case}/karyas',                 [KaryaController::class, 'store'])->name('cases.karyas.store');
    Route::get('/cases/{case}/karyas/{karya}',          [KaryaController::class, 'show'])->name('cases.karyas.show');
    Route::get('/cases/{case}/karyas/{karya}/status',   [KaryaController::class, 'status'])->name('cases.karyas.status');
    Route::delete('/cases/{case}/karyas/{karya}',       [KaryaController::class, 'destroy'])->name('cases.karyas.destroy');

    // Catalogue (browse Vikash bhai's 48 catalogued drafts with filters)
    Route::get('/catalogue',              [CatalogueController::class, 'index'])->name('catalogue.index');
    Route::get('/catalogue/{catalogue}',  [CatalogueController::class, 'show'])->name('catalogue.show');

    // Router (pick uploaded docs → recommend a draft type from the catalogue)
    Route::get('/router',   [RouterController::class, 'show'])->name('router.show');
    Route::post('/router',  [RouterController::class, 'recommend'])->name('router.recommend');

    // Upload-and-draft (the main flow: files in → draft out)
    Route::get('/upload',   [DraftFromFilesController::class, 'show'])->name('upload.show');
    Route::post('/upload',  [DraftFromFilesController::class, 'generate'])->name('upload.generate');

    // Daily log
    Route::post('/daily-log', [\App\Http\Controllers\App\DailyLogController::class, 'store'])->name('daily-log.store');

    // Parent share link (authenticated student generates it)
    Route::get('/parent-link', [\App\Http\Controllers\App\ParentViewController::class, 'shareLink'])->name('parent.share-link');

    // Settings
    Route::get('/settings/profile',  [SettingsController::class, 'profile'])->name('settings.profile');
    Route::patch('/settings/profile',[SettingsController::class, 'updateProfile'])->name('settings.profile.update');
    Route::get('/settings/telegram', [SettingsController::class, 'telegram'])->name('settings.telegram');
    Route::post('/settings/telegram/code',       [SettingsController::class, 'generatePairingCode'])->name('settings.telegram.code');
    Route::post('/settings/telegram/disconnect', [SettingsController::class, 'disconnectTelegram'])->name('settings.telegram.disconnect');
    Route::post('/settings/telegram/toggle',     [SettingsController::class, 'toggleAlerts'])->name('settings.telegram.toggle');
});

// Public parent view — no login needed, token-gated
Route::get('/parent/{token}', [\App\Http\Controllers\App\ParentViewController::class, 'view'])->name('parent.view');
