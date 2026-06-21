<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;

/**
 * Generic controller that renders markdown files from database/content/pages/.
 *
 * Lets us update Vision / About / Privacy / Contact content by editing a
 * markdown file (no Blade redeploy). Add a new page = drop a {slug}.md into
 * database/content/pages/ and add a route to it.
 */
class MarkdownPageController extends Controller
{
    /** @var array<string, array{title: string, description: string}> */
    private const PAGE_META = [
        'vision' => [
            'title' => 'Vision — Aarambhax Legal',
            'description' => 'Our vision: a fair beginning — for children through Shrutam.ai, for advocates through Aarambhax Legal.',
        ],
        'about' => [
            'title' => 'About Us — Aarambhax Legal',
            'description' => 'AI Aarambh — the parent of Shrutam.ai (free K-10 education) and Aarambhax Legal (AI drafting for Indian advocates).',
        ],
        'privacy' => [
            'title' => 'Privacy Policy — Aarambhax Legal',
            'description' => 'How Aarambhax Legal handles advocate-client privileged information under India\'s DPDP Act, 2023.',
        ],
        'contact' => [
            'title' => 'Contact Us — Aarambhax Legal',
            'description' => 'Get in touch with the Aarambhax team — beta feedback, privacy queries, partnerships.',
        ],
    ];

    public function show(string $slug)
    {
        if (! array_key_exists($slug, self::PAGE_META)) {
            abort(404);
        }

        $path = database_path('content/pages/'.$slug.'.md');
        abort_unless(is_file($path), 404, "Content for page '{$slug}' not found.");

        $markdown = file_get_contents($path);

        // Render with Laravel's built-in commonmark
        $html = Str::markdown($markdown, [
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
        ]);

        return view('pages.markdown-page', [
            'slug' => $slug,
            'title' => self::PAGE_META[$slug]['title'],
            'description' => self::PAGE_META[$slug]['description'],
            'html' => $html,
            'show_contact_form' => $slug === 'contact',
        ]);
    }
}
