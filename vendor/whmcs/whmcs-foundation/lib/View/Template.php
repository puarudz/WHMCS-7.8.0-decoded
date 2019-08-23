<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\View;

class Template
{
    protected static $setting = "Template";
    protected static $allTemplates = array();
    protected static $defaultTemplate = "six";
    protected static $templateDirectory = "templates";
    protected static $ignoredTemplateDirectories = array("orderforms");
    protected $name = NULL;
    protected $config = NULL;
    public function __construct($name, \WHMCS\Config\Template $config)
    {
        $this->name = trim($name);
        $this->config = $config;
    }
    public static function factory($systemTemplateName = NULL, $sessionTemplateName = NULL, $requestTemplateName = NULL)
    {
        if (is_null($systemTemplateName)) {
            $systemTemplateName = \WHMCS\Config\Setting::getValue(static::$setting);
        }
        if (is_null($sessionTemplateName)) {
            $sessionTemplateName = \WHMCS\Session::get(static::$setting);
        }
        $allTemplates = self::all();
        $availableTemplates = array();
        foreach ($allTemplates as $template) {
            $availableTemplates[] = $template->getName();
        }
        if (in_array($requestTemplateName, $availableTemplates)) {
            \WHMCS\Session::set(static::$setting, $requestTemplateName);
            return self::find($requestTemplateName);
        }
        if (in_array($sessionTemplateName, $availableTemplates)) {
            return self::find($sessionTemplateName);
        }
        if (in_array($systemTemplateName, $availableTemplates)) {
            return self::find($systemTemplateName);
        }
        if (in_array(static::$defaultTemplate, $availableTemplates)) {
            return self::find(static::$defaultTemplate);
        }
        return self::find($availableTemplates[0]);
    }
    public static function find($name)
    {
        $class = get_called_class();
        $path = ROOTDIR . DIRECTORY_SEPARATOR . static::$templateDirectory . DIRECTORY_SEPARATOR . $name;
        try {
            $file = new \WHMCS\File($path);
        } catch (\Exception $e) {
            return null;
        }
        $configFile = $path . DIRECTORY_SEPARATOR . "theme.yaml";
        if ($name != "" && $file->exists()) {
            return new $class($name, new \WHMCS\Config\Template(new \WHMCS\File($configFile)));
        }
        return null;
    }
    public static function all()
    {
        $class = get_called_class();
        if (isset(static::$allTemplates[$class])) {
            return static::$allTemplates[$class];
        }
        $templates = array();
        $directoryIterator = new \DirectoryIterator(ROOTDIR . DIRECTORY_SEPARATOR . static::$templateDirectory);
        foreach ($directoryIterator as $fileInfo) {
            if ($fileInfo->isDir() && !$fileInfo->isDot() && !in_array($fileInfo->getFilename(), static::$ignoredTemplateDirectories)) {
                $template = self::find($fileInfo->getFilename());
                if (!is_null($template)) {
                    $templates[$fileInfo->getFilename()] = $template;
                }
            }
        }
        uasort($templates, function (Template $a, Template $b) {
            if ($a->getName() == $b->getName()) {
                return 0;
            }
            return $b->getName() < $a->getName() ? 1 : -1;
        });
        static::$allTemplates[$class] = new \Illuminate\Support\Collection($templates);
        return static::$allTemplates[$class];
    }
    public static function getDefault()
    {
        return self::find(\WHMCS\Config\Setting::getValue(static::$setting));
    }
    public function getName()
    {
        return $this->name;
    }
    public function getDisplayName()
    {
        return titleCase(str_replace("_", " ", $this->name));
    }
    public function getConfig()
    {
        return $this->config;
    }
    public function isDefault()
    {
        return $this->name == \WHMCS\Config\Setting::getValue(static::$setting);
    }
}

?>