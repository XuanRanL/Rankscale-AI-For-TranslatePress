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
     * temperature/topP/topK are intentionally NOT sent: Google deprecated them
     * starting with Gemini 3.5/3.6 (currently ignored, will return HTTP 400 in
     * future model generations).
     *
     * @param string $userContent
     * @param string $systemInstruction
     * @param int $maxOutputTokens
     * @return array
     */
    public static function buildRequestBody($userContent, $systemInstruction = '', $maxOutputTokens = 52000) {
        $body = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $userContent]
                    ]
                ]
            ],
            'generationConfig' => [
                'maxOutputTokens' => (int) $maxOutputTokens,
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
