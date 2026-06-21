<?php

namespace App\Services\AarambhAi;

use RuntimeException;

/**
 * Composes the system prompt for a Karya by stacking:
 *   1. Safety (POCSO redaction — always on)
 *   2. Karya-type fragment (case_brief.md / timeline.md / etc.)
 *   3. Forum context (reuse existing forum/{name}.md fragments)
 *   4. Language fragment (en / hi / hinglish / bilingual)
 *   5. Case state JSON
 *
 * Distinct from PromptComposer (which is for analyse/draft/chat). Both
 * coexist; this one is purely for Karya runs.
 */
class KaryaPromptComposer
{
    private string $karyasDir;
    private string $sharedDir;

    public function __construct()
    {
        $base = base_path('app/Pipelines/Prompts');
        $this->karyasDir = $base.'/karyas';
        $this->sharedDir = $base.'/fragments';
    }

    public function build(
        string $karyaType,
        string $forum,
        string $language,
        array $caseStateJson = [],
        ?string $userInstruction = null,
    ): string {
        $entry = KaryaCatalogue::get($karyaType);
        if (! $entry) {
            throw new RuntimeException("Unknown Karya type: {$karyaType}");
        }

        $parts = [];
        $parts[] = $this->fragOrEmpty($this->sharedDir.'/safety/pocso_redaction.md');
        $parts[] = $this->fragOrThrow($this->karyasDir.'/'.$entry['prompt_file']);
        $parts[] = $this->fragOrEmpty($this->sharedDir."/forum/{$forum}.md");
        $parts[] = $this->resolveLanguageFragment($language);

        if (! empty($caseStateJson)) {
            $parts[] = "## CASE PROFILE (runtime, from case state)\n\n```json\n"
                .json_encode($caseStateJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                ."\n```";
        }

        if ($userInstruction) {
            $parts[] = "## ADVOCATE'S INSTRUCTION FOR THIS RUN\n\n".$userInstruction;
        }

        return implode("\n\n---\n\n", array_filter($parts, fn ($p) => $p !== ''));
    }

    private function resolveLanguageFragment(string $language): string
    {
        // 'hinglish' is unique to Karya; the rest reuse shared language fragments
        if ($language === 'hinglish') {
            return "## LANGUAGE — Hinglish\n\n"
                  ."Output Hinglish: Devanagari + roman script mixed, the way Indian "
                  ."advocates speak in chambers. NOT formal Hindi, NOT formal English. "
                  ."See examples in the Karya prompt above. Section numbers + party names "
                  ."stay as in the source documents.";
        }
        return $this->fragOrEmpty($this->sharedDir."/language/{$language}.md");
    }

    private function fragOrThrow(string $path): string
    {
        if (! is_file($path)) {
            throw new RuntimeException("Karya fragment missing: {$path}");
        }
        return rtrim(file_get_contents($path));
    }

    private function fragOrEmpty(string $path): string
    {
        return is_file($path) ? rtrim(file_get_contents($path)) : '';
    }
}
