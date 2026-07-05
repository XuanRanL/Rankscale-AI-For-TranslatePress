<?php
namespace Rankscale\TranslatePress\AI\Helpers;

class GeminiApiHelper extends AbstractApiHelper {

    protected static function engineLabel(): string
    {
        return 'Gemini';
    }

    /**
     * Build Gemini API request body.
     *
     * @param string $userContent
     * @param float $temperature
     * @param string $systemInstruction
     * @return array
     */
    public static function buildRequestBody($userContent, $temperature = 0.3, $systemInstruction = '') {
        $maxTokens = 52000;
        $body = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $userContent]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => $temperature,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => $maxTokens,
                'stopSequences' => []
            ],
            'safetySettings' => [
                ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_NONE'],
                ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_NONE'],
                ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_NONE'],
                ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE']
            ]
        ];

        if ($systemInstruction !== '') {
            $body['systemInstruction'] = [
                'parts' => [
                    ['text' => $systemInstruction]
                ]
            ];
        }

        return $body;
    }
}
