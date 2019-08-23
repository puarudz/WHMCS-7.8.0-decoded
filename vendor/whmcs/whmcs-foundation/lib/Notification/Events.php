<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Notification;

class Events
{
    const TICKET = "Ticket";
    const INVOICE = "Invoice";
    const ORDER = "Order";
    const SERVICE = "Service";
    const DOMAIN = "Domain";
    const API = "API";
    const EventClasses = NULL;
    public static function all()
    {
        $events = array();
        foreach (self::EventClasses as $eventType) {
            $eventClass = "WHMCS\\Notification\\Events\\" . $eventType;
            $events[] = new $eventClass();
        }
        return $events;
    }
    public static function factory($name)
    {
        if (in_array($name, self::EventClasses)) {
            $eventClass = "WHMCS\\Notification\\Events\\" . $name;
            return new $eventClass();
        }
        return null;
    }
    public static function defineHooks()
    {
        foreach (self::all() as $events) {
            $eventType = getClassName($events);
            foreach ($events->getEvents() as $eventName => $params) {
                $hookPoints = $params["hook"];
                if (!is_array($hookPoints)) {
                    $hookPoints = array($hookPoints);
                }
                foreach ($hookPoints as $hookPoint) {
                    add_hook($hookPoint, 1, function ($vars) use($eventType, $eventName) {
                        Events::trigger($eventType, $eventName, $vars);
                    });
                }
            }
        }
    }
    public static function trigger($eventType, $event, $hookParameters)
    {
        $rulesCache = Rule::getCache();
        if (!isset($rulesCache[$eventType][$event])) {
            return false;
        }
        $origAdminLang = null;
        $rules = Rule::active()->whereIn("id", $rulesCache[$eventType][$event])->get();
        foreach ($rules as $rule) {
            $eventClass = "WHMCS\\Notification\\Events\\" . $eventType;
            $eventObj = new $eventClass();
            if ($eventObj->evaluateConditions($event, $rule->conditions, $hookParameters)) {
                try {
                    $adminLanguage = \WHMCS\User\Admin::orderBy("roleid")->orderBy("id")->pluck("language")->first();
                    if (\AdminLang::getName() != $adminLanguage) {
                        $origAdminLang = \AdminLang::getName();
                        \DI::forgetInstance("adminlang");
                        $adminLang = \DI::make("adminlang", array($adminLanguage));
                        \AdminLang::swap($adminLang);
                    }
                } catch (\Exception $e) {
                }
                $notification = $eventObj->buildNotification($event, $hookParameters);
                if (!$notification) {
                    return false;
                }
                try {
                    run_hook("NotificationPreSend", array("eventType" => $eventType, "eventName" => $event, "rule" => $rule, "hookParameters" => $hookParameters, "notification" => $notification));
                } catch (Exception\AbortNotification $e) {
                    continue;
                }
                $rule->triggerNotification($notification);
            }
        }
        if (!is_null($origAdminLang)) {
            \DI::forgetInstance("adminlang");
            $adminLang = \DI::make("adminlang", array($origAdminLang));
            \AdminLang::swap($adminLang);
        }
    }
}

?>