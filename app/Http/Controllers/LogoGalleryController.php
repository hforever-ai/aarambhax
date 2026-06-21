<?php

namespace App\Http\Controllers;

class LogoGalleryController extends Controller
{
    public function index()
    {
        $indexPath = public_path('images/logo-concepts/index.json');
        $concepts = [];

        if (is_file($indexPath)) {
            $raw = json_decode((string) file_get_contents($indexPath), true) ?: [];
            foreach ($raw as $c) {
                if (! empty($c['svg_path']) && is_file(public_path($c['svg_path']))) {
                    $concepts[] = [
                        'id'        => $c['id'] ?? 'unknown',
                        'name'      => $c['name'] ?? $c['id'] ?? 'Unnamed',
                        'direction' => $c['direction'] ?? '',
                        'svg_path'  => $c['svg_path'],
                        'svg'       => file_get_contents(public_path($c['svg_path'])),
                        'size'      => filesize(public_path($c['svg_path'])),
                    ];
                }
            }
        }

        return view('pages.logo-gallery', compact('concepts'));
    }
}
