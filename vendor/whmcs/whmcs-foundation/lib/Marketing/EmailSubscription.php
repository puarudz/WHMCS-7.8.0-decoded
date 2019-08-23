<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Marketing;

class EmailSubscription
{
    const ACTION_OPTIN = "optin";
    const ACTION_OPTOUT = "optout";
    public static function isUsingOptInField()
    {
        return \WHMCS\Config\Setting::getValue("MarketingEmailConvert");
    }
    public function generateOptInUrl($userId, $email)
    {
        return $this->generateOptInOutUrl(self::ACTION_OPTIN, $userId, $email);
    }
    public function generateOptOutUrl($userId, $email)
    {
        return $this->generateOptInOutUrl(self::ACTION_OPTOUT, $userId, $email);
    }
    protected function generateOptInOutUrl($action, $userId, $email)
    {
        $url = fqdnRoutePath("subscription-manage");
        if (strpos($url, "?") === false) {
            $url .= "?";
        } else {
            $url .= "&";
        }
        return $url . "action=" . $action . "&email=" . urlencode($email) . "&key=" . $this->generateKey($userId, $email, $action);
    }
    public function generateKey($userId, $email, $action)
    {
        if ($action == self::ACTION_OPTOUT) {
            $action = "";
        } else {
            $action = self::ACTION_OPTIN;
        }
        return sha1($action . $email . $userId . \App::get_hash());
    }
    public function validateKey(\WHMCS\User\Client $client, $action, $key)
    {
        if ($key != $this->generateKey($client->id, $client->email, $action)) {
            throw new \WHMCS\Exception\Validation\InvalidValue("Invalid key");
        }
    }
}

?>