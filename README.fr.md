# Rankscale AI For TranslatePress

[简体中文](README.md) · [English](README.en.md) · **Français** · [Español](README.es.md) · [Deutsch](README.de.md)

Rankscale AI For TranslatePress est une puissante extension de TranslatePress qui ajoute la prise en charge de plusieurs moteurs de traduction IA à votre site WordPress. Grâce à des invites de traduction optimisées, elle évite efficacement les caractères parasites et les textes explicatifs superflus dans les traductions.

## ✨ Principales fonctionnalités

*   **Prise en charge multi-moteurs** :
    *   **API DeepSeek** : traduction de haute qualité via SiliconFlow, le point de terminaison officiel de DeepSeek ou un point de terminaison personnalisé (prend en charge DeepSeek-V3.2 et le mode de réflexion approfondie).
    *   **API Google Gemini** : prend en charge les derniers modèles tels que Gemini 2.5 Flash / Pro et Gemini 3 Flash, avec un généreux niveau gratuit.
    *   **API OpenAI (ChatGPT)** : prend en charge des modèles tels que GPT-5.4 et GPT-5.2, compatibles avec l'API Responses et Chat Completions.
*   **Mode de réflexion approfondie** : le moteur DeepSeek prend en charge `enable_thinking`, permettant au modèle de raisonner avant de traduire pour améliorer la qualité. Le Thinking Budget est configurable (128-32768 tokens).
*   **Nouvelle tentative automatique** : en cas de limitation de débit de l'API (429) ou de défaillances temporaires du serveur (5xx), l'extension réessaie automatiquement selon une stratégie de repli exponentiel afin de garantir l'achèvement des tâches de traduction.
*   **Optimisation intelligente** :
    *   Une architecture d'invite System Message optimisée pour une meilleure qualité de traduction et un meilleur respect des règles.
    *   Suppression automatique du texte explicatif que l'IA peut ajouter (prend en charge les schémas multilingues EN/ZH/JA/KO/FR/DE/ES et plus).
    *   Protection contre l'injection HTML : empêche automatiquement l'IA d'introduire des balises HTML dans les traductions en texte brut.
*   **Configuration flexible** :
    *   DeepSeek : basculez entre les points de terminaison SiliconFlow / DeepSeek officiel / API personnalisée.
    *   Gemini : choisissez les modèles Flash (rapide), Flash-Lite (le moins cher), Pro (meilleure qualité) ou Gemini 3 (à la pointe).
    *   OpenAI : prend en charge les points de terminaison API Responses (recommandé) et Chat Completions (compatibilité).
    *   Les clés API sont affichées sous forme de mots de passe pour une meilleure sécurité.
*   **Prise en charge multisite** : compatible avec WordPress Multisite, y compris TranslatePress activé au niveau du réseau.
*   **Architecture efficace** : basée sur des classes de base abstraites pour un code propre et maintenable ; l'ajout d'un nouveau moteur de traduction ne nécessite que très peu de code.

## 📋 Prérequis

*   WordPress 6.0 ou version ultérieure
*   PHP 7.2 ou version ultérieure
*   L'extension [TranslatePress](https://wordpress.org/plugins/translatepress-multilingual/) installée et activée

## 🚀 Installation et configuration

1.  **Installer l'extension** :
    *   Téléchargez le paquet zip de l'extension.
    *   Dans l'administration WordPress, allez dans **Extensions > Ajouter > Téléverser une extension**, téléversez le paquet zip et installez-le.
    *   Activez l'extension.

2.  **Configurer un moteur de traduction** :
    *   Allez dans **Réglages > TranslatePress**.
    *   Cliquez sur l'onglet **Traduction automatique (Automatic Translation)**.
    *   Sous **Translation Engine**, sélectionnez le moteur IA souhaité :
        *   **DeepSeek** : idéal pour le chinois et la traduction multilingue, prend en charge le mode de réflexion approfondie.
        *   **Google Gemini** : prend en charge davantage de langues, rapide, avec un niveau gratuit élevé.
        *   **OpenAI** : utilise des modèles tels que GPT-5.4/5.2 pour une traduction de haute qualité.
    *   Saisissez la **clé API** correspondante.
    *   (Facultatif) DeepSeek : choisissez le point de terminaison de l'API (SiliconFlow / DeepSeek officiel / URL personnalisée), activez le mode de réflexion approfondie et ajustez le Thinking Budget.
    *   (Facultatif) Gemini : choisissez une version de modèle spécifique.
    *   (Facultatif) OpenAI : choisissez le type de point de terminaison et le modèle.

3.  **Commencer à traduire** :
    *   Après avoir enregistré vos réglages, TranslatePress utilisera automatiquement le moteur IA que vous avez configuré pour traduire le contenu de votre site.

## ❓ FAQ

### Comment obtenir une clé API DeepSeek ?
1.  Rendez-vous sur le [site de SiliconFlow](https://cloud.siliconflow.cn/).
2.  Créez un compte ou connectez-vous.
3.  Créez une nouvelle clé API sur la page de gestion des clés API.
4.  Par défaut, cette extension utilise le modèle DeepSeek-V3.2 fourni par SiliconFlow, rapide et peu coûteux.
5.  Vous pouvez également basculer vers le point de terminaison officiel de DeepSeek ou une URL personnalisée dans les réglages.

### Comment obtenir une clé API Google Gemini ?
1.  Rendez-vous sur [Google AI Studio](https://aistudio.google.com/app/apikey).
2.  Connectez-vous avec votre compte Google.
3.  Cliquez sur « Create API Key ».
4.  Copiez la clé API générée (l'API Gemini offre actuellement un généreux niveau gratuit pour la plupart des utilisateurs).

### Comment obtenir une clé API OpenAI ?
1.  Rendez-vous sur la [plateforme OpenAI](https://platform.openai.com/api-keys).
2.  Créez un compte ou connectez-vous.
3.  Créez une nouvelle clé API.

### Y a-t-il une limite de caractères pour la traduction ?
*   **DeepSeek** : facturé au token ; l'activation du mode de réflexion approfondie augmente la consommation de tokens (y compris les tokens de raisonnement). Consultez la documentation officielle de SiliconFlow pour plus de détails.
*   **Gemini** : propose un niveau gratuit, mais des limites de débit peuvent s'appliquer en cas de forte concurrence ou d'utilisation intensive.
*   **OpenAI** : facturé au token ; les tarifs varient selon le modèle.

### Que faire en cas de limitation de débit de l'API ?
L'extension intègre un mécanisme de nouvelle tentative automatique. Lorsque l'API renvoie 429 (limitation de débit) ou 5xx (erreur serveur), elle attend puis réessaie automatiquement (jusqu'à 2 fois), sans intervention manuelle.

## 📅 Journal des modifications

### 2.0.0
*   **Refonte de l'architecture** : introduction de trois classes de base abstraites - AbstractTranslationEngine, AbstractApiHelper et AbstractRegisterEngine - éliminant environ 1 000 lignes de code dupliqué.
*   **Nouveau : nouvelle tentative automatique** : nouvelle tentative automatique avec repli exponentiel en cas d'erreurs 429/5xx (jusqu'à 2 fois), en respectant l'en-tête de réponse Retry-After.
*   **Nouveau : prise en charge multisite** : compatible avec TranslatePress activé au niveau du réseau sur WordPress Multisite.
*   **Nouveau : configuration du point de terminaison DeepSeek** : choix entre les points de terminaison SiliconFlow, DeepSeek officiel et URL personnalisée.
*   **Amélioré : architecture System Message** : DeepSeek et OpenAI utilisent des messages system/user distincts ; Gemini utilise le champ systemInstruction.
*   **Amélioré : mise à jour des modèles Gemini** : remplacés par les derniers modèles tels que Gemini 2.5 Flash/Pro et Gemini 3 Flash.
*   **Amélioré : mise à jour des modèles OpenAI** : ajout de la prise en charge de GPT-5.4.
*   **Amélioré : amélioration de l'invite Gemini** : mise à niveau vers le même jeu détaillé de 8 règles que DeepSeek/OpenAI.
*   **Amélioré : correction de l'analyse des traductions** : correction du décalage de traduction causé par `array_values()` dans `parseTranslatedItems()`.
*   **Amélioré : correction des paramètres Gemini** : taille de chunk ajustée de 100 à 20, et maxOutputTokens augmenté de 8192 à 65536.
*   **Amélioré : `cleanupTranslatedText`** : schémas de nettoyage multilingues étendus couvrant EN/ZH/JA/KO/FR/DE/ES et plus.
*   **Sécurité : protection contre l'injection HTML** : les trois moteurs ajoutent désormais uniformément la protection `sanitize_injected_markup()`.
*   **Sécurité : affichage masqué des clés API** : tous les champs de saisie de clé API passés en `type="password"`.
*   **Correction : synchronisation du numéro de version** : l'en-tête de l'extension et Common::PLUGIN_VERSION restent cohérents.
*   **Correction : complétion des langues DeepSeek** : ajout de la prise en charge du hindi, du thaï et du vietnamien.
*   **Correction : journalisation des erreurs** : tous les moteurs ajoutent des journaux d'erreurs détaillés pour les réponses non-200 et WP_Error.
*   **Correction : validation renforcée des clés API** : ajout d'une vérification de longueur minimale en plus de la vérification de valeur vide.
*   **Correction : mappage des langues DeepSeek** : complétion des mappages pt_BR/pt_PT manquants.

### 1.6.0
*   Nouveau : mode de réflexion approfondie DeepSeek (`enable_thinking`), activé via l'API SiliconFlow pour améliorer la qualité de traduction.
*   Nouveau : Thinking Budget configurable dans l'administration (128-32768 tokens, 4096 par défaut).
*   Amélioré : `max_tokens` porté à 60000 pour garantir un espace de traduction suffisant en mode réflexion.
*   Amélioré : délai d'expiration automatiquement porté de 180s à 300s lorsque le mode réflexion est activé.

### 1.5.1
*   Correction : correction d'un PHP Warning causé par un `%1$s` non échappé dans DeepSeekApiHelper.
*   Amélioré : mise à jour de la documentation README.

### 1.5.0
*   Nouveau : prise en charge du moteur de traduction OpenAI (ChatGPT).
*   Nouveau : option OpenAI Endpoint (compatible avec différents modèles et proxys).

### 1.4.0
*   Nouveau : prise en charge de l'API Google Gemini.
*   Nouveau : possibilité de choisir différents modèles Gemini (Flash, Pro).
*   Amélioré : de meilleures invites de traduction qui suppriment automatiquement le texte explicatif que l'IA peut ajouter.

## 🙏 Remerciements

Cette extension est une extension et une amélioration du projet open source **Hollisho Integration with DeepSeek for TranslatePress**.

*   Auteur original : hollisho
*   URL du projet : [Hollisho GitHub](https://github.com/hollisho)

---
Développé par [Rankscale AI](https://rankscaleai.com/)
