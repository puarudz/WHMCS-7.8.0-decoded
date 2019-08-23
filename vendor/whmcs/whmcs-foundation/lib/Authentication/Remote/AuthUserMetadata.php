<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Authentication\Remote;

class AuthUserMetadata
{
    protected $fullName = NULL;
    protected $emailAddress = NULL;
    protected $username = NULL;
    protected $providerName = NULL;
    public function __construct($fullName = "", $emailAddress = "", $username = "", $providerName = "")
    {
        if ($fullName) {
            $this->fullName = $fullName;
        }
        if ($emailAddress) {
            $this->emailAddress = $emailAddress;
        }
        if ($username) {
            $this->username = $username;
        }
        if ($providerName) {
            $this->providerName = $providerName;
        }
        return $this;
    }
    public function getFullName()
    {
        return $this->fullName;
    }
    public function setFullName($name)
    {
        $this->fullName = $name;
        return $this;
    }
    public function getEmailAddress()
    {
        return $this->emailAddress;
    }
    public function setEmailAddress($email)
    {
        $this->emailAddress = $email;
        return $this;
    }
    public function getUsername()
    {
        return $this->username;
    }
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }
    public function getProviderName()
    {
        return $this->providerName;
    }
    public function setProviderName($provider)
    {
        $this->providerName = $provider;
        return $this;
    }
}

?>