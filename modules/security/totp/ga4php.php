<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

abstract class GoogleAuthenticator
{
    private $getDatafunction = NULL;
    private $putDatafunction = NULL;
    private $errorText = NULL;
    private $errorCode = NULL;
    private $hotpSkew = NULL;
    private $totpSkew = NULL;
    private $hotpHuntValue = NULL;
    public function __construct($totpskew = 1, $hotpskew = 10, $hotphuntvalue = 200000)
    {
        $this->hotpSkew = $hotpskew;
        $this->totpSkew = $totpskew;
        $this->hotpHuntValue = $hotphuntvalue;
    }
    public abstract function getData($username);
    public abstract function putData($username, $data);
    public abstract function getUsers();
    public function createEmptyData()
    {
        $data["tokenkey"] = "";
        $data["tokentype"] = "HOTP";
        $data["tokentimer"] = 30;
        $data["tokencounter"] = 1;
        $data["tokenalgorithm"] = "SHA1";
        $data["user"] = "";
        return $data;
    }
    public function internalGetData($username)
    {
        $data = $this->getData($username);
        $deco = safe_unserialize(base64_decode($data));
        if (!$deco) {
            $deco = $this->createEmptyData();
        }
        return $deco;
    }
    public function internalPutData($username, $data)
    {
        if ($data == "") {
            $enco = "";
        } else {
            $enco = base64_encode(safe_serialize($data));
        }
        return $this->putData($username, $enco);
    }
    public function setTokenType($username, $tokentype)
    {
        $tokentype = strtoupper($tokentype);
        if ($tokentype != "HOTP" && $tokentype != "TOTP") {
            $errorText = "Invalid Token Type";
            return false;
        }
        $data = $this->internalGetData($username);
        $data["tokentype"] = $tokentype;
        return $this->internalPutData($username, $data);
    }
    public function setUser($username, $ttype = "HOTP", $key = "", $hexkey = "")
    {
        $ttype = strtoupper($ttype);
        if ($ttype != "HOTP" && $ttype != "TOTP") {
            return false;
        }
        if ($key == "") {
            $key = $this->createBase32Key();
        }
        $hkey = $this->helperb322hex($key);
        if ($hexkey != "") {
            $hkey = $hexkey;
        }
        $token = $this->internalGetData($username);
        $token["tokenkey"] = $hkey;
        $token["tokentype"] = $ttype;
        if (!$this->internalPutData($username, $token)) {
            return false;
        }
        return $key;
    }
    public function hasToken($username)
    {
        $token = $this->internalGetData($username);
        if (!isset($token["tokenkey"])) {
            return false;
        }
        if ($token["tokenkey"] == "") {
            return false;
        }
        return true;
    }
    public function setUserKey($username, $key)
    {
        $token = $this->internalGetData($username);
        $token["tokenkey"] = $key;
        $this->internalPutData($username, $token);
        return true;
    }
    public function deleteUser($username)
    {
        $this->internalPutData($username, "");
    }
    public function authenticateUser($username, $code)
    {
        if (preg_match("/[0-9][0-9][0-9][0-9][0-9][0-9]/", $code) < 1) {
            return false;
        }
        $tokendata = $this->internalGetData($username);
        if ($tokendata["tokenkey"] == "") {
            $errorText = "No Assigned Token";
            return false;
        }
        $ttype = $tokendata["tokentype"];
        $tlid = $tokendata["tokencounter"];
        $tkey = $tokendata["tokenkey"];
        switch ($ttype) {
            case "HOTP":
                $st = $tlid + 1;
                $en = $tlid + $this->hotpSkew;
                for ($i = $st; $i < $en; $i++) {
                    $stest = $this->oath_hotp($tkey, $i);
                    if ($code == $stest) {
                        $tokendata["tokencounter"] = $i;
                        $this->internalPutData($username, $tokendata);
                        return true;
                    }
                }
                return false;
            case "TOTP":
                $t_now = time();
                $t_ear = $t_now - $this->totpSkew * $tokendata["tokentimer"];
                $t_lat = $t_now + $this->totpSkew * $tokendata["tokentimer"];
                $t_st = (int) ($t_ear / $tokendata["tokentimer"]);
                $t_en = (int) ($t_lat / $tokendata["tokentimer"]);
                for ($i = $t_st; $i <= $t_en; $i++) {
                    $stest = $this->oath_hotp($tkey, $i);
                    if ($code == $stest) {
                        return true;
                    }
                }
                break;
            default:
                return false;
        }
        return false;
    }
    public function resyncCode($username, $code1, $code2)
    {
        $tokendata = internalGetData($username);
        $ttype = $tokendata["tokentype"];
        $tlid = $tokendata["tokencounter"];
        $tkey = $tokendata["tokenkey"];
        if ($tkey == "") {
            $this->errorText = "No Assigned Token";
            return false;
        }
        switch ($ttype) {
            case "HOTP":
                $st = 0;
                $en = $this->hotpHuntValue;
                for ($i = $st; $i < $en; $i++) {
                    $stest = $this->oath_hotp($tkey, $i);
                    if ($code1 == $stest) {
                        $stest2 = $this->oath_hotp($tkey, $i + 1);
                        if ($code2 == $stest2) {
                            $tokendata["tokencounter"] = $i + 1;
                            internalPutData($username, $tokendata);
                            return true;
                        }
                    }
                }
                return false;
            case "TOTP":
                break;
            default:
                echo "how the frig did i end up here?";
        }
        return false;
    }
    public function getErrorText()
    {
        return $this->errorText;
    }
    public function createURL($user)
    {
        $data = $this->internalGetData($user);
        $toktype = $data["tokentype"];
        $key = $this->helperhex2b32($data["tokenkey"]);
        $counter = $data["tokencounter"] + 1;
        $toktype = strtolower($toktype);
        if ($toktype == "hotp") {
            $url = "otpauth://" . $toktype . "/" . $user . "?secret=" . $key . "&counter=" . $counter;
        } else {
            $url = "otpauth://" . $toktype . "/" . $user . "?secret=" . $key;
        }
        return $url;
    }
    public function createBase32Key()
    {
        $alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ234567";
        $key = "";
        for ($i = 0; $i < 16; $i++) {
            $offset = rand(0, strlen($alphabet) - 1);
            $key .= $alphabet[$offset];
        }
        return $key;
    }
    public function getKey($username)
    {
        $data = $this->internalGetData($username);
        $key = $data["tokenkey"];
        return $key;
    }
    public function getTokenType($username)
    {
        $data = $this->internalGetData($username);
        $toktype = $data["tokentype"];
        return $toktype;
    }
    public function helperb322hex($b32)
    {
        $alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ234567";
        $out = "";
        $dous = "";
        for ($i = 0; $i < strlen($b32); $i++) {
            $in = strrpos($alphabet, $b32[$i]);
            $b = str_pad(base_convert($in, 10, 2), 5, "0", STR_PAD_LEFT);
            $out .= $b;
            $dous .= $b . ".";
        }
        $ar = str_split($out, 20);
        $out2 = "";
        foreach ($ar as $val) {
            $rv = str_pad(base_convert($val, 2, 16), 5, "0", STR_PAD_LEFT);
            $out2 .= $rv;
        }
        return $out2;
    }
    public function helperhex2b32($hex)
    {
        $alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ234567";
        $ar = str_split($hex, 5);
        $out = "";
        foreach ($ar as $var) {
            $bc = base_convert($var, 16, 2);
            $bin = str_pad($bc, 20, "0", STR_PAD_LEFT);
            $out .= $bin;
        }
        $out2 = "";
        $ar2 = str_split($out, 5);
        foreach ($ar2 as $var2) {
            $bc = base_convert($var2, 2, 10);
            $out2 .= $alphabet[$bc];
        }
        return $out2;
    }
    public function oath_hotp($key, $counter)
    {
        $key = pack("H*", $key);
        $cur_counter = array(0, 0, 0, 0, 0, 0, 0, 0);
        for ($i = 7; 0 <= $i; $i--) {
            $cur_counter[$i] = pack("C*", $counter);
            $counter = $counter >> 8;
        }
        $bin_counter = implode($cur_counter);
        if (strlen($bin_counter) < 8) {
            $bin_counter = str_repeat(chr(0), 8 - strlen($bin_counter)) . $bin_counter;
        }
        $hash = hash_hmac("sha1", $bin_counter, $key);
        return str_pad($this->oath_truncate($hash), 6, "0", STR_PAD_LEFT);
    }
    public function oath_truncate($hash, $length = 6)
    {
        foreach (str_split($hash, 2) as $hex) {
            $hmac_result[] = hexdec($hex);
        }
        $offset = $hmac_result[19] & 15;
        return (($hmac_result[$offset + 0] & 127) << 24 | ($hmac_result[$offset + 1] & 255) << 16 | ($hmac_result[$offset + 2] & 255) << 8 | $hmac_result[$offset + 3] & 255) % pow(10, $length);
    }
}

?>