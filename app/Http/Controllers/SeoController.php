<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Post;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function sitemapIndex(): Response
    {
        $maps = ['pages', 'posts-en', 'posts-hi', 'faqs'];
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($maps as $m) {
            $xml .= "  <sitemap>\n    <loc>".url("sitemap-{$m}.xml")."</loc>\n    <lastmod>".now()->toIso8601String()."</lastmod>\n  </sitemap>\n";
        }
        $xml .= '</sitemapindex>';
        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    public function sitemapPages(): Response
    {
        $urls = [
            ['loc' => url('/'),                       'priority' => '1.0', 'changefreq' => 'weekly'],
            ['loc' => route('verifier.show'),         'priority' => '0.9', 'changefreq' => 'weekly'],
            ['loc' => route('sample-drafts.index'),   'priority' => '0.8', 'changefreq' => 'monthly'],
            ['loc' => route('blog.index'),            'priority' => '0.9', 'changefreq' => 'daily'],
            ['loc' => route('faq.index'),             'priority' => '0.9', 'changefreq' => 'weekly'],
            ['loc' => route('pages.about'),           'priority' => '0.7', 'changefreq' => 'monthly'],
            ['loc' => route('pages.pricing'),         'priority' => '0.8', 'changefreq' => 'monthly'],
            ['loc' => route('pages.contact'),         'priority' => '0.6', 'changefreq' => 'yearly'],
            ['loc' => route('pages.privacy'),         'priority' => '0.4', 'changefreq' => 'yearly'],
            ['loc' => route('pages.terms'),           'priority' => '0.4', 'changefreq' => 'yearly'],
            ['loc' => route('pages.accessibility'),   'priority' => '0.4', 'changefreq' => 'yearly'],
        ];
        return $this->buildSitemap($urls);
    }

    public function sitemapPostsEn(): Response
    {
        return $this->postsByLang('en');
    }

    public function sitemapPostsHi(): Response
    {
        return $this->postsByLang('hi');
    }

    public function sitemapFaqs(): Response
    {
        $urls = [];
        Faq::published()->lang('en')->each(function (Faq $f) use (&$urls) {
            $urls[] = [
                'loc' => route('faq.index').'#'.$f->slug,
                'lastmod' => $f->updated_at->toIso8601String(),
                'priority' => '0.5',
                'changefreq' => 'monthly',
            ];
        });
        return $this->buildSitemap($urls);
    }

    private function postsByLang(string $lang): Response
    {
        $urls = [];
        Post::published()->lang($lang)->select('slug', 'updated_at', 'translation_group_id', 'language')->orderByDesc('updated_at')->limit(5000)
            ->each(function (Post $p) use (&$urls) {
                // hreflang alternates if a translation exists
                $alternates = [];
                if ($p->translation_group_id) {
                    $sib = Post::where('translation_group_id', $p->translation_group_id)
                        ->where('language', '!=', $p->language)
                        ->first();
                    if ($sib) {
                        $alternates = [$sib->language => route('blog.show', $sib->slug)];
                    }
                }
                $urls[] = [
                    'loc' => route('blog.show', $p->slug),
                    'lastmod' => $p->updated_at->toIso8601String(),
                    'priority' => '0.7',
                    'changefreq' => 'monthly',
                    'alternates' => $alternates,
                    'self_lang' => $p->language === 'hi' ? 'hi-IN' : 'en-IN',
                ];
            });
        return $this->buildSitemap($urls, withHreflang: true);
    }

    private function buildSitemap(array $urls, bool $withHreflang = false): Response
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
        if ($withHreflang) $xml .= ' xmlns:xhtml="http://www.w3.org/1999/xhtml"';
        $xml .= ">\n";
        foreach ($urls as $u) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>'.htmlspecialchars($u['loc']).'</loc>'."\n";
            if (isset($u['lastmod'])) $xml .= '    <lastmod>'.$u['lastmod'].'</lastmod>'."\n";
            $xml .= '    <changefreq>'.($u['changefreq'] ?? 'monthly').'</changefreq>'."\n";
            $xml .= '    <priority>'.($u['priority'] ?? '0.5').'</priority>'."\n";
            if ($withHreflang && ! empty($u['alternates'])) {
                $xml .= '    <xhtml:link rel="alternate" hreflang="'.($u['self_lang'] ?? 'en-IN').'" href="'.htmlspecialchars($u['loc']).'"/>'."\n";
                foreach ($u['alternates'] as $lang => $href) {
                    $code = $lang === 'hi' ? 'hi-IN' : 'en-IN';
                    $xml .= '    <xhtml:link rel="alternate" hreflang="'.$code.'" href="'.htmlspecialchars($href).'"/>'."\n";
                }
            }
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>'."\n";
        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    public function robots(): Response
    {
        $body = "User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /app/\nDisallow: /webhooks/\n\nSitemap: ".url('sitemap.xml')."\n";
        return response($body, 200)->header('Content-Type', 'text/plain');
    }
}
