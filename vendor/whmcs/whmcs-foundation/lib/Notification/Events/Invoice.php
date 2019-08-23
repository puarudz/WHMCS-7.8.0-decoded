<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Notification\Events;

class Invoice
{
    const DISPLAY_NAME = "Invoice";
    public function getEvents()
    {
        return array("created" => array("label" => "Created", "hook" => "InvoiceCreation"), "paid" => array("label" => "Paid", "hook" => "InvoicePaid"), "cancelled" => array("label" => "Cancelled", "hook" => "InvoiceCancelled"), "refunded" => array("label" => "Refunded", "hook" => "InvoiceRefunded"), "modified" => array("label" => "Modified", "hook" => "UpdateInvoiceTotal"));
    }
    public function getConditions()
    {
        return array("total_due" => array("FriendlyName" => "Total Due", "Type" => "range"), "client_group" => array("FriendlyName" => "Client Group", "Type" => "dropdown", "Options" => function () {
            return \WHMCS\Database\Capsule::table("tblclientgroups")->orderBy("groupname")->pluck("groupname", "id");
        }, "GetDisplayValue" => function ($value) {
            return \WHMCS\Database\Capsule::table("tblclientgroups")->where("id", $value)->first()->groupname;
        }));
    }
    public function evaluateConditions($event, $conditions, $hookParameters)
    {
        $invoiceId = isset($hookParameters["invoiceid"]) ? $hookParameters["invoiceid"] : "";
        $invoice = null;
        if ($conditions["total_due_filter"] && $conditions["total_due"]) {
            if (is_null($invoice)) {
                $invoice = \WHMCS\Billing\Invoice::find($invoiceId);
            }
            $invoiceTotal = $invoice->total;
            if ($conditions["total_due_filter"] == "greater") {
                if ($invoiceTotal < $conditions["total_due"]) {
                    return false;
                }
            } else {
                if ($conditions["total_due"] < $invoiceTotal) {
                    return false;
                }
            }
        }
        if ($conditions["client_group"]) {
            if (is_null($invoice)) {
                $invoice = \WHMCS\Billing\Invoice::find($invoiceId);
            }
            $clientGroup = $invoice->client->groupId;
            if ($conditions["client_group"] != $clientGroup) {
                return false;
            }
        }
        return true;
    }
    public function buildNotification($event, $hookParameters)
    {
        $invoiceId = isset($hookParameters["invoiceid"]) ? $hookParameters["invoiceid"] : "";
        $invoice = \WHMCS\Billing\Invoice::find($invoiceId);
        $title = \AdminLang::trans("fields.invoicenum") . $invoiceId;
        $dueDate = $invoice->duedate;
        $total = $invoice->total;
        $paymentMethod = $invoice->paymentmethod;
        $status = $invoice->status;
        $firstName = $invoice->client->firstName;
        $lastName = $invoice->client->lastName;
        $clientUrl = \App::getSystemUrl() . \App::get_admin_folder_name() . "/clientssummary.php?userid=" . $invoice->clientId;
        $currency = getCurrency($invoice->clientId);
        $total = formatCurrency($total);
        $url = \App::getSystemUrl() . \App::get_admin_folder_name() . "/invoices.php?action=edit&id=" . $invoiceId;
        $message = \AdminLang::trans("notifications.invoice." . $event);
        $statusStyle = "primary";
        if ($status == "Paid") {
            $statusStyle = "success";
        } else {
            if ($status == "Unpaid") {
                $statusStyle = "danger";
            } else {
                if ($status == "Collections") {
                    $statusStyle = "warning";
                } else {
                    if ($status == "Refunded") {
                        $statusStyle = "info";
                    }
                }
            }
        }
        if (!function_exists("getGatewayName")) {
            \App::load_function("gateway");
        }
        return (new \WHMCS\Notification\Notification())->setTitle($title)->setMessage($message)->setUrl($url)->addAttribute((new \WHMCS\Notification\NotificationAttribute())->setLabel(\AdminLang::trans("fields.client"))->setValue($firstName . " " . $lastName)->setUrl($clientUrl))->addAttribute((new \WHMCS\Notification\NotificationAttribute())->setLabel(\AdminLang::trans("fields.duedate"))->setValue(fromMySQLDate($dueDate)))->addAttribute((new \WHMCS\Notification\NotificationAttribute())->setLabel(\AdminLang::trans("fields.total"))->setValue($total))->addAttribute((new \WHMCS\Notification\NotificationAttribute())->setLabel(\AdminLang::trans("fields.paymentmethod"))->setValue(getGatewayName($paymentMethod)))->addAttribute((new \WHMCS\Notification\NotificationAttribute())->setLabel(\AdminLang::trans("fields.status"))->setValue($status)->setStyle($statusStyle));
    }
}

?>