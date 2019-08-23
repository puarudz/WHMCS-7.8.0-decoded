<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Notification\Events;

class API
{
    const DISPLAY_NAME = "API";
    public function getEvents()
    {
        return array("api_call" => array("label" => "Custom API Trigger", "hook" => array()));
    }
    public function getConditions()
    {
        return array("identifier" => array("FriendlyName" => "Trigger Identifier", "Type" => "text"));
    }
    public function evaluateConditions($event, $conditions, $hookParameters)
    {
        if ($conditions["identifier_filter"] && $conditions["identifier"]) {
            if ($conditions["identifier_filter"] == "exact") {
                if ($conditions["identifier"] != $hookParameters["identifier"]) {
                    return false;
                }
            } else {
                if (strpos($hookParameters["identifier"], $conditions["identifier"]) === false) {
                    return false;
                }
            }
        }
        return true;
    }
    public function buildNotification($event, $hookParameters)
    {
        if (isset($hookParameters["title"])) {
            $title = $hookParameters["title"];
            if (isset($hookParameters["message"])) {
                $message = $hookParameters["message"];
                $url = "";
                if (isset($hookParameters["url"])) {
                    $url = $hookParameters["url"];
                }
                if (isset($hookParameters["status"])) {
                    $status = $hookParameters["status"];
                }
                if (isset($hookParameters["statusStyle"])) {
                    $statusStyle = $hookParameters["statusStyle"];
                }
                $notification = (new \WHMCS\Notification\Notification())->setTitle($title)->setMessage($message)->setUrl($url);
                if ($status && $statusStyle) {
                    $notification->addAttribute((new \WHMCS\Notification\NotificationAttribute())->setLabel(\AdminLang::trans("fields.status"))->setValue($status)->setStyle($statusStyle));
                }
                if (isset($hookParameters["attributes"]) && is_array($hookParameters["attributes"])) {
                    foreach ($hookParameters["attributes"] as $attribute) {
                        $notification->addAttribute((new \WHMCS\Notification\NotificationAttribute())->setIcon($attribute["icon"])->setLabel($attribute["label"])->setStyle($attribute["style"])->setUrl($attribute["url"])->setValue($attribute["value"]));
                    }
                }
                return $notification;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}

?>