<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Response;

/**
 * Generates Open Graph social cards on the fly as SVG.
 *
 * No Imagick / GD dependency — pure SVG with branded background, served as
 * image/svg+xml so Twitter/Facebook treat it like a normal image.
 *
 * Routes:
 *   GET /og/post/{slug}.svg     → blog post OG card
 *   GET /og/page/{title}.svg    → arbitrary title OG card (used for static pages)
 */
class OgImageController extends Controller
{
    private const W = 1200;
    private const H = 630;

    public function post(string $slug): Response
    {
        $post = Post::where('slug', $slug)->published()->firstOrFail();
        $kicker = $post->category?->name_en ?? 'Aarambhax Legal';
        return $this->render($post->title, $kicker, $post->author?->name);
    }

    public function page(string $title): Response
    {
        $title = base64_decode($title) ?: 'Aarambhax Legal';
        return $this->render($title, 'Aarambhax Legal');
    }

    private function render(string $title, string $kicker, ?string $byline = null): Response
    {
        $title = e($title);
        $kicker = e(strtoupper($kicker));
        $byline = $byline ? e($byline) : null;

        // Word-wrap title for SVG manually (max 24 chars per line, max 4 lines)
        $lines = $this->wrapTitle($title, 24, 4);
        $lineY = 280;
        $titleSvg = '';
        foreach ($lines as $line) {
            $titleSvg .= '<text x="80" y="'.$lineY.'" fill="#F8F6F0" font-family="Fraunces, Georgia, serif" font-size="64" font-weight="500">'.$line.'</text>';
            $lineY += 78;
        }

        $bylineSvg = $byline
            ? '<text x="80" y="540" fill="#94A3B8" font-family="Inter, sans-serif" font-size="22">By '.$byline.'</text>'
            : '';

        $svg = <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630" viewBox="0 0 1200 630">
  <defs>
    <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%"   stop-color="#0B1F3A"/>
      <stop offset="100%" stop-color="#070E1C"/>
    </linearGradient>
  </defs>
  <rect width="1200" height="630" fill="url(#bg)"/>
  <rect x="60" y="60" width="1080" height="510" fill="none" stroke="#1E3A5F" stroke-width="2" rx="16"/>

  <!-- Brand wordmark top-left -->
  <text x="80" y="130" fill="#F8F6F0" font-family="Fraunces, Georgia, serif" font-size="32" font-weight="500">
    Aarambha<tspan fill="#D4B062">x</tspan> <tspan fill="#94A3B8" font-weight="400">Legal</tspan>
  </text>

  <!-- Kicker (category or label) -->
  <text x="80" y="190" fill="#D4B062" font-family="Inter, sans-serif" font-size="18" letter-spacing="3" font-weight="600">{$kicker}</text>

  <!-- Wrapped title -->
  {$titleSvg}

  <!-- Byline (optional) -->
  {$bylineSvg}

  <!-- Bottom URL -->
  <text x="80" y="585" fill="#94A3B8" font-family="JetBrains Mono, monospace" font-size="18">aarambhax.net</text>
  <text x="1120" y="585" fill="#94A3B8" font-family="JetBrains Mono, monospace" font-size="18" text-anchor="end">Drafts for every court</text>
</svg>
SVG;

        return response($svg, 200)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'public, max-age=86400, immutable');
    }

    private function wrapTitle(string $title, int $maxChars, int $maxLines): array
    {
        $words = preg_split('/\s+/', $title);
        $lines = [];
        $current = '';
        foreach ($words as $w) {
            if (mb_strlen($current.' '.$w) > $maxChars && $current !== '') {
                $lines[] = $current;
                $current = $w;
            } else {
                $current = $current === '' ? $w : $current.' '.$w;
            }
            if (count($lines) >= $maxLines) break;
        }
        if ($current !== '' && count($lines) < $maxLines) {
            $lines[] = $current;
        }
        if (count($lines) === $maxLines && mb_strlen(end($lines)) > $maxChars - 3) {
            $lines[$maxLines - 1] = mb_substr($lines[$maxLines - 1], 0, $maxChars - 3).'…';
        }
        return $lines;
    }
}
