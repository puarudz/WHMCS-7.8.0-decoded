<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Security\Hash;

class Password
{
    protected $useHmac = false;
    protected $defaultHashAlgorithm = 1;
    protected $defaultHashOptions = array();
    protected $infoCache = array();
    const HASH_MD5 = "plain-MD5";
    const HASH_SALTED_MD5 = "salted-MD5";
    const HASH_HMAC_SHA256 = "HMAC-SHA256";
    const HASH_BCRYPT = "bcrypt";
    const HASH_UNKNOWN = "unknown";
    const PATTERN_MD5 = "/^[a-f0-9]{32}\$/i";
    const PATTERN_SALTED_MD5 = "/^[a-f0-9]{32}(?::(.+))\$/i";
    const PATTERN_HMAC_SHA256 = "/^[a-f0-9]{64}(?::(.+))\$/i";
    const PATTERN_BCRYPT = "/^(\\\$2[axy]|\\\$2)\\\$[0-9]{0,2}?\\\$([a-z0-9\\/.]{22})[a-z0-9\\/.]{31}\$/i";
    public function __construct($useHmac = false)
    {
        if (!defined("PASSWORD_BCRYPT")) {
            define("PASSWORD_BCRYPT", 1);
        }
        if (!defined("PASSWORD_DEFAULT")) {
            define("PASSWORD_DEFAULT", PASSWORD_BCRYPT);
        }
        $this->defaultHashAlgorithm = PASSWORD_BCRYPT;
        $this->defaultHashOptions = array();
        if (!empty($useHmac) || version_compare(PHP_VERSION, "5.3.7", "<")) {
            $this->useHmac = true;
        }
    }
    public function verify($input, $storedHash)
    {
        $info = $this->getInfo($storedHash);
        switch ($info["algoName"]) {
            case self::HASH_MD5:
                $result = $this->verifyMd5($input, $storedHash);
                break;
            case self::HASH_SALTED_MD5:
                $result = $this->verifySaltedMd5($input, $storedHash);
                break;
            case self::HASH_HMAC_SHA256:
                $result = $this->verifyHmacSha256($input, $storedHash);
                break;
            case self::HASH_BCRYPT:
                $result = password_verify($input, $storedHash);
                break;
            default:
                throw new \RuntimeException(sprintf("Calculated algorithm \"%s\" is not supported", $info["algoName"]));
        }
        return $result;
    }
    public function hash($input, $algorithm = "", $options = array())
    {
        if ($this->useHmac || $algorithm == self::HASH_HMAC_SHA256) {
            return $this->hmacHash($input);
        }
        if (!($algorithm || in_array($algorithm, array(self::HASH_BCRYPT)))) {
            $algorithm = $this->defaultHashAlgorithm;
        }
        if (empty($options)) {
            $options = $this->defaultHashOptions;
        }
        return password_hash($input, $algorithm, $options);
    }
    public function getInfo($hash)
    {
        if (isset($this->infoCache[$hash])) {
            return $this->infoCache[$hash];
        }
        $info = array("algo" => 0, "algoName" => self::HASH_UNKNOWN, "options" => array());
        if (strpos($hash, "\$") === 0 && $this->useHmac == false) {
            $info = password_get_info($hash);
        } else {
            $matches = array();
            if (preg_match(self::PATTERN_HMAC_SHA256, $hash, $matches)) {
                $info["algoName"] = self::HASH_HMAC_SHA256;
                $info["options"]["salt"] = $matches[1];
            } else {
                if (preg_match(self::PATTERN_SALTED_MD5, $hash)) {
                    $info["algoName"] = self::HASH_SALTED_MD5;
                    $info["options"]["salt"] = $matches[1];
                } else {
                    if (preg_match(self::PATTERN_MD5, $hash)) {
                        $info["algoName"] = self::HASH_MD5;
                    } else {
                        if (preg_match(self::PATTERN_BCRYPT, $hash, $matches)) {
                            $info["algoName"] = self::HASH_BCRYPT;
                            $info["options"]["salt"] = $matches[2];
                        }
                    }
                }
            }
        }
        $this->infoCache[$hash] = $info;
        return $info;
    }
    public function needsRehash($hash, $algorithm = "", $options = array())
    {
        $info = $this->getInfo($hash);
        switch ($info["algoName"]) {
            case self::HASH_MD5:
                $result = true;
                break;
            case self::HASH_SALTED_MD5:
                $result = true;
                break;
            case self::HASH_HMAC_SHA256:
                if ($algorithm == self::HASH_HMAC_SHA256) {
                    return false;
                }
                if (empty($algorithm)) {
                    $result = $this->useHmac ? false : true;
                } else {
                    $result = true;
                }
                break;
            case self::HASH_BCRYPT:
                if (!$algorithm) {
                    $algorithm = $this->defaultHashAlgorithm;
                }
                if (empty($options)) {
                    $options = $this->defaultHashOptions;
                }
                if ($this->useHmac) {
                    $result = true;
                } else {
                    $result = password_needs_rehash($hash, $algorithm, $options);
                }
                break;
            default:
                throw new \RuntimeException(sprintf("Calculated algorithm \"%s\" is not supported", $info["algoName"]));
        }
        return $result;
    }
    protected function hmacHash($input, $key = "")
    {
        if (!$key) {
            $key = bin2hex(\phpseclib\Crypt\Random::string(16));
        }
        $hasher = new \phpseclib\Crypt\Hash("sha256");
        $hasher->setKey($key);
        $hashedInput = $hasher->hash($input);
        if (empty($hashedInput)) {
            return false;
        }
        return bin2hex($hashedInput) . ":" . $key;
    }
    protected function verifyHmacSha256($input, $storedHash)
    {
        list($hashSecret, $hashKey) = explode(":", $storedHash);
        $hashedInput = $this->hmacHash($input, $hashKey);
        return $this->assertBinarySameness($hashedInput, $storedHash);
    }
    protected function verifyMd5($input, $storedHash)
    {
        return $this->assertBinarySameness(md5($input), $storedHash);
    }
    protected function verifySaltedMd5($input, $storedHash)
    {
        list($hash, $salt) = explode(":", $storedHash);
        return $this->assertBinarySameness(md5($salt . $input) . ":" . $salt, $storedHash);
    }
    public function assertBinarySameness($hashedInput, $storedHash)
    {
        if (!is_string($hashedInput) || !is_string($storedHash) || \WHMCS\Utility\Binary::strlen($hashedInput) != \WHMCS\Utility\Binary::strlen($storedHash) || \WHMCS\Utility\Binary::strlen($hashedInput) <= 16) {
            return false;
        }
        $status = 0;
        for ($i = 0; $i < \WHMCS\Utility\Binary::strlen($hashedInput); $i++) {
            $status |= ord($hashedInput[$i]) ^ ord($storedHash[$i]);
        }
        return $status === 0 ? true : false;
    }
}

?>