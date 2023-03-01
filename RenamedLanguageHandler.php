<?php

/**
 * Class for translations handling.
 * Lots of stuff was defined (like paths, using $GLOBALS etc.) which I cannot change as it was supposed to support back compatibility
 * Otherwise I know that some solutions are not ideal, but as little time I got to solve this I'm satisfied with provided solution
 * Obviously I didn't copied whole class and expediently changes few things. Like removed all paths.
 */

namespace removed\RenamedLanguageHandler;

use removed\NotificationClass;
use removed\User;
use removed\Exception;

class RenamedLanguageHandler
{
    /**
     * @param null|string $language
     * @param null|string $key
     * @param null|string $module
     * @param null|string $selectedValue
     * @param bool $preventArrayReturn
     *
     * @return array|string|string[]|null|integer
     */
    public static function translate(?string $language, ?string $key, ?string $module = null, ?string $selectedValue = null, bool $preventArrayReturn = true)
    {
        if (!$language || $GLOBALS['current_lang'] === $language) {
            $languageData = &$GLOBALS;
        } else {
            $languageData = &$GLOBALS['translations'][$language];
        }

        if (!$languageData['globalStrings']) {
            self::loadTranslations($language, false);
        }
        if ($module && !$languageData['moduleTranslationStrings'][$module]) {
            $languageData['moduleTranslationStrings'][$module] = (self::getModuleTranslations($language, $module))[$module];
        }

        switch (true) {
            case $module && !empty($returnString = $languageData['moduleTranslationStrings'][$key]):
            case $module && !empty($returnString = $languageData['moduleTranslationStrings'][$module][$key]):
            case !empty($returnString = $languageData['globalStrings'][$key]):
                break;
            default:
                $returnString = $key;
                break;
        }

        if ($selectedValue !== null) {
            $returnString = $returnString[$selectedValue] ?? $selectedValue;
        }

        if ($preventArrayReturn && is_array($returnString)) {
            $returnString = $selectedValue ?? $key;
        }

        return $returnString;
    }

    /**
     * @param null|string $language
     * @param null|string $key
     * @param null|string $module
     * @param null|array $variables
     * @param bool $removeUnused
     * @param bool $escapeSpecialChars
     * @return array|string|string[]|null
     */
    public static function translateTemplate(
        ?string $language,
        ?string $key,
        ?string $module,
        ?array $variables,
        bool $removeUnused = true,
        bool $escapeSpecialChars = true
    ) {
        $translation = self::translate($language, $key, $module);
        $escapedArray = [];

        foreach ($variables as $replaceFrom => $replaceTo) {
            if (!ctype_alnum($replaceFrom)) {
                continue;
            }
            $escapedArray['{#' . ($escapeSpecialChars ? htmlspecialchars($replaceFrom) : $replaceFrom) . '#}'] =
                ($escapeSpecialChars ? htmlspecialchars($replaceTo) : $replaceTo);
        }
        $translation = strtr($translation, $escapedArray);

        return $removeUnused ?
            preg_replace(
                '/((?<=^|[^\\\]){#[\w]+#})|(\\\)({#[\w]+#})/U',
                '$3',
                $translation
            ) : // Prazdne NEBO s lomitkem pred
            preg_replace('/(\\\)({#[\w]+#})/U', '$2', $translation); // s lomitkem pred
    }

    /**
     * @param string $lang
     * @return array
     */
    public static function loadLanguageFiles(string $lang): array
    {
        $globalStrings = [];
        $renamedVariable = [];
        $data = [];
        ob_start();
        if (is_file($fileName = "baseLanguages/{$lang}.lang.php")) {
            require $fileName;
        }
        if (is_file($fileName = "someOldExtFileWithoutPurpose/{$lang}.lang.ext.php")) {
            require $fileName;
        }
        if (is_file($fileName = "customReplacements/{$lang}.lang.php")) {
            require $fileName;
        }
        if (is_file($fileName = "dynamicalCache/{$lang}.lang.php")) {
            require $fileName;
        }
        if (is_file($fileName = "dynamicEnums/{$lang}.php")) {
            require $fileName;
            $globalStrings['dynamicEnum'] = $data;
        }

        if ($GLOBALS['config']['CDE'] && $prefix = $GLOBALS['sugar_config']['clientDetailEditPrefix']) {
            list($customEnums) = self::loadClientLanguageData($lang, $prefix, true, false);
            if ($customEnums) {
                $globalStrings = array_replace($globalStrings, $customEnums);
            }
        }

        if (ob_get_contents()) {
            self::logLanguageError();
        }
        ob_end_clean();

        return array_replace(
            $renamedVariable ?: [],
            $GLOBALS['renamedVariable'] ?: [],
            $globalStrings ?: [],
            $GLOBALS['globalStrings'] ?: [],
            $data
        );
    }

    /**
     * Return global language translations
     * @param null|string $language
     * @return array
     */
    public static function getTranslations(?string $language = null): array
    {
        if (!$language) {
            $language = $GLOBALS['current_lang'];
        }

        $languages = [];
        if ($language !== $GLOBALS['config']['default_lang']) {
            $languages[] = $GLOBALS['config']['default_lang'];
        }
        $languages[] = $language;

        $translationStrings = [];
        foreach ($languages as $lang) {
            if ($lang) {
                $globalStrings = self::loadLanguageFiles($lang);
                $translationStrings = array_replace($translationStrings, $globalStrings);
            }
        }

        return $translationStrings;
    }

    /**
     * Return module translations
     * If module is not specified, it returns all modules
     * @param null|string $language
     * @param null|string|array $moduleList
     * @return array
     */
    public static function getModuleTranslations(?string $language = null, $moduleList = null): array
    {
        if (!$moduleList) {
            $moduleList = array_merge($GLOBALS['modList'], $GLOBALS['modInvisList']);
        } elseif (is_string($moduleList)) {
            $moduleList = [$moduleList];
        }

        $languages = [
            $language ?: $GLOBALS['current_language'],
        ];
        if ($languages[0] !== $GLOBALS['config']['default_lang']) {
            array_unshift($languages, $GLOBALS['config']['default_lang']);
        }

        $translationStrings = [];
        $clientModString = [];

        if ($GLOBALS['config']['CDE'] && $prefix = $GLOBALS['config']['clientDetailEditPrefix']) {
            list($customEnums, $modStrings) = self::loadClientLanguageData(end($languages), $prefix, false, true);
            $clientModString = $modStrings;
        }

        foreach ($moduleList as $module) {
            $moduleTranslation = [];
            foreach ($languages as $lang) {
                if (!is_file($fileName = "path/{$module}/language/{$lang}.lang.php")) {
                    // if file not exists run default restore function
                    self::rLang($module, $lang);
                }
                if (is_file($fileName)) {
                    $moduleTranslationStrings = [];
                    require $fileName;
                    $moduleTranslation = array_replace($moduleTranslation ?? [], $moduleTranslationStrings ?? []);
                }
            }
            $translationStrings[$module] = $moduleTranslation;
            if ($clientModString && $clientModString[$module]) {
                $translationStrings[$module] = array_replace($translationStrings[$module], $clientModString[$module]);
            }
        }

        return $translationStrings;
    }

    /**
     * Load translation into $GLOBALS
     * @param ?string $language
     * @param bool $loadModules
     * @return bool
     */
    public static function loadTranslations(?string $language = null, $loadModules = true): bool
    {
        global $current_language;
        if (!$language) {
            $language = $current_language;
        }
        if (!$language || $GLOBALS['current_lang'] === $language) {
            $languageData = &$GLOBALS;
        } else {
            $languageData = &$GLOBALS['translations'][$language];
        }

        $languageData['globalStrings'] = self::getTranslations($language);
        $languageData['moduleTranslationStrings'] = $loadModules ? self::getModuleTranslations($language) : [];

        return true;
    }

    /**
     * Function for saving cache language translations (mostly dynamically declared)
     * @param mixed $languageData
     * @param mixed $languages
     * @return bool
     * @throws Exception
     */
    public static function saveLocalLanguageData($languageData, $languages = ''): bool
    {
        if (!$languages) {
            $languages = array_keys($GLOBALS['config']['languages']);
        }
        if (!is_array($languages)) {
            $languages = [$languages];
        }
        $return = true;
        if (!is_dir('pathToLangCache')) {
            mkdir('pathToLangCache');
        }

        foreach ($languages as $language) {
            $globalStrings = [];
            $fileName = "pathToLangCache/{$language}.lang.php";
            if (is_file($fileName)) {
                require $fileName;
            }

            $globalStrings = array_replace($globalStrings, $languageData);

            $content = "<?php \n";
            $content .= exportArray($globalStrings, '$globalStrings');
            $return = $return ? (file_put_contents($fileName, $content) !== false) : false;
        }

        return $return;
    }

    /**
     * @return void
     */
    private static function logLanguageError()
    {
        if (User::$current->isAdmin()) {
            $GLOBALS['log']->fatal('Error occured while loading languages');

            $notifyParams = array(
                'name' => 'Error occurred while loading languages',
                'description' => 'Error occurred while loading languages',
                'assigned_user_id' => User::$current->id,
                'parent_module' => 'Users',
                'parent_record' => User::$current->id,
                'url' => "#home",
            );
            $notifyObj = new NotificationClass();
            $notifyObj->createNotify($notifyParams);
        }
    }

    /**
     * @param string|null $lang
     * @param string|null $prefix
     * @param bool $loadCustomEnums
     * @param bool $loadModStrings
     *
     * @return array
     */
    public static function loadClientLanguageData(
        ?string $lang = null,
        ?string $prefix = null,
        bool $loadCustomEnums = true,
        bool $loadModStrings = true
    ): array {
        $lang = $lang ?: $GLOBALS['current_lang'];
        if (!$prefix) {
            return [];
        }
        $customEnums = [];
        $modStrings = [];
        if (
            $loadModStrings
            && is_file($fileName = __DIR__ . "/superSecretPath/{$lang}_{$prefix}_someWeirdSuffix.lang.php")
        ) {
            $loadModStrings = false;
            require $fileName;
        }
        if (
            $loadCustomEnums
            && is_file($fileName = __DIR__ . "/superSecretPath/{$lang}_{$prefix}_anotherReplacedSuffix.lang.php")
        ) {
            $loadCustomEnums = false;
            require $fileName;
        }

        if (
            ($loadModStrings || $loadCustomEnums)
            && is_file($fileName = $fileName = __DIR__ . "/superSecretPath/{$lang}_{$prefix}.lang.php")
        ) {
            $languageData = json_decode(file_get_contents($fileName), true);
            if ($loadModStrings) {
                $modStrings = $languageData;
                unset($modStrings['custom_enum']);
            }
            if ($loadCustomEnums) {
                $customEnums = $languageData['custom_enum'];
            }
        }

        return [$customEnums, $modStrings];
    }
}
