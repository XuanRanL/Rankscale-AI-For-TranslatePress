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

    $('#trp-deepseek-api-endpoint').on('change', toggleDeepSeekFields);
    $('#trp-deepseek-enable-thinking').on('change', toggleDeepSeekFields);

    toggleDeepSeekFields();
});
