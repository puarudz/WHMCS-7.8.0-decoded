<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Filter;

class Json
{
    private static $maxLength = 65536;
    public static function safeDecode($content, $assoc = false, $depth = 512, $options = 0)
    {
        if (self::$maxLength < strlen($content)) {
            if (defined("JSON_THROW_ON_ERROR") && class_exists("\\JsonException") && $options & JSON_THROW_ON_ERROR) {
                throw new \JsonException("JSON content too long");
            }
            return null;
        }
        return json_decode($content, $assoc, $depth, $options);
    }
}

?>