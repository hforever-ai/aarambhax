<?php

namespace App\Services\AarambhAi;

use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Builds the final system prompt for an LLM call by stacking fragment
 * markdown files in a fixed order:
 *
 *   1. Safety (POCSO redaction — ALWAYS prepended)
 *   2. Persona (analyst / drafter / chat-assistant / critic)
 *   3. Forum (cg_hc / cg_district / cg_revenue / cg_sessions / cg_family / sc / tribunal)
 *   4. Language (en / hi / bilingual)
 *   5. Draft type (only for drafter persona — pulls catalogue-specific guidance)
 *   6. Overlays (lrc_170b, schedule_v_tribal, bilaspur_hc_rules_2007 — opt-in)
 *   7. Case profile (runtime data from CaseRecord.state_json)
 *
 * Fragments live at app/Pipelines/Prompts/fragments/{section}/{name}.md.
 * Each is a tiny markdown file (~200-500 tokens). Easy to edit + version.
 *
 * The composed prompt is the source of truth: PHP composes here, ships
 * the full string to the VPS, VPS forwards as-is to Gemini. No fragment
 * drift between PHP and Python.
 */
class PromptComposer
{
    private string $fragmentRoot;

    public function __construct(?string $fragmentRoot = null)
    {
        $this->fragmentRoot = $fragmentRoot ?? base_path('app/Pipelines/Prompts/fragments');
    }

    /**
     * @param string      $persona         analyst | drafter | chat-assistant | critic
     * @param string      $forum           cg_hc | cg_district | cg_revenue | cg_sessions | cg_family | sc | tribunal
     * @param string      $language        en | hi | bilingual
     * @param string|null $draftTypeSlug   only used when persona=drafter; references prompts/draft_type/{slug}.md if exists
     * @param string[]    $overlays        e.g. ['lrc_170b', 'schedule_v_tribal']
     * @param bool        $pocsoMode       always-on safety prepend (default true)
     * @param array       $caseStateJson   CaseRecord.state_json — embedded as the runtime CASE PROFILE block
     */
    public function build(
        string $persona,
        string $forum,
        string $language,
        ?string $draftTypeSlug = null,
        array $overlays = [],
        bool $pocsoMode = true,
        array $caseStateJson = [],
    ): string {
        // Collect the fragment paths we'll actually use so we can compute
        // cache key based on their max mtime. Any file edit auto-busts cache.
        $fragPaths = [];
        if ($pocsoMode) {
            $fragPaths[] = 'safety/pocso_redaction.md';
        }
        $fragPaths[] = "persona/{$persona}.md";
        $fragPaths[] = "forum/{$forum}.md";
        $fragPaths[] = "language/{$language}.md";
        if ($persona === 'drafter' && $draftTypeSlug && $this->exists("draft_type/{$draftTypeSlug}.md")) {
            $fragPaths[] = "draft_type/{$draftTypeSlug}.md";
        }
        foreach ($overlays as $overlay) {
            if ($this->exists("overlay/{$overlay}.md")) {
                $fragPaths[] = "overlay/{$overlay}.md";
            }
        }

        $maxMtime = max(array_map(
            fn ($p) => (int) filemtime($this->fragmentRoot.'/'.$p),
            $fragPaths,
        ));

        $cacheKey = 'sys_prompt:'
            .implode(':', array_filter([$persona, $forum, $language, $draftTypeSlug]))
            .':'.implode(',', $overlays)
            .':'.md5(json_encode($caseStateJson))
            .':'.$maxMtime;

        return Cache::remember($cacheKey, now()->addDay(), function () use ($fragPaths, $caseStateJson) {
            $parts = array_map(fn ($p) => $this->frag($p), $fragPaths);

            if (! empty($caseStateJson)) {
                $parts[] = "## CASE PROFILE (runtime, from case state)\n\n```json\n"
                    .json_encode($caseStateJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    ."\n```";
            }

            return implode("\n\n---\n\n", array_filter($parts));
        });
    }

    private function frag(string $relativePath): string
    {
        $full = $this->fragmentRoot.'/'.$relativePath;
        if (! is_file($full)) {
            throw new RuntimeException("Prompt fragment missing: {$relativePath} at {$full}");
        }
        return rtrim(file_get_contents($full));
    }

    private function exists(string $relativePath): bool
    {
        return is_file($this->fragmentRoot.'/'.$relativePath);
    }
}
