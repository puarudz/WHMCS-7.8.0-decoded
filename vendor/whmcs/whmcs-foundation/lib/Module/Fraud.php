<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module;

class Fraud extends AbstractModule
{
    protected $type = self::TYPE_FRAUD;
    const SKIP_MODULES = array("SKIPPED", "CREDIT");
    public function getActiveModules()
    {
        return \WHMCS\Database\Capsule::table("tblfraud")->where("setting", "Enable")->where("value", "!=", "")->distinct("fraud")->pluck("fraud");
    }
    public function load($module, $globalVariable = NULL)
    {
        if (in_array($module, self::SKIP_MODULES)) {
            return false;
        }
        return parent::load($module);
    }
    public function getSettings()
    {
        return \WHMCS\Database\Capsule::table("tblfraud")->where("fraud", $this->getLoadedModule())->pluck("value", "setting");
    }
    public function call($function, array $params = array())
    {
        $params = array_merge($params, $this->getSettings());
        return parent::call($function, $params);
    }
    public function doFraudCheck($orderid, $userid = "", $ip = "")
    {
        $params = array();
        $params["ip"] = $ip ? $ip : \App::getRemoteIp();
        $params["forwardedip"] = $_SERVER["HTTP_X_FORWARDED_FOR"];
        $userid = (int) $userid;
        if (!$userid) {
            $userid = \WHMCS\Session::get("uid");
        }
        $clientsdetails = getClientsDetails($userid);
        $params["clientsdetails"] = $clientsdetails;
        $params["clientsdetails"]["countrycode"] = $clientsdetails["phonecc"];
        $order = \WHMCS\Order\Order::find($orderid);
        $params["orderid"] = $order->id;
        $params["order"] = array("id" => $order->id, "order_number" => $order->orderNumber, "amount" => $order->amount, "payment_method" => $order->paymentMethod, "promo_code" => $order->promoCode);
        if (!defined("ADMINAREA")) {
            $params["sessionId"] = session_id();
            $params["userAgent"] = $_SERVER["HTTP_USER_AGENT"];
            $params["acceptLanguage"] = $_SERVER["HTTP_ACCEPT_LANGUAGE"];
        }
        $hookResponses = run_hook("PreFraudCheck", $params);
        foreach ($hookResponses as $hookResponse) {
            $params = array_merge($params, $hookResponse);
        }
        $response = $this->call("doFraudCheck", $params);
        $output = "";
        if ($response) {
            if (version_compare($this->getAPIVersion(), "1.2", ">=")) {
                $responseData = is_array($response["data"]) ? $response["data"] : array();
                $output = json_encode($responseData);
            } else {
                foreach ($response as $key => $value) {
                    if (!in_array($key, array("userinput", "error", "title", "description"))) {
                        $output .= $key . " => " . $value . "\n";
                    }
                }
            }
        }
        $order->fraudModule = $this->getLoadedModule();
        $order->fraudOutput = $output;
        $order->save();
        $response["fraudoutput"] = $output;
        return $response;
    }
    public function processResultsForDisplay($orderid, $fraudoutput = "")
    {
        if ($orderid && !$fraudoutput) {
            $data = get_query_vals("tblorders", "fraudoutput", array("id" => $orderid, "fraudmodule" => $this->getLoadedModule()));
            $fraudoutput = $data["fraudoutput"];
        }
        $results = $this->call("processResultsForDisplay", array("data" => $fraudoutput));
        $fraudResults = \WHMCS\Input\Sanitize::makeSafeForOutput($results);
        if (version_compare($this->getAPIVersion(), "1.2", ">=") && is_string($results)) {
            $return = $results;
        } else {
            $return = "<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\"><tr>";
            $i = 0;
            foreach ($fraudResults as $key => $value) {
                $i++;
                $colspan = "";
                $width = "";
                $end = "";
                if ($key == "Explanation") {
                    $colspan = " colspan=\"3\"";
                    $i = 2;
                } else {
                    $width = " width=\"20%\"";
                }
                if ($i == 2) {
                    $end = "</tr><tr>";
                    $i = 0;
                }
                $return .= "<td class=\"fieldlabel\" width=\"30%\">" . $key . "</td>" . "<td class=\"fieldarea\"" . $colspan . $width . ">" . $value . "</td>" . $end;
            }
            $return .= "</tr></table>";
        }
        return $return;
    }
    public function getAdminActivationForms($moduleName)
    {
        return array((new \WHMCS\View\Form())->setUriPrefixAdminBaseUrl("configfraud.php")->setMethod(\WHMCS\View\Form::METHOD_GET)->setParameters(array("fraud" => $moduleName))->setSubmitLabel(\AdminLang::trans("global.activate")));
    }
    public function getAdminManagementForms($moduleName)
    {
        return array((new \WHMCS\View\Form())->setUriPrefixAdminBaseUrl("configfraud.php")->setMethod(\WHMCS\View\Form::METHOD_GET)->setParameters(array("fraud" => $moduleName))->setSubmitLabel(\AdminLang::trans("global.manage")));
    }
}

?>