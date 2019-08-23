<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\View\Markup\Bbcode;

class Bbcode
{
    public static function transform($text)
    {
        $bbCodeMap = array("b" => "strong", "i" => "em", "u" => "ul", "div" => "div");
        $text = preg_replace("/\\[div=(&quot;|\")(.*?)(&quot;|\")\\]/", "<div class=\"\$2\">", $text);
        foreach ($bbCodeMap as $bbCode => $htmlCode) {
            $text = str_replace("[" . $bbCode . "]", "<" . $htmlCode . ">", $text);
            $text = str_replace("[/" . $bbCode . "]", "</" . $htmlCode . ">", $text);
        }
        return $text;
    }
}

?>