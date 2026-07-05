# Rankscale AI For TranslatePress

[简体中文](README.md) · **English** · [Français](README.fr.md) · [Español](README.es.md) · [Deutsch](README.de.md)

Rankscale AI For TranslatePress is a powerful TranslatePress extension that adds support for multiple AI translation engines to your WordPress site. With optimized translation prompts, it effectively avoids garbled characters and unnecessary explanatory text in translations.

## ✨ Key Features

*   **Multi-Engine Support**:
    *   **DeepSeek API**: High-quality translation via SiliconFlow, the official DeepSeek endpoint, or a custom endpoint (supports DeepSeek-V3.2 and deep-thinking mode).
    *   **Google Gemini API**: Supports the latest models such as Gemini 2.5 Flash / Pro and Gemini 3 Flash, with a generous free tier.
    *   **OpenAI (ChatGPT) API**: Supports models such as GPT-5.4 and GPT-5.2, compatible with both the Responses API and Chat Completions.
*   **Deep-Thinking Mode**: The DeepSeek engine supports `enable_thinking`, letting the model reason before translating to improve quality. The Thinking Budget is configurable (128-32768 tokens).
*   **Automatic Retry**: On API rate limiting (429) or temporary server failures (5xx), it automatically retries with an exponential backoff strategy to ensure translation jobs complete.
*   **Smart Optimization**:
    *   An optimized System Message prompt architecture for higher translation quality and better rule adherence.
    *   Automatically strips explanatory text that AI may add (supports multilingual patterns for EN/ZH/JA/KO/FR/DE/ES and more).
    *   HTML injection protection: automatically prevents AI from introducing HTML tags into plain-text translations.
*   **Flexible Configuration**:
    *   DeepSeek: switch between SiliconFlow / official DeepSeek / custom API endpoints.
    *   Gemini: choose Flash (fast), Flash-Lite (cheapest), Pro (highest quality), or Gemini 3 (cutting-edge) models.
    *   OpenAI: supports both the Responses API (recommended) and Chat Completions (compatibility) endpoints.
    *   API keys are displayed as passwords for better security.
*   **Multisite Support**: Compatible with WordPress Multisite, including network-activated TranslatePress.
*   **Efficient Architecture**: Built on abstract base classes for clean, maintainable code; adding a new translation engine requires very little code.

## 📋 Requirements

*   WordPress 6.0 or higher
*   PHP 7.2 or higher
*   The [TranslatePress](https://wordpress.org/plugins/translatepress-multilingual/) plugin installed and enabled

## 🚀 Installation & Configuration

1.  **Install the plugin**:
    *   Download the plugin zip package.
    *   In the WordPress admin, go to **Plugins > Add New > Upload Plugin**, upload the zip package, and install it.
    *   Activate the plugin.

2.  **Configure a translation engine**:
    *   Go to **Settings > TranslatePress**.
    *   Click the **Automatic Translation** tab.
    *   Under **Translation Engine**, select the AI engine you want:
        *   **DeepSeek**: great for Chinese and multilingual translation, supports deep-thinking mode.
        *   **Google Gemini**: supports more languages, fast, with a high free tier.
        *   **OpenAI**: uses models such as GPT-5.4/5.2 for high-quality translation.
    *   Enter the corresponding **API Key**.
    *   (Optional) DeepSeek: choose the API endpoint (SiliconFlow / official DeepSeek / custom URL), enable deep-thinking mode, and adjust the Thinking Budget.
    *   (Optional) Gemini: choose a specific model version.
    *   (Optional) OpenAI: choose the endpoint type and model.

3.  **Start translating**:
    *   After saving your settings, TranslatePress will automatically use the AI engine you configured to translate your site content.

## ❓ FAQ

### How do I get a DeepSeek API key?
1.  Visit the [SiliconFlow website](https://cloud.siliconflow.cn/).
2.  Register or log in to your account.
3.  Create a new API Key on the API key management page.
4.  By default this plugin uses the DeepSeek-V3.2 model provided by SiliconFlow, which is fast and low-cost.
5.  You can also switch to the official DeepSeek endpoint or a custom URL in the settings.

### How do I get a Google Gemini API key?
1.  Visit [Google AI Studio](https://aistudio.google.com/app/apikey).
2.  Sign in with your Google account.
3.  Click "Create API Key".
4.  Copy the generated API key (the Gemini API currently offers a generous free tier for most users).

### How do I get an OpenAI API key?
1.  Visit the [OpenAI Platform](https://platform.openai.com/api-keys).
2.  Register or log in to your account.
3.  Create a new API Key.

### Is there a character limit for translation?
*   **DeepSeek**: billed per token; enabling deep-thinking mode increases token usage (including reasoning tokens). See the official SiliconFlow documentation for details.
*   **Gemini**: offers a free tier, but rate limits may apply under high concurrency or heavy usage.
*   **OpenAI**: billed per token; pricing varies by model.

### What if I hit API rate limits?
The plugin includes a built-in automatic retry mechanism. When the API returns 429 (rate limited) or 5xx (server error), it automatically waits and retries (up to 2 times), with no manual intervention needed.

## 📅 Changelog

### 2.0.0
*   **Architecture refactor**: Introduced three abstract base classes - AbstractTranslationEngine, AbstractApiHelper, and AbstractRegisterEngine - removing about 1,000 lines of duplicate code.
*   **New: Automatic retry**: Automatic exponential-backoff retry on 429/5xx errors (up to 2 times), respecting the Retry-After response header.
*   **New: Multisite support**: Compatible with network-activated TranslatePress on WordPress Multisite.
*   **New: DeepSeek endpoint configuration**: Choose between SiliconFlow, official DeepSeek, and custom URL endpoints.
*   **Improved: System Message architecture**: DeepSeek and OpenAI use separate system/user messages; Gemini uses the systemInstruction field.
*   **Improved: Gemini model update**: Replaced with the latest models such as Gemini 2.5 Flash/Pro and Gemini 3 Flash.
*   **Improved: OpenAI model update**: Added GPT-5.4 support.
*   **Improved: Gemini prompt enhancement**: Upgraded to the same detailed 8-rule set as DeepSeek/OpenAI.
*   **Improved: Translation parsing fix**: Fixed the translation misalignment caused by `array_values()` in `parseTranslatedItems()`.
*   **Improved: Gemini parameter fix**: Chunk size adjusted from 100 to 20, and maxOutputTokens increased from 8192 to 65536.
*   **Improved: `cleanupTranslatedText`**: Extended multilingual cleanup patterns covering EN/ZH/JA/KO/FR/DE/ES and more.
*   **Security: HTML injection protection**: All three engines now uniformly add `sanitize_injected_markup()` protection.
*   **Security: API key masked display**: All API key input fields changed to `type="password"`.
*   **Fix: Version number sync**: The plugin header and Common::PLUGIN_VERSION are kept consistent.
*   **Fix: DeepSeek language completion**: Added Hindi, Thai, and Vietnamese support.
*   **Fix: Error logging**: All engines add detailed error logs for non-200 responses and WP_Error.
*   **Fix: Enhanced API key validation**: Added a minimum-length check in addition to the empty-value check.
*   **Fix: DeepSeek language mapping**: Filled in the missing pt_BR/pt_PT mappings.

### 1.6.0
*   New: DeepSeek deep-thinking mode (`enable_thinking`), enabled via the SiliconFlow API to improve translation quality.
*   New: Configurable Thinking Budget in the admin (128-32768 tokens, default 4096).
*   Improved: `max_tokens` raised to 60000 to ensure enough translation space in thinking mode.
*   Improved: Timeout automatically raised from 180s to 300s when thinking mode is enabled.

### 1.5.1
*   Fix: Fixed a PHP Warning caused by an unescaped `%1$s` in DeepSeekApiHelper.
*   Improved: README documentation update.

### 1.5.0
*   New: OpenAI (ChatGPT) translation engine support.
*   New: OpenAI Endpoint option (compatible with different models and proxies).

### 1.4.0
*   New: Google Gemini API support.
*   New: Support for choosing different Gemini models (Flash, Pro).
*   Improved: Better translation prompts that automatically strip explanatory text AI may add.

## 🙏 Acknowledgments

This plugin is an extension and refinement of the open-source project **Hollisho Integration with DeepSeek for TranslatePress**.

*   Original author: hollisho
*   Project URL: [Hollisho GitHub](https://github.com/hollisho)

---
Developed by [Rankscale AI](https://rankscaleai.com/)
