<?php
namespace Rankscale\TranslatePress\AI\Helpers;

class DeepSeekApiHelper extends AbstractApiHelper {

    protected static function engineLabel(): string
    {
        return 'DeepSeek';
    }
}
