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
$description = App::getFromRequest("description");
$invoiceaction = App::getFromRequest("invoiceaction");
$recur = (int) App::getFromRequest("recur");
$recurcycle = App::getFromRequest("recurcycle");
$recurfor = (int) App::getFromRequest("recurfor");
$duedate = App::getFromRequest("duedate");
$hours = (double) App::getFromRequest("hours");
$amount = (double) App::getFromRequest("amount");
$result = select_query("tblclients", "", array("id" => $clientid));
$data = mysql_fetch_array($result);
$clientid = $data["id"];
if (!$clientid) {
    $apiresults = array("result" => "error", "message" => "Client ID not Found");
} else {
    if (!$description) {
        $apiresults = array("result" => "error", "message" => "You must provide a description");
    } else {
        $allowedtypes = array("noinvoice", "nextcron", "nextinvoice", "duedate", "recur");
        if ($invoiceaction && !in_array($invoiceaction, $allowedtypes)) {
            $apiresults = array("result" => "error", "message" => "Invalid Invoice Action");
        } else {
            if ($invoiceaction == "recur" && (!$recur && !$recurcycle || !$recurfor)) {
                $apiresults = array("result" => "error", "message" => "Recurring must have a unit, cycle and limit");
            } else {
                if ($invoiceaction == "duedate" && !$duedate) {
                    $apiresults = array("result" => "error", "message" => "Due date is required");
                } else {
                    if ($invoiceaction == "noinvoice") {
                        $invoiceaction = "0";
                    } else {
                        if ($invoiceaction == "nextcron") {
                            $invoiceaction = "1";
                            if (!$duedate) {
                                $duedate = date("Y-m-d");
                            }
                        } else {
                            if ($invoiceaction == "nextinvoice") {
                                $invoiceaction = "2";
                            } else {
                                if ($invoiceaction == "duedate") {
                                    $invoiceaction = "3";
                                } else {
                                    if ($invoiceaction == "recur") {
                                        $invoiceaction = "4";
                                    }
                                }
                            }
                        }
                    }
                    $id = insert_query("tblbillableitems", array("userid" => $clientid, "description" => $description, "hours" => $hours, "amount" => $amount, "recur" => $recur, "recurcycle" => $recurcycle, "recurfor" => $recurfor, "invoiceaction" => $invoiceaction, "duedate" => $duedate));
                    $apiresults = array("result" => "success", "billableid" => $id);
                }
            }
        }
    }
}

?>