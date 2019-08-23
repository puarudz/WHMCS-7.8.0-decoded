<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Input;

class Sanitize
{
    public static function makeSafeForOutput($val)
    {
        $input = new Sanitize();
        $val = $input->decode($val);
        return $input->encode($val);
    }
    public static function convertToCompatHtml($val)
    {
        $input = new Sanitize();
        $val = $input->decode($val);
        $val = $input->decode($val);
        return $input->encodeToCompatHTML($val);
    }
    public static function encode($val)
    {
        $input = new Sanitize();
        if (is_bool($val)) {
            return $val;
        }
        if (is_numeric($val)) {
            return $val;
        }
        if (is_string($val)) {
            return $input->encodeString($val);
        }
        if (is_array($val)) {
            return $input->encodeArray($val);
        }
        if (is_object($val)) {
            return $val;
        }
        return "";
    }
    public static function encodeToCompatHTML($val)
    {
        $input = new Sanitize();
        if (is_bool($val)) {
            return $val;
        }
        if (is_numeric($val)) {
            return $val;
        }
        if (is_string($val)) {
            return $input->encodeStringToCompatHTML($val);
        }
        if (is_array($val)) {
            return $input->encodeArrayToCompatHTML($val);
        }
        if (is_object($val)) {
            return $val;
        }
        return "";
    }
    public static function decode($val)
    {
        $input = new Sanitize();
        if (is_bool($val)) {
            return $val;
        }
        if (is_numeric($val)) {
            return $val;
        }
        if (is_string($val)) {
            return $input->decodeString($val);
        }
        if (is_array($val)) {
            return $input->decodeArray($val);
        }
        if (is_object($val)) {
            return $val;
        }
        return "";
    }
    protected function encodeArray($array)
    {
        foreach ($array as $k => $v) {
            $array[$k] = $this->encode($v);
        }
        return $array;
    }
    protected function encodeArrayToCompatHTML($array)
    {
        foreach ($array as $k => $v) {
            $array[$k] = $this->encodeToCompatHTML($v);
        }
        return $array;
    }
    protected function decodeArray($array)
    {
        foreach ($array as $k => $v) {
            $array[$k] = $this->decode($v);
        }
        return $array;
    }
    protected function encodeString($val)
    {
        return htmlspecialchars($val, ENT_QUOTES);
    }
    protected function encodeStringToCompatHTML($val)
    {
        static $mask = NULL;
        if (!isset($mask)) {
            $mask = $this->getCompatBitmask();
        }
        return htmlspecialchars($val, $mask);
    }
    public function getCompatBitmask()
    {
        $mask = ENT_COMPAT;
        if (defined("ENT_HTML401")) {
            $mask = $mask | ENT_HTML401;
        }
        return $mask;
    }
    protected function decodeString($val)
    {
        $val = str_replace("&nbsp;", " ", $val);
        return html_entity_decode($val, ENT_QUOTES);
    }
    public static function maskEmailVerificationId($message)
    {
        $mask = "verificationId=%2A";
        $regex = "%verificationId=[0-9a-f]{40}%i";
        $maskedMessage = preg_replace($regex, $mask, $message);
        return $maskedMessage;
    }
    public static function escapeSingleQuotedString($content)
    {
        $content = str_replace("\\", "\\\\", $content);
        $content = str_replace("'", "\\'", $content);
        return $content;
    }
    public static function stripTags($val, $allowedTags = "")
    {
        $input = new Sanitize();
        if (is_bool($val)) {
            return $val;
        }
        if (is_numeric($val)) {
            return $val;
        }
        if (is_string($val)) {
            return $input->stripTagsFromString($val, $allowedTags);
        }
        if (is_array($val)) {
            return $input->stripTagsFromArray($val, $allowedTags);
        }
        if (is_object($val)) {
            return $val;
        }
        return "";
    }
    protected function stripTagsFromString($val, $allowedTags)
    {
        $val = $this->decodeString($val);
        $val = strip_tags($val, $allowedTags);
        return $this->encodeString($val);
    }
    protected function stripTagsFromArray(array $array, $allowedTags)
    {
        foreach ($array as $k => $v) {
            $array[$k] = $this->stripTags($v, $allowedTags);
        }
        return $array;
    }
}

?>