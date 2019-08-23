<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Client;

class ClientController
{
    public function export(\WHMCS\Http\Message\ServerRequest $request)
    {
        $client = \WHMCS\User\Client::find($request->get("client_id"));
        if (is_null($client)) {
            throw new \WHMCS\Exception("Client id not found");
        }
        $exportData = $request->get("exportdata");
        if (empty($exportData) || !is_array($exportData)) {
            $exportData = array("profile");
        }
        $dataToExport = array();
        if (in_array("profile", $exportData)) {
            $profileData = $client->toArrayUsingColumnMapNames();
            unset($profileData["creditCardType"]);
            unset($profileData["creditCardLastFourDigits"]);
            unset($profileData["paymentGatewayToken"]);
            $dataToExport["profile"] = $profileData;
        }
        if (in_array("paymethods", $exportData)) {
            $payMethods = array("creditCards" => array(), "bankAccounts" => array());
            if ($client->needsCardDetailsMigrated()) {
                if (!function_exists("getClientDefaultCardDetails")) {
                    require_once ROOTDIR . "/includes/ccfunctions.php";
                }
                $cardDetails = getClientDefaultCardDetails($client->id);
                if ($cardDetails["cardtype"]) {
                    $payMethods["creditCards"][] = array("name" => $cardDetails["cardtype"] . "-" . $cardDetails["cardlastfour"]);
                }
            }
            if ($client->needsBankDetailsMigrated()) {
                if (!function_exists("getClientDefaultBankDetails")) {
                    require_once ROOTDIR . "/includes/clientfunctions.php";
                }
                $bankDetails = getClientDefaultBankDetails($client->id);
                if ($bankDetails["banktype"]) {
                    $payMethods["bankAccounts"][] = array("name" => $bankDetails["banktype"] . "-" . substr($bankDetails["bankacct"], -4, 4));
                }
            }
            foreach ($client->payMethods as $payMethod) {
                $reportType = "";
                if ($payMethod->isCreditCard()) {
                    $reportType = "creditCards";
                } else {
                    if ($payMethod->isBankAccount()) {
                        $reportType = "bankAccounts";
                    }
                }
                if ($reportType) {
                    $payMethods[$reportType][] = array("name" => $payMethod->payment->getDisplayName());
                }
            }
            $dataToExport["payMethods"] = $payMethods;
        }
        if (in_array("contacts", $exportData)) {
            $dataToExport["contacts"] = array();
            foreach ($client->contacts()->get() as $contact) {
                $dataToExport["contacts"][] = $contact->toArrayUsingColumnMapNames();
            }
        }
        if (in_array("services", $exportData)) {
            $dataToExport["services"] = array();
            foreach ($client->services()->get() as $service) {
                $dataToExport["services"][] = $service->toArrayUsingColumnMapNames();
            }
        }
        if (in_array("domains", $exportData)) {
            $dataToExport["domains"] = array();
            foreach ($client->domains()->get() as $domain) {
                $dataToExport["domains"][] = $domain->toArrayUsingColumnMapNames();
            }
        }
        if (in_array("billableitems", $exportData)) {
            $dataToExport["billableitems"] = array();
            foreach (\WHMCS\Database\Capsule::table("tblbillableitems")->where("userid", $client->id)->orderBy("duedate", "asc")->get() as $billableitem) {
                $dataToExport["billableitems"][] = $billableitem;
            }
        }
        if (in_array("invoices", $exportData)) {
            $dataToExport["invoices"] = array();
            foreach ($client->invoices()->with("items")->get() as $invoice) {
                $dataToExport["invoices"][] = $invoice->toArrayUsingColumnMapNames();
            }
        }
        if (in_array("quotes", $exportData)) {
            $dataToExport["quotes"] = array();
            foreach ($client->quotes()->get() as $quote) {
                $dataToExport["quotes"][] = $quote->toArrayUsingColumnMapNames();
            }
        }
        if (in_array("transactions", $exportData)) {
            $dataToExport["transactions"] = array();
            foreach ($client->transactions()->get() as $transaction) {
                $dataToExport["transactions"][] = $transaction->toArrayUsingColumnMapNames();
            }
        }
        if (in_array("tickets", $exportData)) {
            $dataToExport["tickets"] = array();
            foreach ($client->tickets()->with("replies")->get() as $ticket) {
                $dataToExport["tickets"][] = $ticket->toArrayUsingColumnMapNames();
            }
        }
        if (in_array("emails", $exportData)) {
            $dataToExport["emails"] = array();
            foreach (\WHMCS\Database\Capsule::table("tblemails")->where("userid", $client->id)->orderBy("date", "asc")->get() as $email) {
                $dataToExport["emails"][] = $email;
            }
        }
        if (in_array("notes", $exportData)) {
            $dataToExport["notes"] = array();
            foreach (\WHMCS\Database\Capsule::table("tblnotes")->where("userid", $client->id)->orderBy("created", "asc")->get() as $note) {
                $dataToExport["notes"][] = $note;
            }
        }
        if (in_array("consenthistory", $exportData)) {
            $dataToExport["consenthistory"] = array();
            foreach ($client->marketingConsent()->get() as $consent) {
                $dataToExport["consenthistory"][] = $consent;
            }
        }
        if (in_array("activitylog", $exportData)) {
            $dataToExport["activitylog"] = array();
            foreach (\WHMCS\Database\Capsule::table("tblactivitylog")->where("userid", $client->id)->orderBy("date", "asc")->get() as $activity) {
                $dataToExport["activitylog"][] = $activity;
            }
        }
        $attachmentName = "Client Export - Client ID " . $client->id . ".json";
        return new \WHMCS\Http\Message\JsonAttachmentResponse(jsonPrettyPrint($dataToExport), $attachmentName);
    }
}

?>