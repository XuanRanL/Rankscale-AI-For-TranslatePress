<?php
namespace Rankscale\TranslatePress\AI;

use Rankscale\TranslatePress\AI\ServiceProvider\RegisterMachineTranslationEngines;
use Rankscale\TranslatePress\AI\ServiceProvider\RegisterGeminiTranslationEngine;
use Rankscale\TranslatePress\AI\ServiceProvider\RegisterOpenAITranslationEngine;
use Rankscale\TranslatePress\AI\ServiceProvider\RegisterScripts;

/**
 * @desc plugin init entry
 * Class Init
 * @package Rankscale\TranslatePress\AI
 */
class Init
{
    /**
     * @return string[]
     * @desc get registered services
     */
    public static function getService(): array
    {
        return [
            RegisterScripts::class,
            RegisterMachineTranslationEngines::class,
            RegisterGeminiTranslationEngine::class,
            RegisterOpenAITranslationEngine::class,
        ];
    }

    /**
     * @return void
     * @desc load registered services
     */
    public static function registerService()
    {
        foreach (self::getService() as $class) {
            $service = self::instantiate($class);
            if (method_exists($service, 'register')) {
                $service->register();
            }
        }
    }

    public static function instantiate($class)
    {
        return new $class;
    }
}
