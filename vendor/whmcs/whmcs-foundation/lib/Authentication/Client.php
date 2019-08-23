<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Authentication;

class Client
{
    protected $user = NULL;
    protected $isMasqueradingAdmin = false;
    protected $verifiedFirstFactorRemotely = false;
    public function __construct($username, $password = "")
    {
        $this->username = $username;
        $this->password = $password;
        if (($admin = $this->getMasqueradingAdmin()) && $admin->isAllowedToMasquerade()) {
            $this->isMasqueradingAdmin = true;
        }
        $this->clearSessionIds();
        if ($user = $this->findUser()) {
            $this->setUser($user);
        }
    }
    public function findUser()
    {
        $username = $this->username;
        $password = $this->password;
        $isMasqueradingAdmin = $this->isMasqueradingAdmin();
        if (!$username || !($password || $isMasqueradingAdmin || \WHMCS\Session::get("2faverifyc"))) {
            return null;
        }
        $clientQuery = \WHMCS\User\Client::where("email", $username);
        if (!$isMasqueradingAdmin) {
            $clientQuery->where("status", "!=", "Closed");
        }
        $desiredUser = $clientQuery->first();
        if (!$desiredUser) {
            $desiredUser = \WHMCS\User\Client\Contact::where("email", $username)->where("subaccount", 1)->where("password", "!=", "")->first();
        }
        if (!$desiredUser) {
            $loginShareData = $this->dispatchLoginShare($username, $password);
            if (!empty($loginShareData["user"])) {
                $this->verifiedFirstFactorRemotely = true;
                $desiredUser = $loginShareData["user"];
            }
        }
        return $desiredUser;
    }
    public function getUser()
    {
        return $this->user;
    }
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }
    public function comparePassword($userProvidedPassword, $storedHash)
    {
        $hasher = new \WHMCS\Security\Hash\Password();
        return $hasher->verify($userProvidedPassword, $storedHash);
    }
    public function isMasqueradingAdmin()
    {
        return $this->isMasqueradingAdmin;
    }
    public function getMasqueradingAdmin()
    {
        return \WHMCS\User\Admin::find((int) \WHMCS\Session::get("adminid"));
    }
    public function clearSessionIds()
    {
        \WHMCS\Session::delete("uid");
        \WHMCS\Session::delete("cid");
        \WHMCS\Session::delete("upw");
    }
    protected function setSessionIds($user)
    {
        $userId = $user->id;
        $contactId = "";
        if ($user instanceof \WHMCS\User\Client\Contact) {
            $userId = $user->client->id;
            $contactId = $user->id;
            \WHMCS\Session::set("cid", $contactId);
        }
        $userSessionHash = static::generateClientLoginHash($userId, $contactId, $user->passwordHash);
        \WHMCS\Session::set("uid", $userId);
        \WHMCS\Session::set("upw", $userSessionHash);
        if (!$this->isMasqueradingAdmin()) {
            if ($language = $user->language) {
                \WHMCS\Session::set("Language", $language);
            }
            $whmcs = \DI::make("app");
            if (\WHMCS\Session::get("2farememberme") || $whmcs->get_req_var("rememberme")) {
                \WHMCS\Cookie::set("User", $userId . ":" . $contactId . ":" . sha1($userSessionHash . $whmcs->get_hash()), \WHMCS\Carbon::now()->addYear()->getTimestamp());
            } else {
                \WHMCS\Cookie::delete("User");
            }
            \WHMCS\Session::delete("2faclientid");
            \WHMCS\Session::delete("2farememberme");
            \WHMCS\Session::delete("2faverifyc");
        }
        set_token(genRandomVal());
        return $this;
    }
    public function finalizeLogin()
    {
        if (!($user = $this->getUser())) {
            return $this;
        }
        if (!$this->isMasqueradingAdmin()) {
            $user->updateLastLogin();
        }
        $this->setSessionIds($user);
        $hookParams = $user instanceof \WHMCS\User\Client\Contact ? array("userid" => $user->clientId, "contactid" => $user->id) : array("userid" => $user->id, "contactid" => 0);
        run_hook("ClientLogin", $hookParams);
        return $this;
    }
    protected function dispatchLoginShare($username, $password)
    {
        $hookResults = run_hook("ClientLoginShare", array("username" => $username, "password" => $password));
        $data = array("matchFound" => false, "newUser" => false, "user" => null);
        foreach ($hookResults as $hookData) {
            if ($hookData) {
                $hookId = $hookData["id"];
                $hookEmail = $hookData["email"];
                if ($hookId) {
                    $tmpClient = \WHMCS\User\Client::findOrNew($hookId);
                } else {
                    $tmpClient = \WHMCS\User\Client::firstOrNew(array("email" => $hookEmail));
                }
                if ($tmpClient->exists) {
                    $data["user"] = $tmpClient;
                    break;
                }
                if ($hookData["create"]) {
                    $tmpClient->firstName = $hookData["firstname"];
                    $tmpClient->lastName = $hookData["lastname"];
                    $tmpClient->companyName = $hookData["companyname"];
                    $tmpClient->email = $hookData["email"];
                    $tmpClient->address1 = $hookData["address1"];
                    $tmpClient->address2 = $hookData["address2"];
                    $tmpClient->city = $hookData["city"];
                    $tmpClient->state = $hookData["state"];
                    $tmpClient->postcode = $hookData["postcode"];
                    $tmpClient->country = $hookData["country"];
                    $tmpClient->phoneNumber = $hookData["phonenumber"];
                    $tmpClient->passwordHash = $hookData["password"];
                    $tmpClient->save();
                    $data["user"] = $tmpClient;
                    break;
                }
            }
        }
        return $data;
    }
    public function prepareSecondFactor()
    {
        if (!$this->isMasqueradingAdmin()) {
            \WHMCS\Session::set("2faverifyc", true);
            \WHMCS\Session::set("2faclientid", $this->getUser()->id);
            \WHMCS\Session::set("2farememberme", \DI::make("app")->get_req_var("rememberme"));
        }
        return $this;
    }
    public static function isInSecondFactorRequestState()
    {
        $twoFactorAuthentication = new \WHMCS\TwoFactorAuthentication();
        return $twoFactorAuthentication->isActiveClients() && isset($_SESSION["2faverifyc"]);
    }
    public function needsSecondFactorToFinalize()
    {
        $twoFactorAuth = new \WHMCS\TwoFactorAuthentication();
        if ($twoFactorAuth->isActiveClients() && $this->getUser()->twoFactorAuthModule && !$this->isMasqueradingAdmin()) {
            return true;
        }
        return false;
    }
    public function verifySecondFactor()
    {
        if ($this->isMasqueradingAdmin()) {
            return true;
        }
        $whmcs = \DI::make("app");
        $twoFactorAuth = new \WHMCS\TwoFactorAuthentication();
        $twoFactorAuth->setClientID($this->getUser()->id);
        if ($whmcs->get_req_var("backupcode")) {
            return $twoFactorAuth->verifyBackupCode($whmcs->get_req_var("code"));
        }
        return $twoFactorAuth->moduleCall("verify");
    }
    public function verifyFirstFactor()
    {
        if (!($user = $this->getUser())) {
            return false;
        }
        $password = $this->password;
        $hasher = new \WHMCS\Security\Hash\Password();
        if ($this->isMasqueradingAdmin() || $this->verifiedFirstFactorRemotely) {
            return true;
        }
        if ($hasher->verify($password, $user->passwordHash)) {
            if ($hasher->needsRehash($user->passwordHash)) {
                $user->passwordHash = $hasher->hash($password);
                $user->save();
            }
            return true;
        }
        if ($user instanceof \WHMCS\User\Client\Contact) {
            logActivity("Failed Login Attempt - User ID: " . $user->clientId . " - Contact ID: " . $user->id, $user->clientId);
        } else {
            logActivity("Failed Login Attempt - User ID: " . $user->id, $user->id);
        }
        return false;
    }
    public static function generateClientLoginHash($userId, $contactId, $passwordHash)
    {
        $whmcs = \DI::make("app");
        $userIp = $whmcs->get_config("DisableSessionIPCheck") ? "" : \WHMCS\Utility\Environment\CurrentUser::getIP();
        $hashSalt = substr(sha1($whmcs->get_hash()), 0, 20);
        $userId = (int) $userId;
        if (empty($contactId)) {
            $contactId = "";
        } else {
            $contactId = (int) $contactId;
        }
        $delimiter = "|";
        return sha1($userId . $delimiter . $contactId . $delimiter . $passwordHash . $delimiter . $userIp . $delimiter . $hashSalt);
    }
}

?>