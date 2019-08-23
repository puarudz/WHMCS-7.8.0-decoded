<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

add_hook("AdminAreaFooterOutput", 1, function (array $vars) {
    $filename = $vars["filename"];
    $return = "";
    if ($filename == "clientssummary") {
        $return = "<script type=\"text/javascript\" src=\"https://js.stripe.com/v3/\"></script>";
    }
    return $return;
});
add_hook("ClientAreaFooterOutput", 1, function (array $vars) {
    $filename = $vars["filename"];
    $template = $vars["templatefile"];
    $return = "";
    $requiredFiles = array("cart", "creditcard");
    if (in_array($filename, $requiredFiles) || $template == "account-paymentmethods-manage") {
        $return = "<script type=\"text/javascript\" src=\"https://js.stripe.com/v3/\"></script>";
    }
    return $return;
});

?>