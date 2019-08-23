<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Utility;

class ErrorManagement
{
    protected $runner = NULL;
    protected $deferredHandlers = array();
    const ERROR_LEVEL_DEBUG_VALUE = NULL;
    const ERROR_LEVEL_DEBUG_KEY = "debug";
    const ERROR_LEVEL_WARNINGS_VALUE = NULL;
    const ERROR_LEVEL_WARNINGS_KEY = "warnings";
    const ERROR_LEVEL_ERRORS_VALUE = NULL;
    const ERROR_LEVEL_ERRORS_KEY = "errors";
    const ERROR_LEVEL_NONE_VALUE = 0;
    const ERROR_LEVEL_NONE_KEY = "none";
    const ERROR_LEVEL_INHERIT_VALUE = -1;
    const ERROR_LEVEL_INHERIT_KEY = "inherit";
    public function __construct(\Whoops\RunInterface $runner = NULL)
    {
        $this->runner = $runner;
    }
    public static function performErrorReportingSanityCheck()
    {
        $currentLevel = error_reporting();
        if ($currentLevel <= static::ERROR_LEVEL_ERRORS_VALUE) {
            return true;
        }
        $testValue = static::ERROR_LEVEL_NONE_VALUE;
        static::setErrorReportingLevel($testValue);
        $newLevel = error_reporting();
        if ($currentLevel !== $newLevel) {
            static::setErrorReportingLevel($currentLevel);
            return true;
        }
        return false;
    }
    public function getRunner()
    {
        return $this->runner;
    }
    public static function factoryRunner()
    {
        $runner = new Error\Run();
        return $runner;
    }
    public static function boot(\Whoops\RunInterface $runner = NULL)
    {
        if (!$runner) {
            $runner = static::factoryRunner();
            $runner->pushHandler(new \WHMCS\Exception\Handler\CriticalHtmlHandler());
        }
        $runner->register();
        return new static($runner);
    }
    public function loadApplicationHandlers()
    {
        $runner = $this->getRunner();
        $runner->pushHandler(new \WHMCS\Exception\Handler\PrettyHtmlHandler());
        $runner->pushHandler(new \WHMCS\Exception\Handler\TerminusHandler());
        return $this;
    }
    public static function disableIniDisplayErrors()
    {
        ini_set("display_errors", false);
    }
    public static function enableIniDisplayErrors($setting = true)
    {
        if (!$setting) {
            self::disableIniDisplayErrors();
        }
        if (is_string($setting)) {
            $setting = strtolower($setting);
            if ($setting == "on" || $setting === "1" || $setting === "true") {
                $iniValue = true;
            } else {
                if ($setting == "stderr" || $setting == "stdout") {
                    $iniValue = $setting;
                } else {
                    $msg = "\"%s\" is not a valid value for \"display_errors\"." . " Please see PHP Manual for an appropriate value";
                    throw new \InvalidArgumentException(sprintf($msg, $setting));
                }
            }
        } else {
            $iniValue = true;
        }
        ini_set("display_errors", $iniValue);
    }
    public static function isDisplayErrorCurrentlyVisible()
    {
        $displayErrors = strtolower(ini_get("display_errors"));
        if (in_array($displayErrors, array("on", "true", "1", "stdout")) || $displayErrors === 1 || $displayErrors === true) {
            return true;
        }
        return false;
    }
    public static function setErrorReportingLevel($errorLevel = self::ERROR_LEVEL_NONE_VALUE)
    {
        if (!is_numeric($errorLevel)) {
            throw new \InvalidArgumentException("Error reporting level must be numeric");
        }
        if ($errorLevel == self::ERROR_LEVEL_INHERIT_VALUE) {
            error_reporting(error_reporting());
        } else {
            error_reporting($errorLevel);
        }
    }
    public static function supportedErrorLevels()
    {
        $errorLevels = array(self::ERROR_LEVEL_NONE_KEY => self::ERROR_LEVEL_NONE_VALUE, self::ERROR_LEVEL_INHERIT_KEY => self::ERROR_LEVEL_INHERIT_VALUE, self::ERROR_LEVEL_ERRORS_KEY => self::ERROR_LEVEL_ERRORS_VALUE, self::ERROR_LEVEL_WARNINGS_KEY => self::ERROR_LEVEL_WARNINGS_VALUE);
        return $errorLevels;
    }
    public function setErrorLogging($state = false)
    {
        \WHMCS\Config\Setting::setValue("LogErrors", (bool) $state);
        return $this;
    }
    public function isReportingLevelSupported($level)
    {
        if (in_array($level, self::supportedErrorLevels())) {
            return true;
        }
        return false;
    }
    public static function isAllowedToLogErrors()
    {
        return (bool) \WHMCS\Config\Setting::getValue("LogErrors");
    }
    public static function isAllowedToLogSqlErrors()
    {
        return (bool) \WHMCS\Config\Setting::getValue("SQLErrorReporting");
    }
    public function addDeferredHandler(\Whoops\Handler\Handler $handler)
    {
        $this->deferredHandlers[] = $handler;
        return $this;
    }
    public function loadDeferredHandlers()
    {
        $runner = $this->getRunner();
        foreach ($this->deferredHandlers as $handle) {
            $runner->pushHandler($handle);
        }
        $this->deferredHandlers = array();
        return $this;
    }
    public static function distillErrorReportingLevelFromDisplayErrorSetting($setting = NULL)
    {
        if ($setting === 1 || $setting === true || $setting === "on" || $setting === "true" || $setting === "1") {
            $distilledLevel = static::ERROR_LEVEL_ERRORS_VALUE;
        } else {
            if (is_numeric($setting) && 0 <= $setting) {
                $distilledLevel = $setting;
            } else {
                $distilledLevel = null;
            }
        }
        return $distilledLevel;
    }
    public function applyConfigurationSettings(\WHMCS\Config\AbstractConfig $config)
    {
        $distilledLevel = static::distillErrorReportingLevelFromDisplayErrorSetting($config["display_errors"]);
        if ($distilledLevel) {
            static::enableIniDisplayErrors();
        } else {
            static::disableIniDisplayErrors();
            $distilledLevel = static::ERROR_LEVEL_ERRORS_VALUE;
        }
        if (isset($config["error_reporting_level"]) && is_numeric($config["error_reporting_level"])) {
            if (0 <= $config["error_reporting_level"]) {
                static::setErrorReportingLevel($config["error_reporting_level"]);
            }
        } else {
            static::setErrorReportingLevel($distilledLevel);
        }
    }
}

?>