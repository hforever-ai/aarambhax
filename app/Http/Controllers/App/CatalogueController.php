<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\DraftCatalogueEntry;
use Illuminate\Http\Request;

class CatalogueController extends Controller
{
    public function index(Request $request)
    {
        $forum    = $request->query('forum');
        $language = $request->query('language');
        $district = $request->query('district');
        $q        = $request->query('q');

        $query = DraftCatalogueEntry::query()->orderBy('forum')->orderBy('draft_type');

        if ($forum)    $query->where('forum', $forum);
        if ($language) $query->where('language', $language);
        if ($district) $query->where('district', $district);
        if ($q) {
            $query->where(function ($q2) use ($q) {
                $q2->where('draft_type', 'like', "%{$q}%")
                   ->orWhere('court', 'like', "%{$q}%")
                   ->orWhere('source_filename', 'like', "%{$q}%");
            });
        }

        $entries = $query->get();

        $facets = [
            'forums'    => DraftCatalogueEntry::query()->whereNotNull('forum')->distinct()->orderBy('forum')->pluck('forum'),
            'languages' => DraftCatalogueEntry::query()->whereNotNull('language')->distinct()->orderBy('language')->pluck('language'),
            'districts' => DraftCatalogueEntry::query()->whereNotNull('district')->distinct()->orderBy('district')->pluck('district'),
        ];

        $stats = [
            'total'        => DraftCatalogueEntry::count(),
            'by_forum'     => DraftCatalogueEntry::query()->selectRaw('forum, COUNT(*) as c')->groupBy('forum')->pluck('c', 'forum'),
            'by_language'  => DraftCatalogueEntry::query()->selectRaw('language, COUNT(*) as c')->groupBy('language')->pluck('c', 'language'),
            'avg_conf'     => round((float) DraftCatalogueEntry::avg('ai_confidence'), 2),
        ];

        return view('app.catalogue.index', [
            'entries' => $entries,
            'facets'  => $facets,
            'stats'   => $stats,
            'filters' => compact('forum', 'language', 'district', 'q'),
        ]);
    }

    public function show(DraftCatalogueEntry $catalogue)
    {
        return view('app.catalogue.show', ['entry' => $catalogue]);
    }
}
