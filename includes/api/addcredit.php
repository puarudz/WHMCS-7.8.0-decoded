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
$clientid = (int) App::getFromRequest("clientid");
$amount = App::getFromRequest("amount");
$type = strtolower(App::getFromRequest("type"));
if (!$type) {
    $type = "add";
}
if (!in_array($type, array("add", "remove"))) {
    $apiresults = array("result" => "error", "message" => "Type can only be add or remove");
} else {
    if (!$amount) {
        $apiresults = array("result" => "error", "message" => "No Amount Provided");
    } else {
        $amount = (double) $amount;
        if (!(bool) preg_match("/^[\\d]+(\\.[\\d]{1,2})?\$/i", $amount)) {
            $apiresults = array("result" => "error", "message" => "Amount must be in decimal format: ### or ###.##");
        } else {
            $client = WHMCS\User\Client::find($clientid);
            if (!$client) {
                $apiresults = array("result" => "error", "message" => "Client ID Not Found");
            } else {
                $adminId = (int) App::getFromRequest("adminid");
                $date = App::getFromRequest("date");
                if ($date && !validateDateInput($date)) {
                    $apiresults = array("result" => "error", "message" => "Date Format is not Valid");
                } else {
                    if ($type === "remove" && $client->credit < $amount) {
                        $apiresults = array("result" => "error", "message" => "Insufficient Credit Balance");
                    } else {
                        if (!$date) {
                            $date = "now()";
                        }
                        if ($adminId) {
                            $admin = WHMCS\Database\Capsule::table("tbladmins")->where("id", $adminId)->where("disabled", 0)->first(array("id"));
                            if (!$admin) {
                                $apiresults = array("result" => "error", "message" => "Admin ID Not Found");
                                return NULL;
                            }
                            $adminId = $admin->id;
                        }
                        if (!$adminId) {
                            $adminId = WHMCS\Session::get("adminid");
                        }
                        $relativeChange = $amount;
                        if ($type === "remove") {
                            $relativeChange = 0 - $relativeChange;
                        }
                        insert_query("tblcredit", array("clientid" => $clientid, "admin_id" => $adminId, "date" => $date, "description" => $description, "amount" => $relativeChange));
                        $client->credit += $relativeChange;
                        $client->save();
                        $client = $client->fresh();
                        $currency = getCurrency($clientid);
                        $message = "Added Credit - User ID: " . $clientid . " - Amount: " . formatCurrency($amount);
                        if ($type == "remove") {
                            $message = "Removed Credit - User ID: " . $clientid . " - Amount: " . formatCurrency($amount);
                        }
                        logActivity($message, $clientid);
                        $apiresults = array("result" => "success", "newbalance" => $client->credit);
                    }
                }
            }
        }
    }
}

?>