# Rankscale AI For TranslatePress

[简体中文](README.md) · [English](README.en.md) · [Français](README.fr.md) · [Español](README.es.md) · **Deutsch**

Rankscale AI For TranslatePress ist eine leistungsstarke TranslatePress-Erweiterung, die deiner WordPress-Website Unterstützung für mehrere KI-Übersetzungs-Engines hinzufügt. Dank optimierter Übersetzungs-Prompts vermeidet sie zuverlässig Zeichensalat und unnötige erklärende Texte in Übersetzungen.

## ✨ Hauptfunktionen

*   **Unterstützung mehrerer Engines**:
    *   **DeepSeek API**: hochwertige Übersetzung über SiliconFlow, den offiziellen DeepSeek-Endpunkt oder einen benutzerdefinierten Endpunkt (unterstützt DeepSeek-V3.2 und den Deep-Thinking-Modus).
    *   **Google Gemini API**: unterstützt die neuesten Modelle wie Gemini 2.5 Flash / Pro und Gemini 3 Flash mit einem großzügigen kostenlosen Kontingent.
    *   **OpenAI (ChatGPT) API**: unterstützt Modelle wie GPT-5.4 und GPT-5.2, kompatibel mit der Responses API und Chat Completions.
*   **Deep-Thinking-Modus**: Die DeepSeek-Engine unterstützt `enable_thinking`, sodass das Modell vor der Übersetzung schlussfolgert, um die Qualität zu verbessern. Das Thinking Budget ist konfigurierbar (128-32768 Tokens).
*   **Automatischer Wiederholungsversuch**: Bei API-Ratenbegrenzung (429) oder vorübergehenden Serverfehlern (5xx) wird automatisch mit einer exponentiellen Backoff-Strategie erneut versucht, damit Übersetzungsaufgaben abgeschlossen werden.
*   **Intelligente Optimierung**:
    *   Eine optimierte System-Message-Prompt-Architektur für höhere Übersetzungsqualität und bessere Regeltreue.
    *   Entfernt automatisch erklärende Texte, die die KI hinzufügen könnte (unterstützt mehrsprachige Muster für EN/ZH/JA/KO/FR/DE/ES und mehr).
    *   HTML-Injection-Schutz: verhindert automatisch, dass die KI HTML-Tags in reine Textübersetzungen einfügt.
*   **Flexible Konfiguration**:
    *   DeepSeek: Wechsel zwischen den Endpunkten SiliconFlow / offizielles DeepSeek / benutzerdefinierte API.
    *   Gemini: Wahl zwischen den Modellen Flash (schnell), Flash-Lite (am günstigsten), Pro (höchste Qualität) oder Gemini 3 (neueste Generation).
    *   OpenAI: unterstützt sowohl den Responses-API-Endpunkt (empfohlen) als auch den Chat-Completions-Endpunkt (Kompatibilität).
    *   API-Schlüssel werden aus Sicherheitsgründen als Passwort angezeigt.
*   **Multisite-Unterstützung**: kompatibel mit WordPress Multisite, einschließlich netzwerkweit aktiviertem TranslatePress.
*   **Effiziente Architektur**: basiert auf abstrakten Basisklassen für sauberen, wartbaren Code; das Hinzufügen einer neuen Übersetzungs-Engine erfordert nur sehr wenig Code.

## 📋 Voraussetzungen

*   WordPress 6.0 oder höher
*   PHP 7.2 oder höher
*   Das Plugin [TranslatePress](https://wordpress.org/plugins/translatepress-multilingual/) installiert und aktiviert

## 🚀 Installation und Konfiguration

1.  **Plugin installieren**:
    *   Lade das ZIP-Paket des Plugins herunter.
    *   Gehe im WordPress-Backend zu **Plugins > Installieren > Plugin hochladen**, lade das ZIP-Paket hoch und installiere es.
    *   Aktiviere das Plugin.

2.  **Übersetzungs-Engine konfigurieren**:
    *   Gehe zu **Einstellungen > TranslatePress**.
    *   Klicke auf den Tab **Automatische Übersetzung (Automatic Translation)**.
    *   Wähle unter **Translation Engine** die gewünschte KI-Engine:
        *   **DeepSeek**: ideal für Chinesisch und mehrsprachige Übersetzung, unterstützt den Deep-Thinking-Modus.
        *   **Google Gemini**: unterstützt mehr Sprachen, ist schnell und bietet ein hohes kostenloses Kontingent.
        *   **OpenAI**: verwendet Modelle wie GPT-5.4/5.2 für hochwertige Übersetzungen.
    *   Gib den entsprechenden **API-Schlüssel** ein.
    *   (Optional) DeepSeek: wähle den API-Endpunkt (SiliconFlow / offizielles DeepSeek / benutzerdefinierte URL), aktiviere den Deep-Thinking-Modus und passe das Thinking Budget an.
    *   (Optional) Gemini: wähle eine bestimmte Modellversion.
    *   (Optional) OpenAI: wähle den Endpunkttyp und das Modell.

3.  **Mit dem Übersetzen beginnen**:
    *   Nach dem Speichern deiner Einstellungen verwendet TranslatePress automatisch die von dir konfigurierte KI-Engine, um die Inhalte deiner Website zu übersetzen.

## ❓ Häufig gestellte Fragen (FAQ)

### Wie erhalte ich einen DeepSeek-API-Schlüssel?
1.  Besuche die [SiliconFlow-Website](https://cloud.siliconflow.cn/).
2.  Registriere dich oder melde dich in deinem Konto an.
3.  Erstelle auf der Seite zur API-Schlüsselverwaltung einen neuen API-Schlüssel.
4.  Standardmäßig verwendet dieses Plugin das von SiliconFlow bereitgestellte Modell DeepSeek-V3.2, das schnell und kostengünstig ist.
5.  In den Einstellungen kannst du auch zum offiziellen DeepSeek-Endpunkt oder zu einer benutzerdefinierten URL wechseln.

### Wie erhalte ich einen Google-Gemini-API-Schlüssel?
1.  Besuche [Google AI Studio](https://aistudio.google.com/app/apikey).
2.  Melde dich mit deinem Google-Konto an.
3.  Klicke auf „Create API Key".
4.  Kopiere den generierten API-Schlüssel (die Gemini-API bietet derzeit für die meisten Nutzer ein großzügiges kostenloses Kontingent).

### Wie erhalte ich einen OpenAI-API-Schlüssel?
1.  Besuche die [OpenAI-Plattform](https://platform.openai.com/api-keys).
2.  Registriere dich oder melde dich in deinem Konto an.
3.  Erstelle einen neuen API-Schlüssel.

### Gibt es eine Zeichenbegrenzung für die Übersetzung?
*   **DeepSeek**: Abrechnung pro Token; das Aktivieren des Deep-Thinking-Modus erhöht den Tokenverbrauch (einschließlich Reasoning-Tokens). Einzelheiten findest du in der offiziellen SiliconFlow-Dokumentation.
*   **Gemini**: bietet ein kostenloses Kontingent, es können jedoch bei hoher Parallelität oder starker Nutzung Ratenbegrenzungen gelten.
*   **OpenAI**: Abrechnung pro Token; die Preise variieren je nach Modell.

### Was tun bei API-Ratenbegrenzungen?
Das Plugin verfügt über einen integrierten automatischen Wiederholungsmechanismus. Wenn die API 429 (Ratenbegrenzung) oder 5xx (Serverfehler) zurückgibt, wartet es und versucht es automatisch erneut (bis zu 2 Mal), ohne dass ein manuelles Eingreifen erforderlich ist.

## 📅 Änderungsprotokoll

### 2.0.0
*   **Architektur-Refactoring**: Einführung von drei abstrakten Basisklassen - AbstractTranslationEngine, AbstractApiHelper und AbstractRegisterEngine - wodurch etwa 1.000 Zeilen doppelter Code entfernt wurden.
*   **Neu: automatischer Wiederholungsversuch**: automatischer Wiederholungsversuch mit exponentiellem Backoff bei 429/5xx-Fehlern (bis zu 2 Mal), unter Beachtung des Retry-After-Antwort-Headers.
*   **Neu: Multisite-Unterstützung**: kompatibel mit netzwerkweit aktiviertem TranslatePress in WordPress Multisite.
*   **Neu: DeepSeek-Endpunktkonfiguration**: Auswahl zwischen den Endpunkten SiliconFlow, offizielles DeepSeek und benutzerdefinierte URL.
*   **Verbessert: System-Message-Architektur**: DeepSeek und OpenAI verwenden getrennte system/user-Nachrichten; Gemini verwendet das Feld systemInstruction.
*   **Verbessert: Aktualisierung der Gemini-Modelle**: ersetzt durch die neuesten Modelle wie Gemini 2.5 Flash/Pro und Gemini 3 Flash.
*   **Verbessert: Aktualisierung der OpenAI-Modelle**: Unterstützung für GPT-5.4 hinzugefügt.
*   **Verbessert: Verbesserung des Gemini-Prompts**: aktualisiert auf denselben detaillierten Satz von 8 Regeln wie DeepSeek/OpenAI.
*   **Verbessert: Korrektur der Übersetzungsanalyse**: Behebung des Übersetzungsversatzes, der durch `array_values()` in `parseTranslatedItems()` verursacht wurde.
*   **Verbessert: Korrektur der Gemini-Parameter**: Chunk-Größe von 100 auf 20 angepasst und maxOutputTokens von 8192 auf 65536 erhöht.
*   **Verbessert: `cleanupTranslatedText`**: erweiterte mehrsprachige Bereinigungsmuster für EN/ZH/JA/KO/FR/DE/ES und mehr.
*   **Sicherheit: HTML-Injection-Schutz**: alle drei Engines fügen nun einheitlich den `sanitize_injected_markup()`-Schutz hinzu.
*   **Sicherheit: maskierte Anzeige von API-Schlüsseln**: alle Eingabefelder für API-Schlüssel auf `type="password"` umgestellt.
*   **Fix: Synchronisierung der Versionsnummer**: Der Plugin-Header und Common::PLUGIN_VERSION bleiben konsistent.
*   **Fix: Vervollständigung der DeepSeek-Sprachen**: Unterstützung für Hindi, Thailändisch und Vietnamesisch hinzugefügt.
*   **Fix: Fehlerprotokollierung**: alle Engines fügen detaillierte Fehlerprotokolle für Nicht-200-Antworten und WP_Error hinzu.
*   **Fix: verbesserte API-Schlüssel-Validierung**: zusätzlich zur Leerwertprüfung eine Mindestlängenprüfung hinzugefügt.
*   **Fix: DeepSeek-Sprachzuordnung**: die fehlenden pt_BR/pt_PT-Zuordnungen ergänzt.

### 1.6.0
*   Neu: DeepSeek-Deep-Thinking-Modus (`enable_thinking`), aktiviert über die SiliconFlow-API zur Verbesserung der Übersetzungsqualität.
*   Neu: konfigurierbares Thinking Budget im Backend (128-32768 Tokens, Standard 4096).
*   Verbessert: `max_tokens` auf 60000 erhöht, um im Thinking-Modus ausreichend Übersetzungsspielraum zu gewährleisten.
*   Verbessert: Timeout wird bei aktiviertem Thinking-Modus automatisch von 180s auf 300s erhöht.

### 1.5.1
*   Fix: Behebung eines PHP Warning, verursacht durch ein nicht maskiertes `%1$s` in DeepSeekApiHelper.
*   Verbessert: Aktualisierung der README-Dokumentation.

### 1.5.0
*   Neu: Unterstützung der OpenAI-(ChatGPT)-Übersetzungs-Engine.
*   Neu: OpenAI-Endpoint-Option (kompatibel mit verschiedenen Modellen und Proxys).

### 1.4.0
*   Neu: Unterstützung der Google-Gemini-API.
*   Neu: Möglichkeit, verschiedene Gemini-Modelle zu wählen (Flash, Pro).
*   Verbessert: bessere Übersetzungs-Prompts, die erklärende Texte, die die KI hinzufügen könnte, automatisch entfernen.

## 🙏 Danksagung

Dieses Plugin ist eine Erweiterung und Verfeinerung des Open-Source-Projekts **Hollisho Integration with DeepSeek for TranslatePress**.

*   Ursprünglicher Autor: hollisho
*   Projekt-URL: [Hollisho GitHub](https://github.com/hollisho)

---
Entwickelt von [Rankscale AI](https://rankscaleai.com/)
