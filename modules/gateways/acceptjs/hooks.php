<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

add_hook("AdminAreaFooterOutput", 1, function (array $vars) {
    $filename = $vars["filename"];
    $ret = "";
    if ($filename == "clientssummary") {
        $gatewayInterface = new WHMCS\Module\Gateway();
        $gatewayInterface->load("acceptjs");
        $params = $gatewayInterface->getParams();
        $url = "";
        if ($params["testMode"]) {
            $url = "test";
        }
        $url = "https://js" . $url . ".authorize.net/v1/Accept.js";
        $ret = "<script type=\"text/javascript\" src=\"" . $url . "\" charset=\"utf-8\"></script>";
    }
    return $ret;
});
add_hook("ClientAreaFooterOutput", 1, function (array $vars) {
    $filename = $vars["filename"];
    $template = $vars["templatefile"];
    $ret = "";
    $requiredFiles = array("cart", "creditcard");
    if (in_array($filename, $requiredFiles) || $template == "account-paymentmethods-manage") {
        $gatewayInterface = new WHMCS\Module\Gateway();
        $gatewayInterface->load("acceptjs");
        $params = $gatewayInterface->getParams();
        $jsUrl = "";
        if ($params["testMode"]) {
            $jsUrl = "test";
        }
        $jsUrl = "https://js" . $jsUrl . ".authorize.net/v1/Accept.js";
        $ret = "<script type=\"text/javascript\" src=\"" . $jsUrl . "\" charset=\"utf-8\"></script>";
    }
    return $ret;
});

?>