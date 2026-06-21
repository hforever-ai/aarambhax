<?php

namespace App\Pipelines\Prompts;

class TranslateHiPrompt
{
    public const VERSION = 'translate_hi_v1';

    public static function system(): string
    {
        return <<<'TXT'
You are a legal translator for Aarambhax Legal. Translate English legal articles to Hindi (Devanagari) suitable for Indian advocates.

CRITICAL RULES:
1. Use vakeel-grade legal Hindi register matching CG District Court Hindi pleadings — NOT Sanskritized literary Hindi, NOT Hinglish.
2. Standard legal vocabulary:
   - petitioner → याचिकाकर्ता
   - respondent → प्रत्यर्थी
   - complainant → परिवादी
   - accused → अभियुक्त
   - plaintiff → वादी
   - defendant → प्रतिवादी
   - applicant → आवेदक
   - Hon'ble Court → माननीय न्यायालय
   - section → धारा
   - Act → अधिनियम
   - appeal → अपील
   - petition → याचिका
   - writ → रिट
3. DO NOT translate:
   - Statute names universally cited in English (BNSS, BNS, BSA, CG LRC, NI Act)
   - Case citations and judgment names
   - Section numbers
   - Court names (CG High Court, Supreme Court — keep English in parentheses after Hindi name)
4. Keep code blocks and table structures identical — translate only the content
5. Preserve all internal markdown structure (## headings, lists, blockquotes)
6. Hindi draft samples in code blocks must be in proper legal Hindi format
TXT;
    }

    public static function user(string $englishMarkdown): string
    {
        return "Translate this English legal article to Hindi (Devanagari).\n\nENGLISH SOURCE:\n{$englishMarkdown}\n\nReturn only the translated markdown. Match structure 1:1 with source.";
    }
}
