<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if (!defined("WHMCS")) {
    exit("This file cannot be accessed directly");
}
if (!function_exists("addClient")) {
    require ROOTDIR . "/includes/clientfunctions.php";
}
if (!function_exists("updateInvoiceTotal")) {
    require ROOTDIR . "/includes/invoicefunctions.php";
}
if (!function_exists("saveQuote")) {
    require ROOTDIR . "/includes/quotefunctions.php";
}
if (!$subject) {
    $apiresults = array("result" => "error", "message" => "Subject is required");
} else {
    $stagearray = array("Draft", "Delivered", "On Hold", "Accepted", "Lost", "Dead");
    if (!in_array($stage, $stagearray)) {
        $apiresults = array("result" => "error", "message" => "Invalid Stage");
    } else {
        if (!$validuntil) {
            $apiresults = array("result" => "error", "message" => "Valid Until is required");
        } else {
            if (!$datecreated) {
                $datecreated = date("Y-m-d");
            }
            if ($lineitems) {
                $lineitems = base64_decode($lineitems);
                $lineitemsarray = safe_unserialize($lineitems);
            }
            if (!$userid) {
                $clienttype = "new";
            }
            $newquoteid = saveQuote("", $subject, $stage, $datecreated, $validuntil, $clienttype, $userid, $firstname, $lastname, $companyname, $email, $address1, $address2, $city, $state, $postcode, $country, $phonenumber, $currency, $lineitemsarray, $proposal, $customernotes, $adminnotes, false, App::getFromRequest("tax_id"));
            $apiresults = array("result" => "success", "quoteid" => $newquoteid);
        }
    }
}

?>