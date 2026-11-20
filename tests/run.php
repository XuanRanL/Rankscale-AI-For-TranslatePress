<?php
/**
 * Plain-PHP tests, no PHPUnit needed:  php tests/run.php
 * Exit code 0 = all passed.
 */

// Command line only: on a web server this file must do nothing.
if (PHP_SAPI !== 'cli') {
    exit;
}

require __DIR__ . '/bootstrap.php';

use Rankscale\TranslatePress\AI\Helpers\DeepSeekApiHelper;
use Rankscale\TranslatePress\AI\Helpers\ResultAssembler;
use Rankscale\TranslatePress\AI\ServiceProvider\RegisterMachineTranslationEngines;
use Rankscale\TranslatePress\AI\TranslationEngines\DeepSeekTranslationEngine;
use Rankscale\TranslatePress\AI\TranslationEngines\GeminiTranslationEngine;
use Rankscale\TranslatePress\AI\TranslationEngines\OpenAITranslationEngine;

$failures = 0;
$passes   = 0;

function check($name, $condition, $detail = null)
{
    global $failures, $passes;
    if ($condition) {
        $passes++;
        return;
    }
    $failures++;
    echo "FAIL  {$name}\n";
    if ($detail !== null) {
        echo '      ' . (is_string($detail) ? $detail : json_encode($detail, JSON_UNESCAPED_UNICODE)) . "\n";
    }
}

function same($name, $expected, $actual)
{
    check($name, $expected === $actual, ['expected' => $expected, 'actual' => $actual]);
}

function reset_world()
{
    $GLOBALS['test_filters']    = [];
    $GLOBALS['test_http_queue'] = [];
    $GLOBALS['test_http_log']   = [];
    $GLOBALS['test_error_log']  = [];
    $GLOBALS['test_sleep']      = 0;
    $GLOBALS['test_transients'] = [];
}

function http_ok($body)   { return ['response' => ['code' => 200], 'headers' => [], 'body' => is_string($body) ? $body : json_encode($body)]; }
function http_code($code) { return ['response' => ['code' => $code], 'headers' => [], 'body' => '{"error":{"message":"test"}}']; }

function chat_body($content, $finish = 'stop')
{
    return ['choices' => [['finish_reason' => $finish, 'message' => ['role' => 'assistant', 'content' => $content]]]];
}

function deepseek_engine(array $mt = [])
{
    return new DeepSeekTranslationEngine([
        'default-language'                 => 'en_US',
        'trp_machine_translation_settings' => $mt + [DeepSeekTranslationEngine::FIELD_API_KEY => 'test-key-not-a-real-key'],
    ]);
}

// ---------------------------------------------------------------------------------
// Reading a numbered answer
// ---------------------------------------------------------------------------------

$p = DeepSeekApiHelper::parseNumberedAnswer("1. Eins\n2. Zwei\n3. Drei", 3);
same('parser: clean answer', [0 => 'Eins', 1 => 'Zwei', 2 => 'Drei'], $p['items']);
same('parser: clean answer, seen', 3, $p['seen']);
check('parser: clean answer, no flags', !$p['gap'] && !$p['overflow']);

same('parser: old entry point returns the items', [0 => 'Eins', 1 => 'Zwei'], DeepSeekApiHelper::parseTranslatedItems("1. Eins\n2. Zwei", 2));

$p = DeepSeekApiHelper::parseNumberedAnswer("1. Erste Zeile\nzweite Zeile\n2. Zwei", 2);
same('parser: an item may span several lines', "Erste Zeile\nzweite Zeile", $p['items'][0]);

$p = DeepSeekApiHelper::parseNumberedAnswer("<think>1. not this\n2. nor this</think>\n1. Eins\r\n2. Zwei\r\n", 2);
same('parser: thinking block and CRLF', [0 => 'Eins', 1 => 'Zwei'], $p['items']);

$p = DeepSeekApiHelper::parseNumberedAnswer("Here are the translations:\n1. Eins\n2. Zwei", 2);
same('parser: a preface line is ignored', [0 => 'Eins', 1 => 'Zwei'], $p['items']);

// The model skipped item 3. Before, "4. Vier" and "5. Fünf" were glued onto item 2.
$p = DeepSeekApiHelper::parseNumberedAnswer("1. Eins\n2. Zwei\n4. Vier\n5. Fünf", 5);
same('parser: skipped number keeps only the items before it', [0 => 'Eins'], $p['items']);
check('parser: skipped number is reported', $p['gap'] === true && $p['seen'] === 2);

// The model split one item in two: six lines for five items.
$p = DeepSeekApiHelper::parseNumberedAnswer("1. a\n2. b\n3. c\n4. d\n5. e\n6. f", 5);
check('parser: more items than sent is reported', $p['overflow'] === true);

// The model merged two items: four lines for five items, numbered 1-4.
$p = DeepSeekApiHelper::parseNumberedAnswer("1. a\n2. b\n3. c\n4. d", 5);
check('parser: fewer items than sent', $p['seen'] === 4 && !$p['gap'] && !$p['overflow']);

$p = DeepSeekApiHelper::parseNumberedAnswer("1. Schritte:\n1. öffnen\n2. Zwei", 2);
same('parser: a lower number inside an item is text', "Schritte:\n1. öffnen", $p['items'][0]);

$p = DeepSeekApiHelper::parseNumberedAnswer("1. Eins\n2.\n3. Drei", 3);
same('parser: an empty item is left out but counted', [0 => 'Eins', 2 => 'Drei'], $p['items']);
same('parser: an empty item is counted', 3, $p['seen']);

$p = DeepSeekApiHelper::parseNumberedAnswer("1. 5 (stays the same)\n2. Zwei", 2);
same('parser: explanatory parenthesis is removed', '5', $p['items'][0]);

same('parser: empty answer', ['items' => [], 'seen' => 0, 'gap' => false, 'overflow' => false], DeepSeekApiHelper::parseNumberedAnswer('', 3));

// A text with numbered lines of its own, sent alone: the whole answer is its translation.
$p = DeepSeekApiHelper::parseNumberedAnswer("1. Schritte:\n1. öffnen\n2. schließen", 1, true);
same('parser: whole answer for an item sent alone', [0 => "Schritte:\n1. öffnen\n2. schließen"], $p['items']);
$p = DeepSeekApiHelper::parseNumberedAnswer("1. Eins\n2. Zwei", 1);
check('parser: an ordinary single item with a second numbered line is still refused', $p['overflow'] === true);

foreach (["Steps:\n1. open\n2. close" => true, "Steps:\r\n 2. close" => true, "1. Open the box" => false, "Two lines\nno numbers" => false,
          "Version 2.5 is out\nand 3.0 follows" => false, 'Plain' => false] as $text => $expected) {
    same('parser: numbered lines in ' . json_encode($text), $expected, DeepSeekApiHelper::hasNumberedLines($text));
}

// ---------------------------------------------------------------------------------
// Deciding what goes back to TranslatePress
// ---------------------------------------------------------------------------------

$chunk = [12 => 'One', 57 => 'Two', 90 => 'Three', 91 => 'Four'];

$r = ResultAssembler::assemble($chunk, ['items' => ['Eins', 'Zwei', 'Drei', 'Vier'], 'seen' => 4, 'gap' => false, 'overflow' => false]);
same('assembler: complete answer keeps the original keys', [12 => 'Eins', 57 => 'Zwei', 90 => 'Drei', 91 => 'Vier'], $r['translations']);
same('assembler: complete answer status', ResultAssembler::STATUS_OK, $r['status']);

$r = ResultAssembler::assemble($chunk, ['items' => ['Eins', 'Zwei', 'Vier'], 'seen' => 3, 'gap' => false, 'overflow' => false]);
same('assembler: fewer items than sent returns nothing', [], $r['translations']);
same('assembler: fewer items than sent status', ResultAssembler::STATUS_REJECTED, $r['status']);

$r = ResultAssembler::assemble($chunk, ['items' => ['a', 'b', 'c', 'd'], 'seen' => 4, 'gap' => false, 'overflow' => true]);
same('assembler: more items than sent returns nothing', [], $r['translations']);

$r = ResultAssembler::assemble($chunk, ['items' => ['Eins'], 'seen' => 2, 'gap' => true, 'overflow' => false]);
same('assembler: skipped number keeps the items before it', [12 => 'Eins'], $r['translations']);
same('assembler: skipped number status', ResultAssembler::STATUS_PARTIAL, $r['status']);

$r = ResultAssembler::assemble($chunk, ['items' => ['Eins', 'Zwei', 'Dr'], 'seen' => 3, 'gap' => false, 'overflow' => false], true);
same('assembler: cut-off answer drops the last item', [12 => 'Eins', 57 => 'Zwei'], $r['translations']);
same('assembler: cut-off answer status', ResultAssembler::STATUS_TRUNCATED, $r['status']);

$r = ResultAssembler::assemble($chunk, ['items' => ['Ei'], 'seen' => 1, 'gap' => false, 'overflow' => false], true);
same('assembler: cut off inside the first item returns nothing', [], $r['translations']);

$r = ResultAssembler::assemble([1 => 'One', 2 => '→', 3 => 'Three'], ['items' => [0 => 'Eins', 2 => 'Drei'], 'seen' => 3, 'gap' => false, 'overflow' => false]);
same('assembler: empty answer for a symbol keeps the symbol', [1 => 'Eins', 2 => '→', 3 => 'Drei'], $r['translations']);

$r = ResultAssembler::assemble([1 => 'One', 2 => 'Two words', 3 => 'Three'], ['items' => [0 => 'Eins', 2 => 'Drei'], 'seen' => 3, 'gap' => false, 'overflow' => false]);
same('assembler: empty answer for real text is left out, never the source', [1 => 'Eins', 3 => 'Drei'], $r['translations']);

$r = ResultAssembler::assemble($chunk, ['items' => [], 'seen' => 0, 'gap' => false, 'overflow' => false]);
same('assembler: nothing parsed returns nothing', [], $r['translations']);

foreach (['%s' => false, '%1$s / %2$d' => false, '<br>' => false, '&nbsp;&rarr;' => false, '→ 123 %' => false, '$99.00' => false,
          'Size' => true, '<b>Größe</b>' => true, '한국어' => true, '%s reviews' => true] as $text => $expected) {
    same('assembler: translatable text in "' . $text . '"', $expected, ResultAssembler::hasTranslatableText($text));
}

// ---------------------------------------------------------------------------------
// The engine: what TranslatePress gets back
// ---------------------------------------------------------------------------------

reset_world();
$engine = deepseek_engine();
$GLOBALS['test_http_queue'][] = http_ok(chat_body("1. Eins\n2. Zwei"));
same('engine: success returns the translations under their keys', [7 => 'Eins', 9 => 'Zwei'], $engine->translate_array([7 => 'One', 9 => 'Two'], 'de_DE', 'en_US'));
same('engine: one request', 1, count($GLOBALS['test_http_log']));
same('engine: SiliconFlow is the default address', 'https://api.siliconflow.cn/v1/chat/completions', $GLOBALS['test_http_log'][0]['url']);
same('engine: SiliconFlow gets its thinking switch', false, $GLOBALS['test_http_log'][0]['body']['enable_thinking'] ?? null);
same('engine: SiliconFlow output limit', 52000, $GLOBALS['test_http_log'][0]['body']['max_tokens'] ?? null);
check('engine: target language is named in the instruction', strpos($GLOBALS['test_http_log'][0]['body']['messages'][0]['content'], 'into German') !== false);

// The fix this release is about: a failed call must not hand the source text back.
reset_world();
$engine = deepseek_engine();
$GLOBALS['test_http_queue'] = [http_code(500), http_code(500)];
same('engine: HTTP 500 returns nothing', [], $engine->translate_array([7 => 'One', 9 => 'Two'], 'de_DE', 'en_US'));
same('engine: HTTP 500 is tried twice', 2, count($GLOBALS['test_http_log']));

reset_world();
$engine = deepseek_engine();
$GLOBALS['test_http_queue'] = [http_code(401), http_ok(chat_body('1. never used'))];
same('engine: HTTP 401 returns nothing', [], $engine->translate_array([7 => 'One'], 'de_DE', 'en_US'));
same('engine: HTTP 401 is not retried', 1, count($GLOBALS['test_http_log']));

reset_world();
$engine = deepseek_engine();
$GLOBALS['test_http_queue'] = [new WP_Error('http_request_failed', 'cURL error 28: Operation timed out after 180001 milliseconds'), http_ok(chat_body('1. never used'))];
same('engine: timeout returns nothing', [], $engine->translate_array([7 => 'One'], 'de_DE', 'en_US'));
same('engine: timeout is not retried', 1, count($GLOBALS['test_http_log']));

reset_world();
$engine = deepseek_engine();
$GLOBALS['test_http_queue'] = [new WP_Error('http_request_failed', 'cURL error 56: Recv failure: Connection reset by peer'), http_ok(chat_body('1. Eins'))];
same('engine: connection reset is retried', [7 => 'Eins'], $engine->translate_array([7 => 'One'], 'de_DE', 'en_US'));

reset_world();
$engine = deepseek_engine();
$GLOBALS['test_http_queue'][] = http_ok(chat_body(''));
same('engine: empty answer returns nothing', [], $engine->translate_array([7 => 'One'], 'de_DE', 'en_US'));

reset_world();
$engine = deepseek_engine();
$GLOBALS['test_http_queue'][] = http_ok('{"error":{"message":"bad"}}');
same('engine: error body with HTTP 200 returns nothing', [], $engine->translate_array([7 => 'One'], 'de_DE', 'en_US'));

reset_world();
$engine = deepseek_engine();
$GLOBALS['test_http_queue'][] = http_ok(chat_body("1. Eins\n2. Zwei\n3. Dre", 'length'));
same('engine: cut-off answer keeps only complete items', [1 => 'Eins', 2 => 'Zwei'], $engine->translate_array([1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four'], 'de_DE', 'en_US'));

reset_world();
$engine = deepseek_engine();
$GLOBALS['test_http_queue'][] = http_ok(chat_body("1. Eins\n2. Zwei und Drei\n3. Vier"));
same('engine: merged items return nothing', [], $engine->translate_array([1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four'], 'de_DE', 'en_US'));

reset_world();
$engine = deepseek_engine();
$GLOBALS['test_http_queue'][] = http_ok(chat_body("1. <b>Eins</b>\n2. <i>Zwei</i>"));
same('engine: markup is not added to plain text', [1 => '&lt;b&gt;Eins&lt;/b&gt;', 2 => '<i>Zwei</i>'], $engine->translate_array([1 => 'One', 2 => '<i>Two</i>'], 'de_DE', 'en_US'));

// One TranslatePress chunk is one request; a failed chunk does not take the others with it.
reset_world();
$GLOBALS['test_filters']['trp_ai_chunk_size'] = function () { return 2; };
$engine = deepseek_engine();
same('engine: chunk size follows the filter', 2, $engine->get_chunk_size());
$GLOBALS['test_http_queue'] = [http_code(400), http_ok(chat_body("1. Drei\n2. Vier"))];
same('engine: only the failed chunk is left out', [3 => 'Drei', 4 => 'Vier'], $engine->translate_array([1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four'], 'de_DE', 'en_US'));

// A text with numbered lines of its own is sent in a request of its own, after the shared one.
reset_world();
$engine = deepseek_engine();
$GLOBALS['test_http_queue'] = [http_ok(chat_body("1. Eins\n2. Drei")), http_ok(chat_body("1. Schritte:\n1. öffnen\n2. schließen"))];
same('engine: a text with numbered lines goes alone and keeps them', [1 => 'Eins', 3 => 'Drei', 2 => "Schritte:\n1. öffnen\n2. schließen"],
    $engine->translate_array([1 => 'One', 2 => "Steps:\n1. open\n2. close", 3 => 'Three'], 'de_DE', 'en_US'));
same('engine: two requests for it', 2, count($GLOBALS['test_http_log']));
check('engine: the shared request does not contain it', strpos($GLOBALS['test_http_log'][0]['body']['messages'][1]['content'], 'Steps:') === false);

reset_world();
same('engine: default chunk size', 20, deepseek_engine()->get_chunk_size());
$GLOBALS['test_filters']['trp_ai_chunk_size'] = function () { return 500; };
same('engine: chunk size is capped', 50, deepseek_engine()->get_chunk_size());

// ---------------------------------------------------------------------------------
// The DeepSeek engine's request, per endpoint
// ---------------------------------------------------------------------------------

reset_world();
$engine = deepseek_engine([DeepSeekTranslationEngine::FIELD_API_ENDPOINT => 'deepseek']);
$GLOBALS['test_http_queue'][] = http_ok(chat_body('1. Eins'));
$engine->translate_array([1 => 'One'], 'de_DE', 'en_US');
$body = $GLOBALS['test_http_log'][0]['body'];
check('request: DeepSeek official gets no SiliconFlow fields', !array_key_exists('enable_thinking', $body) && !array_key_exists('reasoning_effort', $body));
same('request: DeepSeek official model', 'deepseek-v4-flash', $body['model']);

reset_world();
$engine = deepseek_engine([
    DeepSeekTranslationEngine::FIELD_API_ENDPOINT     => 'custom',
    DeepSeekTranslationEngine::FIELD_CUSTOM_API_URL   => 'https://gateway.example.test/v1/chat/completions',
    DeepSeekTranslationEngine::FIELD_CUSTOM_MODEL     => 'my-gateway-model',
    DeepSeekTranslationEngine::FIELD_REASONING_EFFORT => 'low',
    DeepSeekTranslationEngine::FIELD_ENABLE_THINKING  => 'yes',
]);
$GLOBALS['test_http_queue'][] = http_ok(chat_body('1. Eins'));
$engine->translate_array([1 => 'One'], 'de_DE', 'en_US');
$request = $GLOBALS['test_http_log'][0];
same('request: the custom address is used as entered', 'https://gateway.example.test/v1/chat/completions', $request['url']);
same('request: custom model', 'my-gateway-model', $request['body']['model']);
same('request: reasoning effort is sent', 'low', $request['body']['reasoning_effort'] ?? null);
check('request: reasoning effort replaces the thinking switch', !array_key_exists('enable_thinking', $request['body']) && !array_key_exists('thinking_budget', $request['body']));
same('request: custom endpoint output limit is unchanged', 52000, $request['body']['max_tokens'] ?? null);
same('request: key goes in the Authorization header', 'Bearer test-key-not-a-real-key', $request['args']['headers']['Authorization']);
same('request: low effort keeps the shorter timeout', 180, $request['args']['timeout']);

reset_world();
$engine = deepseek_engine([
    DeepSeekTranslationEngine::FIELD_API_ENDPOINT   => 'custom',
    DeepSeekTranslationEngine::FIELD_CUSTOM_API_URL => 'https://gateway.example.test/api/chat/completions',
]);
$GLOBALS['test_http_queue'][] = http_ok(chat_body('1. Eins'));
$engine->translate_array([1 => 'One'], 'de_DE', 'en_US');
$request = $GLOBALS['test_http_log'][0];
same('request: any custom address is used as entered', 'https://gateway.example.test/api/chat/completions', $request['url']);
same('request: custom endpoint without effort keeps the thinking switch', false, $request['body']['enable_thinking'] ?? null);

// Unchanged from earlier versions: "Custom URL" with an empty address means SiliconFlow.
reset_world();
same('request: custom endpoint without an address', 'https://api.siliconflow.cn/v1/chat/completions', deepseek_engine([DeepSeekTranslationEngine::FIELD_API_ENDPOINT => 'custom'])->get_api_url());

reset_world();
$GLOBALS['test_filters']['trp_ai_system_instruction'] = function ($text, $engine_key, $target, $source) { return $text . "\nWebsite context: an online shop. [{$engine_key}|{$target}|{$source}]"; };
$GLOBALS['test_filters']['trp_ai_max_output_tokens']  = function () { return 0; };
$GLOBALS['test_filters']['trp_ai_temperature']        = function () { return null; };
$GLOBALS['test_filters']['trp_ai_request_timeout']    = function () { return 40; };
$engine = deepseek_engine();
$GLOBALS['test_http_queue'][] = http_ok(chat_body('1. Eins'));
$engine->translate_array([1 => 'One'], 'de_DE', 'en_US');
$request = $GLOBALS['test_http_log'][0];
check('filters: site context reaches the instruction', strpos($request['body']['messages'][0]['content'], 'an online shop. [deepseek_translate|de|en]') !== false);
check('filters: output limit and temperature can be left out', !array_key_exists('max_tokens', $request['body']) && !array_key_exists('temperature', $request['body']));
same('filters: request timeout', 40, $request['args']['timeout']);

// ---------------------------------------------------------------------------------
// Pause after repeated failures
// ---------------------------------------------------------------------------------

// Three failed requests in a row (each one already retried once) pause translation.
reset_world();
$engine = deepseek_engine();
$GLOBALS['test_http_queue'] = [http_code(500), http_code(500), http_code(500), http_code(500), http_code(500), http_code(500), http_ok(chat_body('1. never used'))];
for ($i = 0; $i < 3; $i++) {
    $engine->translate_array([1 => 'One'], 'de_DE', 'en_US');
}
same('pause: three failed requests were sent', 6, count($GLOBALS['test_http_log']));
same('pause: the next call returns nothing', [], $engine->translate_array([1 => 'One'], 'de_DE', 'en_US'));
same('pause: and sends nothing', 6, count($GLOBALS['test_http_log']));
check('pause: it ends by itself', isset($GLOBALS['test_transients']['trp_ai_pause_deepseek_translate']) && $GLOBALS['test_transients']['trp_ai_pause_deepseek_translate']['ttl'] === 600);

// A success in between starts the count again.
reset_world();
$engine = deepseek_engine();
$GLOBALS['test_http_queue'] = [http_code(400), http_code(400), http_ok(chat_body('1. Eins')), http_code(400), http_code(400), http_ok(chat_body('1. Eins'))];
for ($i = 0; $i < 5; $i++) {
    $engine->translate_array([1 => 'One'], 'de_DE', 'en_US');
}
same('pause: failures that are not in a row do not pause', [1 => 'Eins'], $engine->translate_array([1 => 'One'], 'de_DE', 'en_US'));

// A refused key pauses at once: asking again cannot help.
reset_world();
$engine = deepseek_engine();
$GLOBALS['test_http_queue'] = [http_code(401), http_ok(chat_body('1. never used'))];
$engine->translate_array([1 => 'One'], 'de_DE', 'en_US');
same('pause: a refused key pauses at once', [], $engine->translate_array([1 => 'One'], 'de_DE', 'en_US'));
same('pause: a refused key, one request only', 1, count($GLOBALS['test_http_log']));

// The first failed request of a call stops the rest of that call when it pauses.
reset_world();
$GLOBALS['test_filters']['trp_ai_chunk_size'] = function () { return 1; };
$engine = deepseek_engine();
$GLOBALS['test_http_queue'] = [http_code(403), http_ok(chat_body('1. never used'))];
same('pause: later requests of the same call are not sent', [], $engine->translate_array([1 => 'One', 2 => 'Two'], 'de_DE', 'en_US'));
same('pause: one request', 1, count($GLOBALS['test_http_log']));

// Answers nothing can be taken from count as failures too.
reset_world();
$engine = deepseek_engine();
$GLOBALS['test_http_queue'] = [http_ok(chat_body('no numbers here')), http_ok(chat_body('still none')), http_ok(chat_body('and again')), http_ok(chat_body('1. never used'))];
for ($i = 0; $i < 3; $i++) {
    $engine->translate_array([1 => 'One'], 'de_DE', 'en_US');
}
same('pause: three unusable answers pause', [], $engine->translate_array([1 => 'One'], 'de_DE', 'en_US'));
same('pause: three unusable answers, three requests', 3, count($GLOBALS['test_http_log']));

// The pause can be switched off.
reset_world();
$GLOBALS['test_filters']['trp_ai_failure_pause'] = function () { return 0; };
$engine = deepseek_engine();
$GLOBALS['test_http_queue'] = [http_code(401), http_code(401), http_code(401), http_code(401), http_ok(chat_body('1. Eins'))];
for ($i = 0; $i < 4; $i++) {
    $engine->translate_array([1 => 'One'], 'de_DE', 'en_US');
}
same('pause: switched off by the filter', [1 => 'Eins'], $engine->translate_array([1 => 'One'], 'de_DE', 'en_US'));

// The test request of the settings page is never held back.
reset_world();
$engine = deepseek_engine();
$GLOBALS['test_http_queue'] = [http_code(401), http_ok(chat_body("1. Eins\n2. Zwei"))];
$engine->translate_array([1 => 'One'], 'de_DE', 'en_US');
$test = $engine->test_request();
same('pause: the settings page test still reaches the service', 200, $test['response']['code']);

reset_world();
same('model: an ID with a colon is accepted', 'qwen3:30b', deepseek_engine([DeepSeekTranslationEngine::FIELD_CUSTOM_MODEL => 'qwen3:30b'])->get_model_name());
same('model: an unusable ID falls back', 'deepseek-ai/DeepSeek-V3.2', deepseek_engine([DeepSeekTranslationEngine::FIELD_CUSTOM_MODEL => 'bad model"'])->get_model_name());
same('effort: an unknown value is ignored', '', deepseek_engine([DeepSeekTranslationEngine::FIELD_REASONING_EFFORT => 'ultra'])->get_reasoning_effort());

// ---------------------------------------------------------------------------------
// Saving the settings
// ---------------------------------------------------------------------------------

$register = new RegisterMachineTranslationEngines();
$stored = [
    DeepSeekTranslationEngine::FIELD_API_KEY          => 'test-key-not-a-real-key',
    DeepSeekTranslationEngine::FIELD_API_ENDPOINT     => 'custom',
    DeepSeekTranslationEngine::FIELD_CUSTOM_API_URL   => 'https://gateway.example.test/v1/chat/completions',
    DeepSeekTranslationEngine::FIELD_CUSTOM_MODEL     => 'my-gateway-model',
    DeepSeekTranslationEngine::FIELD_REASONING_EFFORT => 'medium',
    DeepSeekTranslationEngine::FIELD_ENABLE_THINKING  => 'no',
    DeepSeekTranslationEngine::FIELD_THINKING_BUDGET  => 4096,
];
// WordPress runs the same routine when settings are saved from code with the stored values.
same('settings: saving the stored values changes nothing', $stored, $register->sanitize_settings([], $stored));

$posted = $stored;
unset($posted[DeepSeekTranslationEngine::FIELD_ENABLE_THINKING]);
same('settings: an unticked box is stored as no', 'no', $register->sanitize_settings([], $posted)[DeepSeekTranslationEngine::FIELD_ENABLE_THINKING]);
same('settings: a ticked box is stored as yes', 'yes', $register->sanitize_settings([], [DeepSeekTranslationEngine::FIELD_ENABLE_THINKING => 'yes'] + $stored)[DeepSeekTranslationEngine::FIELD_ENABLE_THINKING]);
same('settings: an unknown reasoning effort is not stored', '', $register->sanitize_settings([], [DeepSeekTranslationEngine::FIELD_REASONING_EFFORT => 'ultra'] + $stored)[DeepSeekTranslationEngine::FIELD_REASONING_EFFORT]);
same('settings: thinking budget is kept in range', 32768, $register->sanitize_settings([], [DeepSeekTranslationEngine::FIELD_THINKING_BUDGET => 999999] + $stored)[DeepSeekTranslationEngine::FIELD_THINKING_BUDGET]);

// ---------------------------------------------------------------------------------
// OpenAI and Gemini answers
// ---------------------------------------------------------------------------------

$openai = function (array $mt = []) {
    return new OpenAITranslationEngine(['default-language' => 'en_US', 'trp_machine_translation_settings' => $mt + [OpenAITranslationEngine::FIELD_API_KEY => 'test-key-not-a-real-key']]);
};

reset_world();
$GLOBALS['test_http_queue'][] = http_ok(['status' => 'completed', 'output' => [['type' => 'reasoning'], ['type' => 'message', 'content' => [['type' => 'output_text', 'text' => "1. Eins\n2. Zwei"]]]]]);
same('openai: Responses API answer', [1 => 'Eins', 2 => 'Zwei'], $openai()->translate_array([1 => 'One', 2 => 'Two'], 'de_DE', 'en_US'));
check('openai: reasoning model gets no temperature', !array_key_exists('temperature', $GLOBALS['test_http_log'][0]['body']));

reset_world();
$GLOBALS['test_http_queue'][] = http_ok(['status' => 'incomplete', 'incomplete_details' => ['reason' => 'max_output_tokens'], 'output' => [['type' => 'message', 'content' => [['type' => 'output_text', 'text' => "1. Eins\n2. Zw"]]]]]);
same('openai: cut-off Responses answer keeps only complete items', [1 => 'Eins'], $openai()->translate_array([1 => 'One', 2 => 'Two'], 'de_DE', 'en_US'));

reset_world();
$GLOBALS['test_http_queue'][] = http_ok(chat_body("1. Eins\n2. Zw", 'length'));
same('openai: cut-off Chat Completions answer', [1 => 'Eins'], $openai([OpenAITranslationEngine::FIELD_ENDPOINT => 'chat_completions', OpenAITranslationEngine::FIELD_MODEL => 'gpt-4.1'])->translate_array([1 => 'One', 2 => 'Two'], 'de_DE', 'en_US'));
same('openai: non-reasoning model keeps its temperature', 0.3, $GLOBALS['test_http_log'][0]['body']['temperature'] ?? null);

reset_world();
same('openai: fine-tuned model ID is accepted', 'ft:gpt-4.1:acme::abc123', $openai([OpenAITranslationEngine::FIELD_MODEL => 'custom', OpenAITranslationEngine::FIELD_CUSTOM_MODEL => 'ft:gpt-4.1:acme::abc123'])->get_model());

$gemini = function () {
    return new GeminiTranslationEngine(['default-language' => 'en_US', 'trp_machine_translation_settings' => [GeminiTranslationEngine::FIELD_API_KEY => 'test-key-not-a-real-key']]);
};

reset_world();
$GLOBALS['test_http_queue'][] = http_ok(['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => 'thinking…', 'thought' => true], ['text' => "1. Eins\n"], ['text' => '2. Zwei']]]]]]);
same('gemini: parts are joined, thoughts skipped', [1 => 'Eins', 2 => 'Zwei'], $gemini()->translate_array([1 => 'One', 2 => 'Two'], 'de_DE', 'en_US'));

reset_world();
$GLOBALS['test_http_queue'][] = http_ok(['candidates' => [['finishReason' => 'MAX_TOKENS', 'content' => ['parts' => [['text' => "1. Eins\n2. Zw"]]]]]]);
same('gemini: cut-off answer keeps only complete items', [1 => 'Eins'], $gemini()->translate_array([1 => 'One', 2 => 'Two'], 'de_DE', 'en_US'));

reset_world();
$GLOBALS['test_http_queue'][] = http_ok(['promptFeedback' => ['blockReason' => 'SAFETY']]);
same('gemini: blocked answer returns nothing', [], $gemini()->translate_array([1 => 'One'], 'de_DE', 'en_US'));

// ---------------------------------------------------------------------------------

echo "\n{$passes} passed, {$failures} failed\n";
exit($failures > 0 ? 1 : 0);
