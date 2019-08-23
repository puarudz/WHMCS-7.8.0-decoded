<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function currencyUpdateRates(WHMCS\Scheduling\Task\TaskInterface $task = NULL)
{
    $stuff = curlCall(WHMCS\Utility\CurrencyExchange::EXCHANGE_RATE_FEED_URL, array("CURLOPT_SSL_VERIFYPEER" => true, "CURLOPT_SSL_VERIFYHOST" => 2));
    $stuff = explode("\n", $stuff);
    $exchrate = array();
    $exchrate["EUR"] = 1;
    foreach ($stuff as $line) {
        $line = trim($line);
        $matchstr = "currency='";
        $pos1 = strpos($line, $matchstr);
        if ($pos1) {
            $currencysymbol = substr($line, $pos1 + strlen($matchstr), 3);
            $matchstr = "rate='";
            $pos2 = strpos($line, $matchstr);
            $ratestr = substr($line, $pos2 + strlen($matchstr));
            $pos3 = strpos($ratestr, "'");
            $rate = substr($ratestr, 0, $pos3);
            $exchrate[$currencysymbol] = $rate;
        }
    }
    $responses = run_hook("FetchCurrencyExchangeRates", $exchrate);
    foreach ($responses as $response) {
        if (is_array($response)) {
            foreach ($response as $currencyCode => $rate) {
                $exchrate[$currencyCode] = $rate;
            }
        }
    }
    $result = select_query("tblcurrencies", "", array("`default`" => "1"));
    $data = mysql_fetch_array($result);
    $currencycode = $data["code"];
    $baserate = isset($exchrate[$currencycode]) ? $exchrate[$currencycode] : "";
    $return = "";
    $result = select_query("tblcurrencies", "", array("`default`" => array("sqltype" => "NEQ", "value" => "1")), "code", "ASC");
    $updatedFailed = 0;
    while ($data = mysql_fetch_array($result)) {
        $id = $data["id"];
        $code = $data["code"];
        $coderate = $exchrate[$code];
        $exchangerate = 0;
        if ($coderate) {
            $codeRateRatio = $baserate / $coderate;
            if ($codeRateRatio) {
                $exchangerate = round(1 / $codeRateRatio, 5);
            }
        }
        if (0 < $exchangerate) {
            update_query("tblcurrencies", array("rate" => $exchangerate), array("id" => $id));
            if ($task) {
                logActivity("Updated " . $code . " Exchange Rate to " . $exchangerate);
            }
            $return .= "Updated " . $code . " Exchange Rate to " . $exchangerate . "<br />";
        } else {
            if ($task) {
                $updatedFailed++;
                logActivity("Update Failed for " . $code . " Exchange Rate");
            }
            $return .= "Update Failed for " . $code . " Exchange Rate<br />";
        }
    }
    if ($task) {
        if ($updatedFailed) {
            $task->output("updated")->write(0);
        } else {
            $task->output("updated")->write(1);
        }
    }
    return $return;
}
function currencyUpdatePricing($currencyid = "")
{
    $result = select_query("tblcurrencies", "id", array("`default`" => "1"));
    $data = mysql_fetch_array($result);
    $defaultcurrencyid = $data["id"];
    $where = array();
    $where["id"] = array("sqltype" => "NEQ", "value" => $defaultcurrencyid);
    if ($currencyid) {
        $where["id"] = $currencyid;
    }
    $currencies = array();
    $result = select_query("tblcurrencies", "id,rate", $where);
    while ($data = mysql_fetch_array($result)) {
        $currencies[$data["id"]] = $data["rate"];
    }
    $result = select_query("tblpricing", "", array("currency" => $defaultcurrencyid));
    while ($data = mysql_fetch_array($result)) {
        $type = $data["type"];
        $relid = $data["relid"];
        $msetupfee = $data["msetupfee"];
        $qsetupfee = $data["qsetupfee"];
        $ssetupfee = $data["ssetupfee"];
        $asetupfee = $data["asetupfee"];
        $bsetupfee = $data["bsetupfee"];
        $tsetupfee = $data["tsetupfee"];
        $monthly = $data["monthly"];
        $quarterly = $data["quarterly"];
        $semiannually = $data["semiannually"];
        $annually = $data["annually"];
        $biennially = $data["biennially"];
        $triennially = $data["triennially"];
        if (in_array($type, array("domainregister", "domaintransfer", "domainrenew"))) {
            $domaintype = true;
        } else {
            $domaintype = false;
        }
        if ($type == "configoptions") {
            $negativePriceAllowed = true;
        } else {
            $negativePriceAllowed = false;
        }
        foreach ($currencies as $id => $rate) {
            if ($rate <= 0) {
                continue;
            }
            if ($domaintype) {
                $result2 = select_query("tblpricing", "id", array("type" => $type, "currency" => $id, "relid" => $relid, "tsetupfee" => $tsetupfee));
            } else {
                $result2 = select_query("tblpricing", "id", array("type" => $type, "currency" => $id, "relid" => $relid));
            }
            $data = mysql_fetch_array($result2);
            $pricing_id = $data["id"];
            if (!$pricing_id) {
                $pricing_id = insert_query("tblpricing", array("type" => $type, "currency" => $id, "relid" => $relid, "tsetupfee" => $tsetupfee));
            }
            if ($negativePriceAllowed) {
                $update_msetupfee = round($msetupfee * $rate, 2);
                $update_qsetupfee = round($qsetupfee * $rate, 2);
                $update_ssetupfee = round($ssetupfee * $rate, 2);
                $update_asetupfee = round($asetupfee * $rate, 2);
                $update_bsetupfee = round($bsetupfee * $rate, 2);
            } else {
                $update_msetupfee = 0 < $msetupfee ? round($msetupfee * $rate, 2) : $msetupfee;
                $update_qsetupfee = 0 < $qsetupfee ? round($qsetupfee * $rate, 2) : $qsetupfee;
                $update_ssetupfee = 0 < $ssetupfee ? round($ssetupfee * $rate, 2) : $ssetupfee;
                $update_asetupfee = 0 < $asetupfee ? round($asetupfee * $rate, 2) : $asetupfee;
                $update_bsetupfee = 0 < $bsetupfee ? round($bsetupfee * $rate, 2) : $bsetupfee;
            }
            if ($domaintype) {
                $update_tsetupfee = $tsetupfee;
            } else {
                $update_tsetupfee = 0 < $tsetupfee ? round($tsetupfee * $rate, 2) : $tsetupfee;
            }
            if ($negativePriceAllowed) {
                $update_monthly = round($monthly * $rate, 2);
                $update_quarterly = round($quarterly * $rate, 2);
                $update_semiannually = round($semiannually * $rate, 2);
                $update_annually = round($annually * $rate, 2);
                $update_biennially = round($biennially * $rate, 2);
                $update_triennially = round($triennially * $rate, 2);
            } else {
                $update_monthly = 0 < $monthly ? round($monthly * $rate, 2) : $monthly;
                $update_quarterly = 0 < $quarterly ? round($quarterly * $rate, 2) : $quarterly;
                $update_semiannually = 0 < $semiannually ? round($semiannually * $rate, 2) : $semiannually;
                $update_annually = 0 < $annually ? round($annually * $rate, 2) : $annually;
                $update_biennially = 0 < $biennially ? round($biennially * $rate, 2) : $biennially;
                $update_triennially = 0 < $triennially ? round($triennially * $rate, 2) : $triennially;
            }
            if ($domaintype) {
                $updatecriteria = array("type" => $type, "currency" => $id, "relid" => $relid, "tsetupfee" => $tsetupfee);
            } else {
                $updatecriteria = array("type" => $type, "currency" => $id, "relid" => $relid);
            }
            update_query("tblpricing", array("msetupfee" => $update_msetupfee, "qsetupfee" => $update_qsetupfee, "ssetupfee" => $update_ssetupfee, "asetupfee" => $update_asetupfee, "bsetupfee" => $update_bsetupfee, "tsetupfee" => $update_tsetupfee, "monthly" => $update_monthly, "quarterly" => $update_quarterly, "semiannually" => $update_semiannually, "annually" => $update_annually, "biennially" => $update_biennially, "triennially" => $update_triennially), $updatecriteria);
        }
    }
}

?>