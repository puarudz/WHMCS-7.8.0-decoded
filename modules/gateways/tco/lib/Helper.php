<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Gateway\TCO;

class Helper
{
    protected static $languages = array("chinese" => "zh", "danish" => "da", "dutch" => "nl", "french" => "fr", "german" => "gr", "greek" => "el", "italian" => "it", "japanese" => "jp", "norwegian" => "no", "portuguese" => "pt", "slovenian" => "sl", "spanish" => "es_la", "swedish" => "sv", "english" => "en");
    public static function convertCurrency($amount, \WHMCS\Billing\Currency $currency, \WHMCS\Billing\Invoice $invoice)
    {
        return \WHMCS\Billing\Invoice\Helper::convertCurrency($amount, $currency, $invoice);
    }
    public static function language($language)
    {
        $language = strtolower($language);
        $tcoLanguage = "";
        if (array_key_exists($language, self::$languages)) {
            $tcoLanguage = self::$languages[$language];
        }
        return $tcoLanguage;
    }
    public static function languageInput($language)
    {
        $tcoLanguage = self::language($language);
        if ($tcoLanguage) {
            $tcoLanguage = "<input type=\"hidden\" name=\"lang\" value=\"" . $tcoLanguage . "\">";
        }
        return $tcoLanguage;
    }
}

?>