<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if (!defined("WHMCS")) {
    exit("This file cannot be accessed directly");
}
if (defined("WHMCSLIVECHAT")) {
    return NULL;
}
if (App::getApplicationConfig()->disable_hook_loading === true) {
    return NULL;
}
$hooks = array();
$hooks = loadhookfiles();
WHMCS\Module\Module::defineHooks();
WHMCS\MarketConnect\Promotion::initHooks();
WHMCS\Notification\Events::defineHooks();
function sort_array_by_priority($a, $b)
{
    return $a["priority"] < $b["priority"] ? -1 : 1;
}
function hookToString($hook)
{
    if (is_object($hook) && $hook instanceof WHMCS\Scheduling\Task\TaskInterface && !is_callable($hook)) {
        $callableName = get_class($hook);
    } else {
        is_callable($hook, false, $callableName);
    }
    if ($callableName == "Closure::__invoke") {
        $callableName = "(anonymous function)";
    }
    return $callableName;
}
function hook_log($hook_name, $msg, $input1 = "", $input2 = "", $input3 = "")
{
    if ($hook_name == "LogActivity") {
        return NULL;
    }
    $HooksDebugMode = WHMCS\Config\Setting::getValue("HooksDebugMode");
    $hookLogging = defined("HOOKSLOGGING") || $HooksDebugMode;
    if ($hookLogging) {
        $specificHookLogging = App::getApplicationConfig()->hooks_debug_whitelist;
        if ($specificHookLogging && is_array($specificHookLogging) && 0 < count($specificHookLogging) && !in_array($hook_name, $specificHookLogging)) {
            $hookLogging = false;
        }
        if ($hookLogging) {
            $msg = "Hooks Debug: " . $msg;
            if (defined("IN_CRON")) {
                $msg = "Cron Job: " . $msg;
            }
            logActivity(sprintf($msg, $input1, $input2, $input3));
        }
    }
}
function get_registered_hooks($hookName)
{
    global $hooks;
    if (is_array($hooks) && isset($hooks[$hookName]) && is_array($hooks[$hookName])) {
        return $hooks[$hookName];
    }
    return array();
}
function set_registered_hooks($hookName, array $hooks)
{
    global $hooks;
    if (!is_array($hooks)) {
        $hooks = array();
    }
    $hooks[$hookName] = $hooks;
}
function run_hook($hook_name, $args, $unpackArguments = false)
{
    global $hooks;
    if (!is_array($hooks)) {
        hook_log($hook_name, "Hook File: the hooks list has been mutated to %s", ucfirst(gettype($hooks)));
        $hooks = array();
    }
    hook_log($hook_name, "Called Hook Point %s", $hook_name);
    if (!array_key_exists($hook_name, $hooks)) {
        hook_log($hook_name, "No Hook Functions Defined", $hook_name);
        return array();
    }
    $license = DI::make("license");
    if ($license->isUnlicensed() && defined("ADMINAREA") && constant("ADMINAREA")) {
        hook_log($hook_name, "Admin Hook Functions not allowed without active license", $hook_name);
        return array();
    }
    unset($rollbacks);
    $rollbacks = array();
    reset($hooks[$hook_name]);
    $results = array();
    foreach ($hooks[$hook_name] as $hook) {
        array_push($rollbacks, $hook["rollback_function"]);
        if (is_callable($hook["hook_function"])) {
            hook_log($hook_name, "Hook Point %s - Calling Hook Function %s", $hook_name, hooktostring($hook["hook_function"]));
            $res = $unpackArguments ? call_user_func_array($hook["hook_function"], $args) : call_user_func($hook["hook_function"], $args);
            if ($res) {
                $results[] = $res;
                hook_log($hook_name, "Hook Completed - Returned True");
            } else {
                hook_log($hook_name, "Hook Completed - Returned False");
            }
        } else {
            hook_log($hook_name, "Hook Function %s Not Found", hooktostring($hook["hook_function"]));
        }
    }
    return $results;
}
function add_hook($hook_name, $priority, $hook_function, $rollback_function = "")
{
    global $hooks;
    if (!is_array($hooks)) {
        hook_log($hook_name, "Hook File: the hooks list has been mutated to %s", ucfirst(gettype($hooks)));
        $hooks = array();
    }
    if (!array_key_exists($hook_name, $hooks)) {
        $hooks[$hook_name] = array();
    }
    array_push($hooks[$hook_name], array("priority" => $priority, "hook_function" => $hook_function, "rollback_function" => $rollback_function));
    hook_log($hook_name, "Hook Defined for Point: %s - Priority: %s - Function Name: %s", $hook_name, $priority, hooktostring($hook_function));
    uasort($hooks[$hook_name], "sort_array_by_priority");
}
function remove_hook($hook_name, $priority, $hook_function, $rollback_function)
{
    global $hooks;
    if (!is_array($hooks)) {
        hook_log($hook_name, "Hook File: the hooks list has been mutated to %s", ucfirst(gettype($hooks)));
        $hooks = array();
    }
    if (array_key_exists($hook_name, $hooks)) {
        reset($hooks[$hook_name]);
        foreach ($hooks[$hook_name] as $key => $hook) {
            if (0 <= $priority && $priority == $hook["priority"] || $hook_function && $hook_function == $hook["hook_function"] || $rollback_function && $rollback_function == $hook["rollback_function"]) {
                unset($hooks[$hook_name][$key]);
            }
        }
    }
}
function clear_hooks($hook_name)
{
    global $hooks;
    if (!is_array($hooks)) {
        hook_log($hook_name, "Hook File: the hooks list has been mutated to %s", ucfirst(gettype($hooks)));
        $hooks = array();
    }
    if (array_key_exists($hook_name, $hooks)) {
        unset($hooks[$hook_name]);
    }
}
function run_validate_hook(&$validate, $hook_name, $args)
{
    $hookerrors = run_hook($hook_name, $args);
    $errormessage = "";
    if (is_array($hookerrors) && count($hookerrors)) {
        foreach ($hookerrors as $hookerrors2) {
            if (is_array($hookerrors2)) {
                $validate->addErrors($hookerrors2);
            } else {
                $validate->addError($hookerrors2);
            }
        }
    }
}
function processHookResults($moduleName, $function, array $hookResults = array())
{
    if (!empty($hookResults)) {
        $hookErrors = array();
        $abortWithSuccess = false;
        foreach ($hookResults as $hookResult) {
            if (!empty($hookResult["abortWithError"])) {
                $hookErrors[] = $hookResult["abortWithError"];
            }
            if (array_key_exists("abortWithSuccess", $hookResult) && $hookResult["abortWithSuccess"] === true) {
                $abortWithSuccess = true;
            }
        }
        if (count($hookErrors)) {
            throw new WHMCS\Exception(implode(" ", $hookErrors));
        }
        if ($abortWithSuccess) {
            logActivity("Function " . $moduleName . "->" . $function . "() Aborted by Action Hook Code");
            return true;
        }
    }
    return false;
}
function loadHookFiles()
{
    global $hooks;
    $hooks = array();
    $hooksdir = ROOTDIR . "/includes/hooks/";
    $dh = opendir($hooksdir);
    while (false !== ($hookfile = readdir($dh))) {
        $hookFilePath = $hooksdir . $hookfile;
        if (is_file($hookFilePath)) {
            $extension = pathinfo($hookfile, PATHINFO_EXTENSION);
            if ($extension == "php") {
                hook_log("", "Attempting to load hook file: %s", $hookFilePath);
                $hookLoaded = WHMCS\Utility\SafeInclude::file($hookFilePath, function ($errorMessage) {
                    hook_log("", $errorMessage);
                });
                if ($hookLoaded) {
                    hook_log("", "Hook File Loaded: %s", $hookFilePath);
                    if (!is_array($hooks)) {
                        hook_log("", "Hook File: %s mutated the hooks list from Array to %s", $hookFilePath, ucfirst(gettype($hooks)));
                        $hooks = array();
                    }
                }
            }
        }
    }
    closedir($dh);
    return $hooks;
}

?>