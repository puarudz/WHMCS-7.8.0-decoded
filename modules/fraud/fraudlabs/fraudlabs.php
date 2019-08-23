<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function fraudlabs_MetaData()
{
    return array("DisplayName" => "FraudLabs Pro", "SupportsRechecks" => true, "APIVersion" => "1.2");
}
function fraudlabs_getConfigArray()
{
    return array("Enable" => array("FriendlyName" => "Enable FraudLabs Pro", "Type" => "yesno", "Description" => "Tick to enable FraudLabs Pro Fraud Checking for Orders"), "licenseKey" => array("FriendlyName" => "FraudLabs Pro License Key", "Type" => "text", "Size" => "30", "Description" => "Don't have an account? " . "<a href=\"http://go.whmcs.com/1409/fraudlabs-create-account\" class=\"autoLinked\">" . "Click here to sign up &raquo;</a>"), "riskScore" => array("FriendlyName" => "FraudLabs Pro Fraud Risk Score", "Type" => "text", "Size" => "2", "Default" => 20, "Description" => "Higher than this value and the order will be blocked (1 -> 100)"), "rejectFreeEmail" => array("FriendlyName" => "Reject Free Email Service", "Type" => "yesno", "Description" => "Block orders from free email addresses such as Hotmail & Yahoo!"), "rejectCountryMismatch" => array("FriendlyName" => "Reject Country Mismatch", "Type" => "yesno", "Description" => "Block orders where order address is different from IP Location"), "rejectAnonymousNetwork" => array("FriendlyName" => "Reject Anonymous Networks", "Type" => "yesno", "Description" => "Block orders where the user is ordering through an anonymous network"), "rejectHighRiskCountry" => array("FriendlyName" => "Reject High Risk Country", "Type" => "yesno", "Description" => "Block orders from high risk countries"));
}
function fraudlabs_doFraudCheck(array $params, $checkOnly = false)
{
    $emailDomain = explode("@", $params["clientsdetails"]["email"], 2);
    $emailDomain = isset($emailDomain[1]) ? $emailDomain[1] : "";
    $billing = array();
    $billing["first_name"] = $params["clientsdetails"]["firstname"];
    $billing["last_name"] = $params["clientsdetails"]["lastname"];
    if ($params["clientsdetails"]["address1"]) {
        $billing["bill_addr"] = $params["clientsdetails"]["address1"];
    }
    if ($params["clientsdetails"]["city"]) {
        $billing["bill_city"] = $params["clientsdetails"]["city"];
    }
    if ($params["clientsdetails"]["state"]) {
        $billing["bill_state"] = $params["clientsdetails"]["state"];
    }
    if ($params["clientsdetails"]["postcode"]) {
        $billing["bill_zip_code"] = $params["clientsdetails"]["postcode"];
    }
    $billing["bill_country"] = $params["clientsdetails"]["country"];
    $billing["user_phone"] = $params["clientsdetails"]["telephoneNumber"];
    $model = $params["clientsdetails"]["model"];
    if ($model instanceof WHMCS\User\Client) {
        $currencyCode = $model->currencyrel->code;
    } else {
        $currencyCode = $model->client->currencyrel->code;
    }
    $request = array_merge(array("ip" => $params["ip"], "format" => "json", "email_domain" => $emailDomain, "email" => $params["clientsdetails"]["email"], "email_hash" => WHMCS\Module\Fraud\FraudLabs\FraudLabs::hash($params["clientsdetails"]["email"]), "user_order_id" => substr($params["order"]["order_number"], 0, 15), "amount" => $params["order"]["amount"], "currency" => $currencyCode), $billing);
    $errorResponse = NULL;
    try {
        $response = (new WHMCS\Module\Fraud\FraudLabs\Request())->setLicenseKey($params["licenseKey"])->call($request);
        if ($response->isSuccessful()) {
            if (!$checkOnly) {
                (new WHMCS\Module\Fraud\FraudLabs\FraudLabs())->validateRules($params, $response);
            }
        } else {
            $errorCode = $response->get("fraudlabspro_error_code");
            $error = $response->get("fraudlabspro_message");
            logActivity("FraudLabs Pro Fraud Check - Error Occurred: " . $errorCode . " - " . $error);
            $errorResponse = Lang::trans("fraud.checkConfiguration");
        }
    } catch (WHMCS\Exception\Fraud\FraudCheckException $e) {
        $errorResponse = $e->getMessage();
    } catch (WHMCS\Exception\Http\ConnectionError $e) {
        logActivity("FraudLabs Pro Fraud Check - Connection Error: " . $e->getMessage());
        $errorResponse = Lang::trans("fraud.checkConfiguration");
    } catch (Exception $e) {
        logActivity("FraudLabs Pro Fraud Check - General Error: " . $e->getMessage());
        $errorResponse = Lang::trans("fraud.checkConfiguration");
    }
    $returnData = array();
    if (!empty($response) && $response instanceof WHMCS\Module\Fraud\FraudLabs\Response) {
        $returnData["data"] = $response->toArray();
        if (in_array($response->get("fraudlabspro_error_code"), array(101, 102, 103, 104))) {
            $errorResponse = NULL;
        }
    }
    if (!is_null($errorResponse)) {
        $returnData["error"] = array("title" => Lang::trans("fraud.title") . " " . Lang::trans("fraud.error"), "description" => $errorResponse);
    }
    return $returnData;
}
function fraudlabs_processResultsForDisplay(array $params)
{
    return (new WHMCS\Module\Fraud\FraudLabs\FraudLabs())->formatResponse(new WHMCS\Module\Fraud\FraudLabs\Response($params["data"]));
}

?>