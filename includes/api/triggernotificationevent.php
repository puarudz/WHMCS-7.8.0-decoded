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
$identifier = App::getFromRequest("notification_identifier");
$title = App::getFromRequest("title");
$message = App::getFromRequest("message");
$url = App::getFromRequest("url");
$status = App::getFromRequest("status");
$statusStyle = App::getFromRequest("statusStyle");
$notificationAttributes = App::getFromRequest("attributes");
if (!is_array($notificationAttributes)) {
    $notificationAttributes = array();
}
if (!$identifier) {
    $apiresults = array("result" => "error", "message" => "API Notification Events require a identifier string to be passed.");
} else {
    if (!$title) {
        $apiresults = array("result" => "error", "message" => "API Notification Events require a title to be provided.");
    } else {
        if (!$message) {
            $apiresults = array("result" => "error", "message" => "API Notification Events require a message to be provided.");
        } else {
            $parameters = array("identifier" => $identifier, "title" => $title, "message" => $message, "url" => $url, "status" => $status, "statusStyle" => $statusStyle, "attributes" => $notificationAttributes);
            try {
                WHMCS\Notification\Events::trigger(WHMCS\Notification\Events::API, "api_call", $parameters);
            } catch (Exception $e) {
                $apiresults = array("result" => "error", "message" => "Notification failed to send: " . $e->getMessage());
                return NULL;
            }
            $apiresults = array("result" => "success", "message" => "Notification Event Triggered");
        }
    }
}

?>