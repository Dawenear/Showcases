<?php

/**
 * Those are mostly aliases for RenamedLanguageHandler as in system every translation function was defined as sole function I had to keep that aliases alive
 * Only exception is exportArray which could be replaced by var_export() but only as long as all validity checks will be performed before prior export
 */


/**
 * Translate into current language
 * @param string|null $key
 * @param string|null $module
 * @param string|null $selectedValue
 * @param bool $preventArrayReturn
 * @return array|string|string[]|null|integer
 */
function translate(?string $key, ?string $module = null, ?string $selectedValue = null, bool $preventArrayReturn = true)
{
    return RenamedLanguageHandler::translate(null, $key, $module, $selectedValue, $preventArrayReturn);
}

/**
 * Translate into defined language
 * @param string|null $language
 * @param string|null $key
 * @param string|null $module
 * @param string|null $selectedValue
 * @param bool $preventArrayReturn
 * @return array|string|string[]|null|integer
 */
function languageTranslate(?string $language, ?string $key, ?string $module = null, ?string $selectedValue = null, bool $preventArrayReturn = true)
{
    return RenamedLanguageHandler::translate($language, $key, $module, $selectedValue, $preventArrayReturn);
}

/**
 * Translate enum value
 * @param string $enum
 * @param string $key
 * @return array|string|string[]|null|integer
 */
function translateEnum(string $enum, string $key)
{
    return RenamedLanguageHandler::translate(null, $enum, null, $key);
}

/**
 * Translate enum value to defined language
 * @param string $language
 * @param string $enum
 * @param string $key
 * @return array|string|string[]|null|integer
 */
function languageTranslateEnum(string $language, string $enum, string $key)
{
    return RenamedLanguageHandler::translate($language, $enum, null, $key);
}

/**
 * Translate into current language as template with variables
 * @param string|null $key
 * @param string|null $module
 * @param array|null $variables array pairs of variables
 * @param bool $removeUnused
 * @return array|string|string[]|null
 */
function translateTemplate(?string $key, ?string $module, ?array $variables, bool $removeUnused = true)
{
    return RenamedLanguageHandler::translateTemplate(null, $key, $module, $variables, $removeUnused);
}

/**
 * Translate into defined language as template with variables
 * @param string|null $language
 * @param string|null $key
 * @param string|null $module
 * @param array|null $variables
 * @param bool $removeUnused
 * @return array|string|string[]|null
 */
function languageTranslateTemplate(?string $language, ?string $key, ?string $module, ?array $variables, bool $removeUnused = true)
{
    return RenamedLanguageHandler::translateTemplate($language, $key, $module, $variables, $removeUnused);
}

/**
 * Returns enum
 * @param string $enumName
 * @param string|null $language
 * @return array|string|string[]|null|integer
 */
function getEnum(string $enumName, ?string $language = null)
{
    return RenamedLanguageHandler::translate($language, $enumName, null, null, false);
}

/**
 * Returns global translations
 * @param string|null $language
 * @return array
 */
function getTranslations(string $language = null): array
{
    return RenamedLanguageHandler::getTranslations($language);
}

/**
 * Returns mod translation for specified module or all modules
 * @param null|string $language
 * @param null|string|array $moduleList
 * @return array
 */
function getModuleTranslations(?string $language = null, $moduleList = null): array
{
    return RenamedLanguageHandler::getModuleTranslations($language, $moduleList);
}

/**
 * Load translations into $GLOBALS
 * @param string|null $language
 * @param bool $loadModules
 * @return bool
 */
function loadTranslations(?string $language = null, $loadModules = true): bool
{
    return RenamedLanguageHandler::loadTranslations($language, $loadModules);
}

/**
 * Function for saving cache language translations (mostly dynamically declared)
 * @param array $languageData
 * @param array|string|null $languages
 * @return bool
 * @throws Exception
 */
function saveLocalLanguageData(array $languageData, $languages = ''): bool
{
    return RenamedLanguageHandler::saveLocalLanguageData($languageData, $languages);
}

/**
 * @param array $array
 * @param string $name
 * @param bool $oneArray
 * @param string $separator
 * @param bool $top
 * @param int $offset
 * @return string
 * @throws Exception
 */
function exportArray(array $array, string $name, bool $oneArray = false, string $separator = PHP_EOL, bool $top = true, int $offset = 0): string
{
    $return = '';
    $end = '';
    if ($oneArray && $top) {
        $top = false;
        $end = str_pad('', $offset, ' ') . '];' . $separator;
        $return .= str_pad('', $offset, ' ') . $name . ' = [' . $separator;
    }
    foreach ($array as $key => $item) {
        $key = addcslashes($key, "'");
        $return .= $top ? $name . "['{$key}'] = " : str_pad('', $offset, ' ') . "'{$key}' => ";

        switch (gettype($item)) {
            case 'boolean':
                $return .= $item ? 'true' : 'false';
                break;
            case 'integer':
            case 'double':
                $return .= $item;
                break;
            case 'string':
                $return .= "'" . addcslashes($item, "'") . "'";
                break;
            case 'NULL':
                $return .= 'null';
                break;
            case 'array':
                $return .= '[' . ($item ?
                        $separator . exportArray(
                            $item,
                            $name,
                            false,
                            $separator,
                            false,
                            $offset + 4
                        ) . str_pad('', $offset, ' ') : '') . ']';
                break;
            default:
                throw new Exception();
        }
        $return .= ($top ? ';' : ',') . $separator;
    }
    $return .= $end;

    return $return;
}
