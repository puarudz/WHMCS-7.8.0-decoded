<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Token;

class Query
{
    private $tokenName = "";
    const DEFAULT_TOKEN_NAME = "TokenQuery";
    public function __construct($name = "")
    {
        if (empty($name)) {
            $name = self::DEFAULT_TOKEN_NAME;
        }
        $this->setTokenName($name);
        return $this;
    }
    public function generateToken()
    {
        return $this->generateRandomAlphanumeric(16);
    }
    public function isValidTokenFormat($token)
    {
        $isValid = false;
        if (is_string($token)) {
            $isValid = preg_match("/^[a-zA-Z0-9]{16}\$/", $token) ? true : false;
        }
        return $isValid;
    }
    public function setTokenValue($token)
    {
        $tokenName = $this->getTokenName();
        $this->setSessionValue($tokenName, $token);
        return $this->getQueryName($token);
    }
    public function getTokenValue()
    {
        $tokenName = $this->getTokenName();
        $token = $this->getSessionValue($tokenName);
        return $token;
    }
    public function getQuery($token = "")
    {
        $query = "";
        if ($token) {
            $tokenQueryName = $this->getQueryName($token);
            $query = $this->getSessionValue($tokenQueryName);
        }
        return $query;
    }
    public function setQuery($token = "", $query = "")
    {
        if ($token) {
            $tokenQueryName = $this->setTokenValue($token);
            $this->setSessionValue($tokenQueryName, $query);
        }
    }
    public function generateRandomAlphanumeric($length = 16)
    {
        mt_srand();
        $values = array_merge(range(65, 90), range(97, 122), range(48, 57));
        $max = count($values) - 1;
        $str = chr(mt_rand(97, 122));
        for ($i = 1; $i < $length; $i++) {
            $str .= chr($values[mt_rand(0, $max)]);
        }
        return $str;
    }
    public function setTokenName($name)
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException(sprintf("Name must be a string", ""));
        }
        $this->tokenName = $name;
        return $this;
    }
    public function getTokenName()
    {
        return $this->tokenName;
    }
    private function getQueryName($token)
    {
        return $this->getTokenName() . "_" . $token;
    }
    private function getSessionValue($key)
    {
        $value = "";
        if (class_exists("WHMCS\\Session")) {
            $value = \WHMCS\Session::get($key);
        } else {
            if (!empty($_SESSION[$key])) {
                $value = $_SESSION[$key];
            }
        }
        return $value;
    }
    private function setSessionValue($key, $value)
    {
        if (class_exists("WHMCS\\Session")) {
            \WHMCS\Session::set($key, $value);
        } else {
            $_SESSION[$key] = $value;
        }
        return $this;
    }
}

?>