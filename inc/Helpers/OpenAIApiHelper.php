<?php
namespace Rankscale\TranslatePress\AI\Helpers;

class OpenAIApiHelper extends AbstractApiHelper {

    protected static function engineLabel(): string
    {
        return 'OpenAI';
    }
}
