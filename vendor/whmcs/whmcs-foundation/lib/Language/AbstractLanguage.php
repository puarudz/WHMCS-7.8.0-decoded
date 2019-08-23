<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Language;

abstract class AbstractLanguage extends \Symfony\Component\Translation\Translator
{
    protected $globalVariable = "";
    protected $name = NULL;
    protected static $languageCache = NULL;
    const FALLBACK_LANGUAGE = "english";
    public function __construct($name = "english", $fallback = self::FALLBACK_LANGUAGE, $languageDirectoryOverride = NULL)
    {
        $languageDirectory = is_null($languageDirectoryOverride) ? static::getDirectory() : $languageDirectoryOverride;
        $path = $languageDirectory . DIRECTORY_SEPARATOR . $name . ".php";
        $overridePath = $languageDirectory . DIRECTORY_SEPARATOR . "overrides" . DIRECTORY_SEPARATOR . $name . ".php";
        $fallbackPath = $languageDirectory . DIRECTORY_SEPARATOR . $fallback . ".php";
        $fallbackLocales = array_unique(array($name, $fallback));
        parent::__construct("override_" . $name);
        $this->setFallbackLocales($fallbackLocales);
        $this->addLoader("whmcs", new Loader\WhmcsLoader($this->globalVariable));
        $this->addLoader("dynamic", new Loader\DatabaseLoader());
        if (\WHMCS\Config\Setting::getValue("EnableTranslations")) {
            $this->addResource("dynamic", null, $name, "dynamicMessages");
            $fallbackLanguage = \WHMCS\Config\Setting::getValue("Language");
            if ($fallbackLanguage != $name) {
                $this->addResource("dynamic", null, $fallbackLanguage, "dynamicMessages");
            }
        }
        if (file_exists($overridePath)) {
            $this->addResource("whmcs", $overridePath, "override_" . $name);
        }
        if (file_exists($path)) {
            $this->addResource("whmcs", $path, $name);
        }
        if ($fallbackPath != $path && file_exists($fallbackPath)) {
            $this->addResource("whmcs", $fallbackPath, $fallback);
        }
    }
    public static function getLanguages($languageDirectoryOverride = NULL)
    {
        $languages = array();
        static $languagesCache = NULL;
        $languageDirectory = is_null($languageDirectoryOverride) ? static::getDirectory() : $languageDirectoryOverride;
        if (!is_null($languagesCache) && isset($languagesCache[$languageDirectory])) {
            return $languagesCache[$languageDirectory];
        }
        $glob = glob($languageDirectory . DIRECTORY_SEPARATOR . "*.php");
        if ($glob === false) {
            throw new \WHMCS\Exception("Unable to read language directory.");
        }
        foreach ($glob as $languageFile) {
            $languageName = pathinfo($languageFile, PATHINFO_FILENAME);
            if (preg_match("/^[a-z0-9@_\\.\\-]*\$/i", $languageName) && $languageName != "index") {
                $languages[] = $languageName;
            }
        }
        if (count($languages) == 0) {
            throw new \WHMCS\Exception("Could not find any language files.");
        }
        $languagesCache[$languageDirectory] = $languages;
        return $languages;
    }
    public static function getValidLanguageName($language, $fallback = self::FALLBACK_LANGUAGE)
    {
        $language = strtolower(trim($language));
        $fallback = strtolower(trim($fallback));
        $englishFallback = strtolower(trim(self::FALLBACK_LANGUAGE));
        $languages = static::getLanguages();
        if (!in_array($language, $languages)) {
            if (in_array($englishFallback, $languages)) {
                $language = in_array($fallback, $languages) ? $fallback : $englishFallback;
            } else {
                $language = in_array($fallback, $languages) ? $fallback : $languages[0];
            }
        }
        return $language;
    }
    public static function getLocales()
    {
        static $locales = array();
        $class = get_called_class();
        if (0 < count($locales)) {
            return $locales;
        }
        $transientData = new \WHMCS\TransientData();
        $cachedLocales = $transientData->retrieve($class . "Locales");
        if ($cachedLocales) {
            $cachedLocales = json_decode($cachedLocales, true);
            if ($cachedLocales["hash"] == md5(implode(",", static::getLanguages()))) {
                $locales = $cachedLocales["locales"];
                return $locales;
            }
        }
        foreach (static::getLanguages() as $language) {
            ${$language} = new $class($language);
            $locale = ${$language}->getLanguageLocale();
            list($languageCode, $countryCode) = explode("_", $locale, 2);
            $locales[] = array("locale" => $locale, "language" => $language, "languageCode" => $languageCode, "countryCode" => $countryCode, "localisedName" => upperCaseFirstLetter(\Punic\Language::getName($languageCode, $locale)));
        }
        $transientData->store($class . "Locales", json_encode(array("hash" => md5(implode(",", static::getLanguages())), "locales" => $locales)), 24 * 60 * 60);
        return $locales;
    }
    public function getLanguageLocale()
    {
        return $this->trans("locale");
    }
    public function getName()
    {
        return str_replace("override_", "", $this->getLocale());
    }
    public function toArray()
    {
        $return = array();
        $messages = array();
        $catalogue = $this->getCatalogue("override_" . $this->getName());
        if ($catalogue) {
            $messages = $catalogue->all();
            while ($catalogue = $catalogue->getFallbackCatalogue()) {
                $messages = array_replace_recursive($catalogue->all(), $messages);
            }
        }
        $messages = isset($messages["messages"]) ? $messages["messages"] : array();
        foreach ($messages as $key => $value) {
            $this->unFlatten($return, $key, $value);
        }
        return $return;
    }
    protected function unFlatten(array &$messages, $key, $value)
    {
        if (strpos($key, ".") === false) {
            $messages[$key] = $value;
        } else {
            list($key, $remainder) = explode(".", $key, 2);
            if (!isset($messages[$key])) {
                $messages[$key] = array();
            }
            $this->unFlatten($messages[$key], $remainder, $value);
        }
    }
    public function getFile()
    {
        return static::getDirectory() . DIRECTORY_SEPARATOR . $this->getName() . ".php";
    }
    public function synchronizeFileWith(AbstractLanguage $language, $saveTo = NULL)
    {
        $localMessages = $this->getCatalogue("messages");
        if ($localMessages->all() == array()) {
            $localMessages = $localMessages->getFallbackCatalogue()->all();
        }
        $languageMessages = $language->getCatalogue("messages");
        if ($languageMessages->all() == array()) {
            $languageMessages = $languageMessages->getFallbackCatalogue("messages")->all();
        }
        foreach ($languageMessages["messages"] as $key => $message) {
            if (!isset($localMessages["messages"][$key])) {
                $localMessages["messages"][$key] = $message;
            }
        }
        $newFileContents = array();
        $localFileContents = file($this->getFile(), FILE_IGNORE_NEW_LINES);
        foreach (file($language->getFile(), FILE_IGNORE_NEW_LINES) as $lineNumber => $line) {
            if (strpos($line, "\$") === 0) {
                list($originalKey) = explode("=", $line, 2);
                $originalKey = trim($originalKey);
                $key = str_replace(array("\$" . $this->globalVariable, "[", "]"), "", $originalKey);
                $key = str_replace(array("''", "\"\""), ".", $key);
                $key = str_replace(array("\"", "'"), "", $key);
                $translatedMessage = str_replace("\"", "\\\"", $localMessages["messages"][$key]);
                $translatedMessage = str_replace("\n", "\\n", $translatedMessage);
                $originalKey = str_replace("\"", "'", $originalKey);
                $newFileContents[] = (string) $originalKey . " = \"" . $translatedMessage . "\";";
            } else {
                if (strpos($line, "//////////") === 0) {
                    continue;
                }
                if (strpos($line, " *") === 0) {
                    $newFileContents[] = $localFileContents[$lineNumber];
                } else {
                    $newFileContents[] = $line;
                }
            }
        }
        $saveTo = is_null($saveTo) ? $this->getFile() : $saveTo;
        file_put_contents($saveTo, implode("\n", $newFileContents) . $this->getLanguageFileFooter());
        return $this;
    }
    public function getLanguageFileFooter()
    {
        return "\n";
    }
    protected static function findOrCreate($languageName)
    {
        $className = get_called_class();
        $scope = $className . "." . $languageName;
        if (empty(self::$languageCache[$scope])) {
            $language = new $className($languageName);
            self::$languageCache[$scope] = $language;
        } else {
            $language = self::$languageCache[$scope];
        }
        return $language;
    }
}

?>