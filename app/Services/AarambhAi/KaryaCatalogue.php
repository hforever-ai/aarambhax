<?php

namespace App\Services\AarambhAi;

/**
 * Static registry of Karya types. Each entry describes:
 *   slug         — the type identifier (matches karyas.type column)
 *   label_en     — display name (English)
 *   label_hi     — display name (Hindi/Devanagari)
 *   group        — phase grouping for the picker UI
 *   prompt_file  — fragment file under app/Pipelines/Prompts/karyas/
 *   model        — 'flash' or 'pro' — picks Gemini tier
 *   description  — 1-line for the picker
 *   default_lang — output language default
 *
 * Adding a new Karya = add an entry here + drop a .md file in karyas/.
 */
class KaryaCatalogue
{
    public const TYPES = [
        'case_brief' => [
            'slug' => 'case_brief',
            'label_en' => 'Case Brief',
            'label_hi' => 'केस का सारांश',
            'group' => 'A. Intake',
            'prompt_file' => 'case_brief.md',
            'model' => 'pro',
            'description' => '1-page summary of the case file — facts, parties, history, gaps.',
            'default_lang' => 'en',
        ],
        'timeline' => [
            'slug' => 'timeline',
            'label_en' => 'Timeline of Events',
            'label_hi' => 'समय रेखा',
            'group' => 'A. Intake',
            'prompt_file' => 'timeline.md',
            'model' => 'flash',
            'description' => 'Chronological table of dated events with source docs.',
            'default_lang' => 'en',
        ],
        'argument_synopsis' => [
            'slug' => 'argument_synopsis',
            'label_en' => 'Argument Synopsis',
            'label_hi' => 'तर्क सारांश',
            'group' => 'C. Hearing prep',
            'prompt_file' => 'argument_synopsis.md',
            'model' => 'pro',
            'description' => 'Court-room talking points for the next hearing.',
            'default_lang' => 'en',
        ],
        'reply_to_notice' => [
            'slug' => 'reply_to_notice',
            'label_en' => 'Reply to Notice',
            'label_hi' => 'जवाब',
            'group' => 'B. Drafting',
            'prompt_file' => 'reply_to_notice.md',
            'model' => 'pro',
            'description' => 'Para-wise reply to a legal notice or pleading.',
            'default_lang' => 'en',
        ],
        'samjhao' => [
            'slug' => 'samjhao',
            'label_en' => 'Plain Explainer',
            'label_hi' => 'समझाओ',
            'group' => 'D. Comprehension',
            'prompt_file' => 'samjhao.md',
            'model' => 'flash',
            'description' => 'Plain explanation — what happened, why it matters, what to do.',
            'default_lang' => 'hinglish',
        ],
    ];

    public static function get(string $slug): ?array
    {
        return self::TYPES[$slug] ?? null;
    }

    public static function all(): array
    {
        return self::TYPES;
    }

    public static function grouped(): array
    {
        $out = [];
        foreach (self::TYPES as $slug => $entry) {
            $out[$entry['group']][$slug] = $entry;
        }
        ksort($out);
        return $out;
    }
}
