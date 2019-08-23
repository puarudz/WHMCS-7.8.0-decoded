<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Billing\Invoice;

class Helper
{
    public static function convertCurrency($amount, \WHMCS\Billing\Currency $currency, \WHMCS\Billing\Invoice $invoice)
    {
        $userCurrency = $invoice->client->currencyrel;
        if ($userCurrency->id != $currency->id) {
            $amount = convertCurrency($amount, $currency->id, $userCurrency->id);
            if ($invoice->total < $amount + 1 && $amount - 1 < $invoice->total) {
                $amount = $invoice->total;
            }
        }
        return $amount;
    }
}

?>