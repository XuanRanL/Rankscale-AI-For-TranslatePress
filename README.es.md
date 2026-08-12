# Rankscale AI For TranslatePress

[简体中文](README.md) · [English](README.en.md) · [Français](README.fr.md) · **Español** · [Deutsch](README.de.md)

Rankscale AI For TranslatePress es una potente extensión de TranslatePress que añade compatibilidad con varios motores de traducción con IA a tu sitio WordPress. Gracias a indicaciones de traducción optimizadas, evita eficazmente los caracteres extraños y los textos explicativos innecesarios en las traducciones.

## ✨ Características principales

*   **Compatibilidad con múltiples motores**:
    *   **API de DeepSeek**: traducción de alta calidad a través de SiliconFlow, el endpoint oficial de DeepSeek o un endpoint personalizado (SiliconFlow usa por defecto DeepSeek-V3.2, el endpoint oficial deepseek-v4-flash; modo de pensamiento profundo compatible).
    *   **API de Google Gemini**: compatible con Gemini 3.6 Flash, Gemini 3.5 Flash-Lite y la serie Gemini 2.5, con un generoso nivel gratuito.
    *   **API de OpenAI (ChatGPT)**: compatible con modelos como GPT-5.4 y GPT-5.2, compatible con la Responses API y Chat Completions.
*   **Modo de pensamiento profundo**: el motor DeepSeek admite `enable_thinking`, lo que permite que el modelo razone antes de traducir para mejorar la calidad. El Thinking Budget es configurable (128-32768 tokens).
*   **Reintento automático**: ante la limitación de tasa de la API (429) o fallos temporales del servidor (5xx), reintenta automáticamente con una estrategia de retroceso exponencial para garantizar que las tareas de traducción se completen.
*   **Optimización inteligente**:
    *   Una arquitectura de indicaciones System Message optimizada para una mayor calidad de traducción y un mejor cumplimiento de las reglas.
    *   Elimina automáticamente el texto explicativo que la IA pueda añadir (compatible con patrones multilingües EN/ZH/JA/KO/FR/DE/ES y más).
    *   Protección contra inyección de HTML: evita automáticamente que la IA introduzca etiquetas HTML en las traducciones de texto plano.
*   **Configuración flexible**:
    *   DeepSeek: alterna entre los endpoints SiliconFlow / DeepSeek oficial / API personalizada.
    *   Gemini: elige Gemini 3.6 Flash (recomendado), 3.5 Flash-Lite (el más económico) o la serie 2.5 (heredada).
    *   **ID de modelo personalizado**: los tres motores aceptan un ID de modelo introducido manualmente — usa modelos nuevos o renombrados sin esperar una actualización del plugin.
    *   OpenAI: compatible con los endpoints Responses API (recomendado) y Chat Completions (compatibilidad).
    *   Las claves API se muestran como contraseñas para mayor seguridad.
*   **Compatibilidad con Multisitio**: compatible con WordPress Multisite, incluido TranslatePress activado a nivel de red.
*   **Arquitectura eficiente**: basada en clases base abstractas para un código limpio y fácil de mantener; añadir un nuevo motor de traducción requiere muy poco código.

## 📋 Requisitos

*   WordPress 6.0 o superior
*   PHP 7.2 o superior
*   La extensión [TranslatePress](https://wordpress.org/plugins/translatepress-multilingual/) instalada y activada

## 🚀 Instalación y configuración

1.  **Instalar la extensión**:
    *   Descarga el paquete zip de la extensión.
    *   En el administrador de WordPress, ve a **Plugins > Añadir nuevo > Subir plugin**, sube el paquete zip e instálalo.
    *   Activa la extensión.

2.  **Configurar un motor de traducción**:
    *   Ve a **Ajustes > TranslatePress**.
    *   Haz clic en la pestaña **Traducción automática (Automatic Translation)**.
    *   En **Translation Engine**, selecciona el motor de IA que quieras:
        *   **DeepSeek**: ideal para el chino y la traducción multilingüe, compatible con el modo de pensamiento profundo.
        *   **Google Gemini**: compatible con más idiomas, rápido y con un alto nivel gratuito.
        *   **OpenAI**: utiliza modelos como GPT-5.4/5.2 para una traducción de alta calidad.
    *   Introduce la **clave API** correspondiente.
    *   (Opcional) DeepSeek: elige el endpoint de la API (SiliconFlow / DeepSeek oficial / URL personalizada), activa el modo de pensamiento profundo y ajusta el Thinking Budget.
    *   (Opcional) Gemini: elige una versión de modelo específica.
    *   (Opcional) OpenAI: elige el tipo de endpoint y el modelo.

3.  **Empezar a traducir**:
    *   Después de guardar los ajustes, TranslatePress utilizará automáticamente el motor de IA que hayas configurado para traducir el contenido de tu sitio.

## ❓ Preguntas frecuentes (FAQ)

### ¿Cómo obtengo una clave API de DeepSeek?
1.  Visita el [sitio web de SiliconFlow](https://cloud.siliconflow.cn/).
2.  Regístrate o inicia sesión en tu cuenta.
3.  Crea una nueva clave API en la página de gestión de claves API.
4.  De forma predeterminada, esta extensión utiliza el modelo DeepSeek-V3.2 proporcionado por SiliconFlow, rápido y de bajo coste.
5.  También puedes cambiar al endpoint oficial de DeepSeek o a una URL personalizada en los ajustes.

### ¿Cómo obtengo una clave API de Google Gemini?
1.  Visita [Google AI Studio](https://aistudio.google.com/app/apikey).
2.  Inicia sesión con tu cuenta de Google.
3.  Haz clic en "Create API Key".
4.  Copia la clave API generada (la API de Gemini actualmente ofrece un generoso nivel gratuito para la mayoría de los usuarios).

### ¿Cómo obtengo una clave API de OpenAI?
1.  Visita la [plataforma de OpenAI](https://platform.openai.com/api-keys).
2.  Regístrate o inicia sesión en tu cuenta.
3.  Crea una nueva clave API.

### ¿Hay un límite de caracteres para la traducción?
*   **DeepSeek**: se factura por token; activar el modo de pensamiento profundo aumenta el consumo de tokens (incluidos los tokens de razonamiento). Consulta la documentación oficial de SiliconFlow para más detalles.
*   **Gemini**: ofrece un nivel gratuito, pero pueden aplicarse límites de tasa con alta concurrencia o uso intensivo.
*   **OpenAI**: se factura por token; los precios varían según el modelo.

### ¿Qué hago si alcanzo los límites de tasa de la API?
La extensión incluye un mecanismo de reintento automático integrado. Cuando la API devuelve 429 (límite de tasa) o 5xx (error del servidor), espera y reintenta automáticamente (hasta 2 veces), sin necesidad de intervención manual.

## 📅 Registro de cambios

### 2.1.0
*   **Nuevo: ID de modelo personalizado** para los tres motores (DeepSeek / Gemini / OpenAI). Los cambios de nombre de modelos por parte de los proveedores (como la retirada de deepseek-chat) ya no rompen el plugin — la solución de raíz al problema de las listas de modelos obsoletas.
*   **Corrección: endpoint oficial de DeepSeek**: `deepseek-chat` fue retirado por DeepSeek el 24/07/2026; el endpoint oficial ahora usa `deepseek-v4-flash` por defecto. `enable_thinking`/`thinking_budget` son específicos de SiliconFlow y ya no se envían al endpoint oficial.
*   **Corrección: compatibilidad de parámetros GPT-5 (OpenAI)**: los modelos de razonamiento (GPT-5/serie o) ya no reciben `temperature` (solo aceptan el valor por defecto 1, de lo contrario devuelven 400); Chat Completions ahora usa `max_completion_tokens` en lugar del rechazado `max_tokens`; límites de tokens de salida por familia de modelos (gpt-4o-mini 16384 / gpt-4.1 32768).
*   **Corrección: obsolescencia de parámetros de muestreo de Gemini**: se eliminaron `temperature`/`topP`/`topK` según las indicaciones de Google (ignorados desde Gemini 3.5+, devolverán 400 en generaciones futuras).
*   **Mejora: lista de modelos de Gemini**: el modelo por defecto ahora es `gemini-3.6-flash`, se añadió `gemini-3.5-flash-lite`; los ID de modelos retirados guardados en ajustes antiguos se sustituyen automáticamente.
*   **Corrección: orden de registro del hook de activación**: la comprobación de dependencia de TranslatePress ahora se ejecuta aunque TranslatePress esté inactivo.
*   **Nuevo: filtro `trp_ai_max_output_tokens`** para personalizar los límites de tokens de salida por motor/modelo.

### 2.0.0
*   **Refactorización de la arquitectura**: se introdujeron tres clases base abstractas - AbstractTranslationEngine, AbstractApiHelper y AbstractRegisterEngine - eliminando unas 1000 líneas de código duplicado.
*   **Nuevo: reintento automático**: reintento automático con retroceso exponencial ante errores 429/5xx (hasta 2 veces), respetando la cabecera de respuesta Retry-After.
*   **Nuevo: compatibilidad con Multisitio**: compatible con TranslatePress activado a nivel de red en WordPress Multisite.
*   **Nuevo: configuración del endpoint de DeepSeek**: elige entre los endpoints SiliconFlow, DeepSeek oficial y URL personalizada.
*   **Mejorado: arquitectura System Message**: DeepSeek y OpenAI utilizan mensajes system/user separados; Gemini utiliza el campo systemInstruction.
*   **Mejorado: actualización de modelos Gemini**: reemplazados por los últimos modelos como Gemini 2.5 Flash/Pro y Gemini 3 Flash.
*   **Mejorado: actualización de modelos OpenAI**: se añadió compatibilidad con GPT-5.4.
*   **Mejorado: mejora de la indicación de Gemini**: actualizada al mismo conjunto detallado de 8 reglas que DeepSeek/OpenAI.
*   **Mejorado: corrección del análisis de traducción**: se corrigió el desajuste de traducción causado por `array_values()` en `parseTranslatedItems()`.
*   **Mejorado: corrección de parámetros de Gemini**: tamaño de chunk ajustado de 100 a 20, y maxOutputTokens aumentado de 8192 a 65536.
*   **Mejorado: `cleanupTranslatedText`**: patrones de limpieza multilingües ampliados que cubren EN/ZH/JA/KO/FR/DE/ES y más.
*   **Seguridad: protección contra inyección de HTML**: los tres motores añaden ahora de forma uniforme la protección `sanitize_injected_markup()`.
*   **Seguridad: visualización enmascarada de claves API**: todos los campos de entrada de clave API cambiados a `type="password"`.
*   **Corrección: sincronización del número de versión**: la cabecera de la extensión y Common::PLUGIN_VERSION se mantienen coherentes.
*   **Corrección: completado de idiomas de DeepSeek**: se añadió compatibilidad con hindi, tailandés y vietnamita.
*   **Corrección: registro de errores**: todos los motores añaden registros de errores detallados para respuestas distintas de 200 y WP_Error.
*   **Corrección: validación reforzada de claves API**: se añadió una comprobación de longitud mínima además de la comprobación de valor vacío.
*   **Corrección: mapeo de idiomas de DeepSeek**: se completaron los mapeos pt_BR/pt_PT que faltaban.

### 1.6.0
*   Nuevo: modo de pensamiento profundo de DeepSeek (`enable_thinking`), activado a través de la API de SiliconFlow para mejorar la calidad de traducción.
*   Nuevo: Thinking Budget configurable en el administrador (128-32768 tokens, 4096 por defecto).
*   Mejorado: `max_tokens` elevado a 60000 para garantizar suficiente espacio de traducción en el modo de pensamiento.
*   Mejorado: tiempo de espera aumentado automáticamente de 180s a 300s cuando se activa el modo de pensamiento.

### 1.5.1
*   Corrección: se corrigió un PHP Warning causado por un `%1$s` sin escapar en DeepSeekApiHelper.
*   Mejorado: actualización de la documentación README.

### 1.5.0
*   Nuevo: compatibilidad con el motor de traducción OpenAI (ChatGPT).
*   Nuevo: opción OpenAI Endpoint (compatible con diferentes modelos y proxies).

### 1.4.0
*   Nuevo: compatibilidad con la API de Google Gemini.
*   Nuevo: posibilidad de elegir diferentes modelos de Gemini (Flash, Pro).
*   Mejorado: mejores indicaciones de traducción que eliminan automáticamente el texto explicativo que la IA pueda añadir.

## 🙏 Agradecimientos

Esta extensión es una ampliación y mejora del proyecto de código abierto **Hollisho Integration with DeepSeek for TranslatePress**.

*   Autor original: hollisho
*   URL del proyecto: [Hollisho GitHub](https://github.com/hollisho)

---
Desarrollado por [Rankscale AI](https://rankscaleai.com/)
