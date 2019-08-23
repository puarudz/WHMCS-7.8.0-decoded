<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Notification\Hipchat;

class Hipchat implements \WHMCS\Module\Contracts\NotificationModuleInterface
{
    use \WHMCS\Module\Notification\DescriptionTrait;
    const HOSTED_API_URL = "https://api.hipchat.com/v2/";
    public function __construct()
    {
        $this->setDisplayName("HipChat")->setLogoFileName("logo.png");
    }
    public function settings()
    {
        return array("api_token" => array("FriendlyName" => "API Token", "Type" => "text", "Description" => "Requires a HipChat API V2 User Token. To create one, navigate to Account Settings > API Access. The token requires the View Room and Send Notification scopes."), "api_url" => array("FriendlyName" => "API URL", "Type" => "text", "Placeholder" => static::HOSTED_API_URL, "Description" => "If using HipChat Server Edition, enter the URL to your self-hosted Hipchat Instance."));
    }
    public function testConnection($settings)
    {
        $this->call($settings, "room?start-index=0&max-results=1");
    }
    public function notificationSettings()
    {
        return array("room" => array("FriendlyName" => "Room", "Type" => "dynamic", "Description" => "Select the desired room for a notification.", "Required" => true), "message" => array("FriendlyName" => "Customise Message", "Type" => "text", "Description" => "Allows you to customise the primary display message shown in the notification."), "notify" => array("FriendlyName" => "User Notification", "Type" => "yesno", "Description" => "This can include playing a sound, a mobile notification and more based on your notification settings."));
    }
    public function getDynamicField($fieldName, $settings)
    {
        if ($fieldName == "room") {
            $response = $this->call($settings, "room?start-index=0&max-results=1000");
            $rooms = array();
            foreach ($response["items"] as $room) {
                $rooms[] = array("id" => $room["id"], "name" => $room["name"], "description" => "Room ID");
            }
            return array("values" => $rooms);
        } else {
            return array();
        }
    }
    public function sendNotification(\WHMCS\Notification\Contracts\NotificationInterface $notification, $moduleSettings, $notificationSettings)
    {
        $messageBody = $notification->getMessage();
        if ($notificationSettings["message"]) {
            $messageBody = $notificationSettings["message"];
        }
        $card = (new Card())->title(\WHMCS\Input\Sanitize::decode($notification->getTitle()))->style("application")->url($notification->getUrl())->message($messageBody)->icon(\App::getSystemUrl() . "assets/img/notification-icon.png");
        foreach ($notification->getAttributes() as $attribute) {
            $card->addAttribute((new CardAttribute())->label($attribute->getLabel())->value($attribute->getValue())->url($attribute->getUrl())->style($attribute->getStyle())->icon($attribute->getIcon()));
        }
        $message = (new Message())->message(\WHMCS\Input\Sanitize::decode($notification->getTitle()) . "<br>" . $messageBody . "<br>" . "<a href=\"" . $notification->getUrl() . "\">" . $notification->getUrl() . "</a>")->notify((bool) $notificationSettings["notify"])->card($card);
        $room = $notificationSettings["room"];
        $room = explode("|", $room, 2);
        $roomId = $room[0];
        $this->call($moduleSettings, "room/" . $roomId . "/notification", $message->toArray());
    }
    protected function call($settings, $uri, $postdata = NULL)
    {
        $url = $settings["api_url"];
        $token = $settings["api_token"];
        if (!$url) {
            $url = static::HOSTED_API_URL;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url . $uri);
        if ($postdata) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata));
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Authorization: Bearer " . $token));
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $decoded = json_decode($response, true);
        logModuleCall("hipchat", $uri, $postdata, $response, $decoded);
        if (substr($httpcode, 0, 1) != 2) {
            if (isset($decoded["error"])) {
                if (isset($decoded["error"]["code"])) {
                    if ($decoded["error"]["code"] == "401") {
                        throw new \WHMCS\Exception("API Token is invalid. Please check your entry and ensure it has the View Room and Send Notification scopes authorized.");
                    }
                    $errorMsg = $decoded["error"]["code"] . " - " . $decoded["error"]["message"];
                } else {
                    $errorMsg = "The following error occurred: " . json_encode($decoded["error"]);
                }
            } else {
                $errorMsg = "An unknown error occurred: " . $response;
            }
            throw new \WHMCS\Exception($errorMsg);
        }
        return $decoded;
    }
}

?>