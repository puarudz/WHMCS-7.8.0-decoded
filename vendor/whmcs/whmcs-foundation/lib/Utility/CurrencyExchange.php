<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Utility;

class CurrencyExchange extends \WHMCS\Config\AbstractConfig
{
    const EXCHANGE_RATE_FEED_URL = "https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml";
    public static function fetchCurrentRates()
    {
        $rawFeed = curlCall(static::EXCHANGE_RATE_FEED_URL, array("CURLOPT_SSL_VERIFYPEER" => true, "CURLOPT_SSL_VERIFYHOST" => 2));
        $rawFeed = explode("\n", $rawFeed);
        $exchangeRates = array();
        $exchangeRates["EUR"] = 1;
        foreach ($rawFeed as $line) {
            $line = trim($line);
            $matchString = "currency='";
            $pos1 = strpos($line, $matchString);
            if ($pos1) {
                $currencySymbol = substr($line, $pos1 + strlen($matchString), 3);
                $matchString = "rate='";
                $pos2 = strpos($line, $matchString);
                $rateString = substr($line, $pos2 + strlen($matchString));
                $pos3 = strpos($rateString, "'");
                $rate = substr($rateString, 0, $pos3);
                $exchangeRates[$currencySymbol] = $rate;
            }
        }
        return new static($exchangeRates);
    }
    public static function factoryFromStoredRates()
    {
        $exchangeRates = array();
        $currencies = \WHMCS\Billing\Currency::all();
        foreach ($currencies as $currency) {
            $exchangeRates[$currency->code] = $currency->rate;
        }
        return new static($exchangeRates);
    }
    public function hasCurrencyCode($code)
    {
        return $this->offsetExists($code);
    }
    public function getUsdExchangeRate($code)
    {
        if (!$this->hasCurrencyCode($code)) {
            throw new \RuntimeException("Exchange rate cannot be calculated. " . "Currency code " . $code . " is unknown by ECB or stored currencies.");
        }
        if (!$this->hasCurrencyCode("USD")) {
            throw new \RuntimeException("Exchange rate cannot be calculated. " . "Currency code USD is unknown by ECB or stored currencies.");
        }
        $userCodeRate = $this->{$code};
        $usdCodeRate = $this->USD;
        $exchangeRate = 0;
        if ($usdCodeRate) {
            $codeRateRatio = $userCodeRate / $usdCodeRate;
            if ($codeRateRatio) {
                $exchangeRate = round(1 / $codeRateRatio, 5);
            }
        }
        return $exchangeRate;
    }
}

?>