# Rankscale AI For TranslatePress

**简体中文** · [English](README.en.md) · [Français](README.fr.md) · [Español](README.es.md) · [Deutsch](README.de.md)

Rankscale AI For TranslatePress 是一个强大的 TranslatePress 扩展插件，为您的 WordPress 网站提供多个 AI 翻译引擎支持。通过优化的翻译提示词，有效避免了翻译中出现的异常字符和不必要的解释性文字。

## ✨ 主要特性

*   **多引擎支持**：
    *   **DeepSeek API**：通过 SiliconFlow (硅基流动)、DeepSeek 官方或自定义端点提供高质量翻译（支持 DeepSeek-V3.2，支持深度思考模式）。
    *   **Google Gemini API**：支持 Gemini 2.5 Flash / Pro、Gemini 3 Flash 等最新模型，拥有充足的免费额度。
    *   **OpenAI (ChatGPT) API**：支持 GPT-5.4、GPT-5.2 等模型，兼容 Responses API 和 Chat Completions。
*   **深度思考模式**：DeepSeek 引擎支持开启 `enable_thinking`，模型在翻译前进行推理思考，提升翻译质量。可自定义 Thinking Budget（128-32768 tokens）。
*   **自动重试**：遇到 API 限流（429）或服务器临时故障（5xx）时，自动使用指数退避策略重试，确保翻译任务顺利完成。
*   **智能优化**：
    *   优化的 System Message 提示词架构，翻译质量更高、规则遵守更好。
    *   自动清理 AI 可能添加的解释性文字（支持英/中/日/韩/法/德/西等多语言模式）。
    *   HTML 注入防护：自动阻止 AI 在纯文本翻译中引入 HTML 标签。
*   **灵活配置**：
    *   DeepSeek：支持 SiliconFlow / DeepSeek 官方 / 自定义 API 端点切换。
    *   Gemini：支持选择 Flash（快速）、Flash-Lite（最便宜）、Pro（最高质量）、Gemini 3（前沿）模型。
    *   OpenAI：支持 Responses API（推荐）和 Chat Completions（兼容）双端点。
    *   API 密钥以密码形式显示，安全性更高。
*   **多站点支持**：兼容 WordPress Multisite，支持网络级别激活的 TranslatePress。
*   **高效架构**：基于抽象基类设计，代码简洁、可维护性强，新增翻译引擎只需极少代码。

## 📋 环境要求

*   WordPress 6.0 或更高版本
*   PHP 7.2 或更高版本
*   已安装并启用 [TranslatePress](https://wordpress.org/plugins/translatepress-multilingual/) 插件

## 🚀 安装与配置

1.  **安装插件**：
    *   下载插件 zip 包。
    *   在 WordPress 后台，转到 **插件 > 安装插件 > 上传插件**，上传 zip 包并安装。
    *   启用插件。

2.  **配置翻译引擎**：
    *   转到 **设置 > TranslatePress**。
    *   点击 **自动翻译 (Automatic Translation)** 标签页。
    *   在 **Translation Engine** 中选择您想要的 AI 引擎：
        *   **DeepSeek**：适合中文及多语言互译，支持深度思考模式。
        *   **Google Gemini**：支持更多语言，速度快，免费额度高。
        *   **OpenAI**：使用 GPT-5.4/5.2 等模型进行高质量翻译。
    *   输入相应的 **API Key**。
    *   （可选）DeepSeek：选择 API 端点（SiliconFlow / DeepSeek 官方 / 自定义 URL），开启深度思考模式并调整 Thinking Budget。
    *   （可选）Gemini：选择具体的模型版本。
    *   （可选）OpenAI：选择端点类型和模型。

3.  **开始翻译**：
    *   保存设置后，TranslatePress 将自动使用您配置的 AI 引擎翻译网站内容。

## ❓ 常见问题 (FAQ)

### 如何获取 DeepSeek API 密钥？
1.  访问 [SiliconFlow (硅基流动) 官网](https://cloud.siliconflow.cn/)。
2.  注册或登录账号。
3.  在 API 密钥管理页面创建一个新的 API Key。
4.  本插件默认使用 SiliconFlow 提供的 DeepSeek-V3.2 模型，速度快且成本低。
5.  也可在设置中切换到 DeepSeek 官方端点或自定义 URL。

### 如何获取 Google Gemini API 密钥？
1.  访问 [Google AI Studio](https://aistudio.google.com/app/apikey)。
2.  使用 Google 账号登录。
3.  点击 "Create API Key"。
4.  复制生成的 API 密钥（Gemini API 目前对大多数用户提供充足的免费额度）。

### 如何获取 OpenAI API 密钥？
1.  访问 [OpenAI Platform](https://platform.openai.com/api-keys)。
2.  注册或登录账号。
3.  创建一个新的 API Key。

### 翻译有字数限制吗？
*   **DeepSeek**：按 token 计费，开启深度思考模式后 token 消耗会增加（包含推理 tokens），具体请参考 SiliconFlow 官方文档。
*   **Gemini**：提供免费层级，但在高并发或大量使用下可能会有速率限制。
*   **OpenAI**：按 token 计费，不同模型价格不同。

### 遇到 API 限流怎么办？
插件内置了自动重试机制。当 API 返回 429（限流）或 5xx（服务器错误）时，会自动等待后重试（最多重试 2 次），无需手动干预。

## 📅 更新日志

### 2.0.0
*   **架构重构**：引入 AbstractTranslationEngine、AbstractApiHelper、AbstractRegisterEngine 三个抽象基类，消除约 1000 行重复代码。
*   **新增：自动重试**：遇到 429/5xx 错误时自动指数退避重试（最多 2 次），尊重 Retry-After 响应头。
*   **新增：多站点支持**：兼容 WordPress Multisite 网络激活的 TranslatePress。
*   **新增：DeepSeek 端点配置**：支持 SiliconFlow、DeepSeek 官方、自定义 URL 三种端点选择。
*   **改进：System Message 架构**：DeepSeek 和 OpenAI 使用独立的 system/user 消息分离，Gemini 使用 systemInstruction 字段。
*   **改进：Gemini 模型更新**：替换为 Gemini 2.5 Flash/Pro、Gemini 3 Flash 等最新模型。
*   **改进：OpenAI 模型更新**：新增 GPT-5.4 支持。
*   **改进：Gemini Prompt 增强**：升级为与 DeepSeek/OpenAI 相同的详细 8 条规则集。
*   **改进：翻译解析修复**：修复 `parseTranslatedItems()` 中 `array_values()` 导致的翻译错位问题。
*   **改进：Gemini 参数修复**：chunk 大小从 100 调整为 20，maxOutputTokens 从 8192 提升到 65536。
*   **改进：`cleanupTranslatedText`**：扩展多语言清理模式，覆盖英/中/日/韩/法/德/西等语言。
*   **安全：HTML 注入防护**：所有三个引擎统一添加 `sanitize_injected_markup()` 保护。
*   **安全：API Key 密码显示**：所有 API Key 输入框改为 `type="password"`。
*   **修复：版本号同步**：插件头部和 Common::PLUGIN_VERSION 保持一致。
*   **修复：DeepSeek 语言补全**：添加 Hindi、Thai、Vietnamese 支持。
*   **修复：错误日志**：所有引擎添加非 200 响应和 WP_Error 的详细错误日志。
*   **修复：API Key 验证增强**：除空值检查外增加最小长度验证。
*   **修复：DeepSeek 语言映射**：补全缺失的 pt_BR/pt_PT 映射。

### 1.6.0
*   新增：DeepSeek 深度思考模式（`enable_thinking`），通过硅基流动 API 开启，提升翻译质量。
*   新增：后台可配置 Thinking Budget（128-32768 tokens，默认 4096）。
*   优化：`max_tokens` 提升至 60000，确保思考模式下翻译空间充足。
*   优化：开启思考模式时超时时间自动从 180s 提升至 300s。

### 1.5.1
*   修复：DeepSeekApiHelper 中 `%1$s` 未转义导致 PHP Warning 的问题。
*   优化：README 文档更新。

### 1.5.0
*   新增：OpenAI (ChatGPT) 翻译引擎支持。
*   新增：OpenAI Endpoint 选项（兼容不同模型与代理）。

### 1.4.0
*   新增：Google Gemini API 支持。
*   新增：支持选择不同的 Gemini 模型（Flash, Pro）。
*   优化：改进翻译 prompt，自动清理 AI 可能添加的解释性文字。

## 🙏 致谢

本插件基于开源项目 **Hollisho Integration with DeepSeek for TranslatePress** 进行功能扩展与优化。

*   原始作者：hollisho
*   项目地址：[Hollisho GitHub](https://github.com/hollisho)

---
Developed by [Rankscale AI](https://rankscaleai.com/)
