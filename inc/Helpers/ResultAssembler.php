<?php
namespace Rankscale\TranslatePress\AI\Helpers;

/**
 * Decides which translations of one answer are handed back to TranslatePress.
 *
 * TranslatePress saves every string an engine returns as "machine translated" and never
 * asks for it again. A string that is NOT returned stays untranslated and is requested
 * again on a later page load. So returning the source text for a failed, cut-off or
 * misnumbered answer stores the untranslated text as the translation for good; leaving
 * the string out costs one more request later. This class keeps that decision in one
 * place, without WordPress, so it can be tested on its own.
 */
class ResultAssembler
{
    const STATUS_OK        = 'ok';
    const STATUS_PARTIAL   = 'partial';
    const STATUS_TRUNCATED = 'truncated';
    const STATUS_REJECTED  = 'rejected';

    /**
     * @param array $chunk     Strings that were sent, original keys preserved.
     * @param array $parsed    Result of AbstractApiHelper::parseNumberedAnswer().
     * @param bool  $truncated True when the service stopped at its output limit.
     * @return array{translations: array, status: string, reason: string, expected: int, received: int}
     */
    public static function assemble(array $chunk, array $parsed, $truncated = false)
    {
        $expected = count($chunk);
        $items    = (isset($parsed['items']) && is_array($parsed['items'])) ? $parsed['items'] : [];
        $seen     = isset($parsed['seen']) ? (int) $parsed['seen'] : 0;

        if ($seen < 1) {
            return self::result([], self::STATUS_REJECTED, 'no numbered items in the answer', $expected);
        }

        // More items than were sent: one item was split in two, so every item after the
        // split sits one position too late. Nothing can be matched to its string safely.
        if (!empty($parsed['overflow'])) {
            return self::result([], self::STATUS_REJECTED, 'more items than were sent', $expected);
        }

        $usable = $seen;
        $reason = '';

        if ($truncated) {
            // Cut off at the output limit, usually in the middle of an item: the last
            // item that was started may be half a sentence.
            $usable = $seen - 1;
            $reason = 'answer cut off at the output limit';
        } elseif (!empty($parsed['gap'])) {
            // The parser already dropped the item in front of the skipped number.
            $usable = $seen - 1;
            $reason = 'a number was skipped in the answer';
        } elseif ($seen < $expected) {
            // A finished answer with fewer items than were sent: two items were merged
            // and the rest renumbered, so the items after the merge sit on the wrong
            // strings and there is no way to tell where the merge happened.
            return self::result([], self::STATUS_REJECTED, 'fewer items than were sent', $expected);
        }

        $translations = [];
        $position     = 0;
        foreach ($chunk as $key => $source) {
            if ($position >= $usable) {
                break;
            }
            if (isset($items[$position]) && is_string($items[$position]) && $items[$position] !== '') {
                $translations[$key] = $items[$position];
            } elseif (!self::hasTranslatableText($source)) {
                // The item came back empty and there was nothing to translate in it
                // (symbols, numbers, placeholders): keep it as it is, so it is not sent
                // again on every page load.
                $translations[$key] = $source;
            } elseif ($reason === '') {
                $reason = 'an item came back empty';
            }
            $position++;
        }

        if (empty($translations)) {
            return self::result([], self::STATUS_REJECTED, $reason !== '' ? $reason : 'no usable items', $expected);
        }

        if (count($translations) === $expected) {
            return self::result($translations, self::STATUS_OK, '', $expected);
        }

        return self::result($translations, $truncated ? self::STATUS_TRUNCATED : self::STATUS_PARTIAL, $reason, $expected);
    }

    /**
     * False for strings made only of markup, entities, placeholders, digits and symbols.
     *
     * @param mixed $text
     * @return bool
     */
    public static function hasTranslatableText($text)
    {
        $text = (string) $text;
        $bare = preg_replace('/<[^>]*>/u', ' ', $text);
        if (!is_string($bare)) {
            // Not valid UTF-8: cannot be judged, so never treat it as "nothing to translate".
            return true;
        }
        $bare = preg_replace('/&(?:#\d+|#x[0-9a-f]+|[a-z][a-z0-9]*);/i', ' ', $bare);
        $bare = preg_replace('/%(?:\d+\$)?[-+0-9.]*[sdfu]/', ' ', $bare);

        return (bool) preg_match('/\p{L}/u', (string) $bare);
    }

    private static function result(array $translations, $status, $reason, $expected)
    {
        return [
            'translations' => $translations,
            'status'       => $status,
            'reason'       => $reason,
            'expected'     => $expected,
            'received'     => count($translations),
        ];
    }
}
