jQuery(function($) {
    function toggleDeepSeekFields() {
        var endpoint = $('#trp-deepseek-api-endpoint').val();
        if (endpoint === 'custom') {
            $('#trp-deepseek-custom-url-wrapper').show();
        } else {
            $('#trp-deepseek-custom-url-wrapper').hide();
        }

        var thinkingEnabled = $('#trp-deepseek-enable-thinking').is(':checked');
        if (thinkingEnabled) {
            $('#trp-deepseek-thinking-budget-wrapper').show();
        } else {
            $('#trp-deepseek-thinking-budget-wrapper').hide();
        }
    }

    function toggleCustomModelField(selectId, wrapperId) {
        var $select = $(selectId);
        if (!$select.length) {
            return;
        }
        if ($select.val() === 'custom') {
            $(wrapperId).show();
        } else {
            $(wrapperId).hide();
        }
    }

    function toggleGeminiFields() {
        toggleCustomModelField('#trp-gemini-model', '#trp-gemini-custom-model-wrapper');
    }

    function toggleOpenAIFields() {
        toggleCustomModelField('#trp-openai-model', '#trp-openai-custom-model-wrapper');
    }

    $('#trp-deepseek-api-endpoint').on('change', toggleDeepSeekFields);
    $('#trp-deepseek-enable-thinking').on('change', toggleDeepSeekFields);
    $('#trp-gemini-model').on('change', toggleGeminiFields);
    $('#trp-openai-model').on('change', toggleOpenAIFields);

    toggleDeepSeekFields();
    toggleGeminiFields();
    toggleOpenAIFields();
});
