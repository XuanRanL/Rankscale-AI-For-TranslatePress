<?php
namespace Rankscale\TranslatePress\AI\Helpers;

abstract class AbstractApiHelper {

    const supportedLanguages = [
        'af' => 'Afrikaans',
        'am' => 'Amharic',
        'ar' => 'Arabic',
        'az' => 'Azerbaijani',
        'be' => 'Belarusian',
        'bg' => 'Bulgarian',
        'bn' => 'Bengali',
        'bs' => 'Bosnian',
        'ca' => 'Catalan',
        'ceb' => 'Cebuano',
        'cs' => 'Czech',
        'cy' => 'Welsh',
        'da' => 'Danish',
        'de' => 'German',
        'el' => 'Greek',
        'en' => 'English',
        'eo' => 'Esperanto',
        'es' => 'Spanish',
        'et' => 'Estonian',
        'eu' => 'Basque',
        'fa' => 'Persian',
        'fi' => 'Finnish',
        'fil' => 'Filipino',
        'fr' => 'French',
        'ga' => 'Irish',
        'gl' => 'Galician',
        'gu' => 'Gujarati',
        'ha' => 'Hausa',
        'he' => 'Hebrew',
        'hi' => 'Hindi',
        'hmn' => 'Hmong',
        'hr' => 'Croatian',
        'ht' => 'Haitian Creole',
        'hu' => 'Hungarian',
        'hy' => 'Armenian',
        'id' => 'Indonesian',
        'ig' => 'Igbo',
        'is' => 'Icelandic',
        'it' => 'Italian',
        'ja' => 'Japanese',
        'jv' => 'Javanese',
        'ka' => 'Georgian',
        'kk' => 'Kazakh',
        'km' => 'Khmer',
        'kn' => 'Kannada',
        'ko' => 'Korean',
        'ku' => 'Kurdish',
        'ky' => 'Kyrgyz',
        'lo' => 'Lao',
        'lt' => 'Lithuanian',
        'lv' => 'Latvian',
        'mg' => 'Malagasy',
        'mi' => 'Maori',
        'mk' => 'Macedonian',
        'ml' => 'Malayalam',
        'mn' => 'Mongolian',
        'mr' => 'Marathi',
        'ms' => 'Malay',
        'mt' => 'Maltese',
        'my' => 'Myanmar (Burmese)',
        'nb' => 'Norwegian Bokmål',
        'ne' => 'Nepali',
        'nl' => 'Dutch',
        'nn' => 'Norwegian Nynorsk',
        'no' => 'Norwegian',
        'pa' => 'Punjabi',
        'pl' => 'Polish',
        'ps' => 'Pashto',
        'pt' => 'Portuguese',
        'ro' => 'Romanian',
        'ru' => 'Russian',
        'sd' => 'Sindhi',
        'si' => 'Sinhala',
        'sk' => 'Slovak',
        'sl' => 'Slovenian',
        'so' => 'Somali',
        'sq' => 'Albanian',
        'sr' => 'Serbian',
        'su' => 'Sundanese',
        'sv' => 'Swedish',
        'sw' => 'Swahili',
        'ta' => 'Tamil',
        'te' => 'Telugu',
        'tg' => 'Tajik',
        'th' => 'Thai',
        'tk' => 'Turkmen',
        'tl' => 'Tagalog',
        'tr' => 'Turkish',
        'uk' => 'Ukrainian',
        'ur' => 'Urdu',
        'uz' => 'Uzbek',
        'vi' => 'Vietnamese',
        'xh' => 'Xhosa',
        'yi' => 'Yiddish',
        'yo' => 'Yoruba',
        'zh-cn' => 'Chinese (simplified)',
        'zh-tw' => 'Chinese (traditional)',
        'zu' => 'Zulu',
    ];

    /**
     * @return string
     */
    abstract protected static function engineLabel(): string;

    /**
     * @param string $targetLang
     * @param string $sourceLang
     * @return string
     */
    public static function getSystemInstruction($targetLang, $sourceLang = 'auto')
    {
        $targetLangName = static::supportedLanguages[$targetLang] ?? $targetLang;
        $sourceLangName = ($sourceLang === 'auto') ? '' : (static::supportedLanguages[$sourceLang] ?? $sourceLang);

        $prompt = "You are a professional localization translator. Translate each numbered item into {$targetLangName}.\n\n";
        $prompt .= "IMPORTANT RULES:\n";
        $prompt .= "1) Output ONLY the translations. No headings, notes, JSON/Markdown/code fences, or extra lines.\n";
        $prompt .= "2) Keep the exact numbering format (1. 2. 3. …). One item per line; the number of output lines MUST equal the number of input items. Do not skip/merge/split/renumber.\n";
        $prompt .= "3) Preserve HTML EXACTLY (tags, nesting, attributes, comments). Translate only visible text nodes; NEVER add/remove/reorder tags or attributes.\n";
        $prompt .= "4) If an item contains NO '<' or '>' in the original, you MUST NOT output '<' or '>' in the translation (avoid introducing fake tags).\n";
        $prompt .= "5) Keep placeholders/tokens unchanged: %s, %1\$s, %(name)s, {name}, {{var}}, {count, plural, …}, [shortcode], URLs/emails.\n";
        $prompt .= "6) Preserve whitespace, punctuation, and HTML entities exactly; do not add quotes or normalize.\n";
        $prompt .= "7) Do NOT add explanatory parentheses like '(the number stays the same / se mantiene igual / 没有内容)'. If an item is empty or only untranslatable tokens, return it unchanged.\n";
        $prompt .= "8) Preserve all emoji characters (e.g. 😀🎉🔥) exactly as they appear. NEVER replace, remove, or convert emojis to '?' or any other character.\n";
        $prompt .= "9) If an item looks like a URL slug (lowercase, hyphen-separated words, no spaces), translate it as a URL slug: lowercase output, words separated by hyphens, no special characters.\n";

        if ($sourceLang !== 'auto' && $sourceLangName) {
            $prompt .= "\nSource language: {$sourceLangName}\n";
        }

        return $prompt;
    }

    /**
     * @param array $texts
     * @return string
     */
    public static function getUserContent($texts)
    {
        $counter = 0;
        $itemsList = implode("\n", array_map(function($text) use (&$counter) {
            return (++$counter) . ". " . $text;
        }, $texts));

        return "Items:\n{$itemsList}\n\nTranslations:";
    }

    /**
     * @param array $texts
     * @param string $sourceLang
     * @param string $targetLang
     * @return string
     */
    public static function convert($texts, $sourceLang, $targetLang)
    {
        return static::getSystemInstruction($targetLang, $sourceLang) . "\n" . static::getUserContent($texts);
    }

    /**
     * @param string $content
     * @param int $expectedCount
     * @return array
     */
    public static function parseTranslatedItems($content, $expectedCount) {
        $content = (string) $content;
        if ($content === '') {
            return [];
        }

        $content = preg_replace('/<think>[\s\S]*?<\/think>\s*/u', '', $content);

        $lines = explode("\n", $content);
        $items = [];
        $currentIndex = null;
        $currentLines = [];
        $nextExpected = 1;

        foreach ($lines as $line) {
            if (preg_match('/^\s*(\d+)\.\s*(.*)$/', $line, $matches)) {
                $num = (int) $matches[1];
                $isSequential = ($num === $nextExpected && $num <= $expectedCount) || ($currentIndex === null && $num === 1);

                if ($isSequential) {
                    if ($currentIndex !== null) {
                        $text = trim(implode("\n", $currentLines));
                        $text = static::cleanupTranslatedText($text);
                        if ($text !== '') {
                            $items[$currentIndex] = $text;
                        }
                    }
                    $currentIndex = $num - 1;
                    $currentLines = [$matches[2]];
                    $nextExpected = $num + 1;
                } elseif ($currentIndex !== null) {
                    $currentLines[] = $line;
                }
            } elseif ($currentIndex !== null) {
                $currentLines[] = $line;
            }
        }

        if ($currentIndex !== null) {
            $text = trim(implode("\n", $currentLines));
            $text = static::cleanupTranslatedText($text);
            if ($text !== '') {
                $items[$currentIndex] = $text;
            }
        }

        ksort($items);

        if (count($items) !== $expectedCount) {
            error_log(sprintf('[Rankscale AI] %s: parsed %d items but expected %d', static::engineLabel(), count($items), $expectedCount));
        }

        return $items;
    }

    protected static function cleanupTranslatedText($text) {
        $text = preg_replace('/\x{200B}\s*\((?>[^<>]*?)\)\s*\x{200B}/u', '', $text);
        $text = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $text);

        $hasHtml = (strpos($text, '<') !== false) || (strpos($text, '>') !== false);
        if (!$hasHtml) {
            $explanatoryPatterns = implode('|', [
                'se mantiene igual',
                'no hay contenido',
                'El n[uú]mero se mantiene',
                '保持不变',
                '没有内容',
                '数字保持不变',
                'stays?\s+the\s+same',
                'remains?\s+unchanged',
                'no\s+content\s+to\s+translate',
                'reste\s+le\s+m[eê]me',
                'pas\s+de\s+contenu',
                'bleibt\s+gleich',
                'kein\s+Inhalt',
                'そのまま',
                '内容なし',
                '그대로\s*유지',
                '내용\s*없음',
                'the\s+number\s+stays',
            ]);
            $text = preg_replace('/\(\s*(?:' . $explanatoryPatterns . ')(?>[^)]*?)\)/ui', '', $text);
        }

        return trim($text);
    }
}
