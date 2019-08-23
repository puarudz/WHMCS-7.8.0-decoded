<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Mail\Entity;

class Invoice extends \WHMCS\Mail\Emailer
{
    protected function getEntitySpecificMergeData($invoiceId)
    {
        $invoice = new \WHMCS\Invoice($invoiceId);
        $sysurl = \App::getSystemURL();
        $data = $invoice->getOutput();
        $userid = $data["userid"];
        $this->setRecipient($userid);
        $invoicedescription = "";
        $invoiceitems = $invoice->getLineItems();
        foreach ($invoiceitems as $item) {
            $lines = preg_split("/<br \\/>(\\r\\n|\\n)/", $item["description"]);
            foreach ($lines as $line) {
                $invoicedescription .= trim($line . " " . $item["amount"]) . "<br>\n";
                $item["amount"] = "";
            }
        }
        $invoicedescription .= str_repeat("-", 54) . "<br>\n";
        $invoicedescription .= \Lang::trans("invoicessubtotal") . ": " . $data["subtotal"] . "<br>\n";
        if (0 < $data["taxrate"]) {
            $invoicedescription .= $data["taxrate"] . "% " . $data["taxname"] . ": " . $data["tax"] . "<br>\n";
        }
        if (0 < $data["taxrate2"]) {
            $invoicedescription .= $data["taxrate2"] . "% " . $data["taxname2"] . ": " . $data["tax2"] . "<br>\n";
        }
        $invoicedescription .= \Lang::trans("invoicescredit") . ": " . $data["credit"] . "<br>\n";
        $invoicedescription .= \Lang::trans("invoicestotal") . ": " . $data["total"] . "";
        $email_merge_fields = array();
        $email_merge_fields["invoice_id"] = (int) $data["invoiceid"];
        $email_merge_fields["invoice_num"] = $data["invoicenum"];
        $email_merge_fields["invoice_date_created"] = $data["date"];
        $email_merge_fields["invoice_date_due"] = $data["duedate"];
        $email_merge_fields["invoice_date_paid"] = $data["datepaid"];
        $email_merge_fields["invoice_items"] = $invoiceitems;
        $email_merge_fields["invoice_html_contents"] = $invoicedescription;
        $email_merge_fields["invoice_subtotal"] = $data["subtotal"];
        $email_merge_fields["invoice_credit"] = $data["credit"];
        $email_merge_fields["invoice_tax"] = $data["tax"];
        $email_merge_fields["invoice_tax_rate"] = $data["taxrate"] . "%";
        $email_merge_fields["invoice_tax2"] = $data["tax2"];
        $email_merge_fields["invoice_tax_rate2"] = $data["taxrate2"] . "%";
        $email_merge_fields["invoice_total"] = $data["total"];
        $email_merge_fields["invoice_amount_paid"] = $data["amountpaid"];
        $email_merge_fields["invoice_balance"] = $data["balance"];
        $email_merge_fields["invoice_status"] = $data["statuslocale"];
        $email_merge_fields["invoice_last_payment_amount"] = $data["lastpaymentamount"];
        $email_merge_fields["invoice_last_payment_transid"] = $data["lastpaymenttransid"];
        $email_merge_fields["invoice_payment_link"] = $invoice->getData("status") == "Unpaid" && 0 < $invoice->getData("balance") ? $invoice->getPaymentLink() : "";
        $email_merge_fields["invoice_payment_method"] = $data["paymentmethod"];
        $email_merge_fields["invoice_link"] = "<a href=\"" . $sysurl . "viewinvoice.php?id=" . $data["id"] . "\">" . $sysurl . "viewinvoice.php?id=" . $data["id"] . "</a>";
        $email_merge_fields["invoice_notes"] = $data["notes"];
        $email_merge_fields["invoice_subscription_id"] = $data["subscrid"];
        $email_merge_fields["invoice_previous_balance"] = $data["clientpreviousbalance"];
        $email_merge_fields["invoice_all_due_total"] = $data["clienttotaldue"];
        $email_merge_fields["invoice_total_balance_due"] = $data["clientbalancedue"];
        $expiry = "";
        $displayName = "";
        $description = "";
        $nextPaymentAttemptDate = null;
        $gatewayInterface = new \WHMCS\Module\Gateway();
        $gatewayInterface->load($data["model"]->paymentmethod);
        $type = $gatewayInterface->getBaseGatewayType();
        if ($data["payMethod"]) {
            $payMethod = $data["payMethod"];
            $displayName = $payMethod->payment->getDisplayName();
            $description = $payMethod->getDescription();
            $type = "bankaccount";
            if ($payMethod->payment instanceof \WHMCS\Payment\Contracts\CreditCardDetailsInterface) {
                $type = "creditcard";
                if ($payMethod->payment->getExpiryDate()) {
                    $expiry = $payMethod->payment->getExpiryDate()->toCreditCard();
                }
            }
        }
        if (in_array($type, array("creditcard", "bankaccount"))) {
            $today = \WHMCS\Carbon::today()->startOfDay();
            $dueDate = \WHMCS\Carbon::createFromFormat("Y-m-d", explode(" ", $data["rawDueDate"])[0])->startOfDay();
            $daysBeforeDue = \WHMCS\Config\Setting::getValue("CCProcessDaysBefore");
            $nextAttemptDate = $dueDate->copy();
            if (0 < $daysBeforeDue) {
                $nextAttemptDate->subDays($daysBeforeDue);
            }
            if ($today->lte($nextAttemptDate)) {
                $nextPaymentAttemptDate = $nextAttemptDate->toClientDateFormat();
            } else {
                $attemptOnlyOnce = \App::getFromRequest("CCAttemptOnlyOnce");
                if (!$attemptOnlyOnce) {
                    $retry = \App::getFromRequest("CCRetryEveryWeekFor");
                    $count = 0;
                    while ($nextAttemptDate->lte($today)) {
                        $nextAttemptDate->addWeek(1);
                        $count++;
                        if ($retry && $count == $retry) {
                            break;
                        }
                    }
                    $nextPaymentAttemptDate = $nextAttemptDate->toClientDateFormat();
                }
            }
        }
        $autoCapture = $gatewayInterface->supportsAutoCapture() && $data["payMethod"];
        $email_merge_fields["invoice_pay_method_description"] = $description;
        $email_merge_fields["invoice_pay_method_display_name"] = $displayName;
        $email_merge_fields["invoice_pay_method_expiry"] = $expiry;
        $email_merge_fields["invoice_pay_method_type"] = $type;
        $email_merge_fields["invoice_auto_capture_available"] = $autoCapture;
        $email_merge_fields["invoice_next_payment_attempt_date"] = $nextPaymentAttemptDate;
        $this->massAssign($email_merge_fields);
        $existingLanguage = "";
        if (\WHMCS\Config\Setting::getValue("EnablePDFInvoices")) {
            $invoice->pdfCreate();
            $invoice->pdfInvoicePage();
            $this->message->addStringAttachment(\Lang::trans("invoicefilename") . $data["invoicenum"] . ".pdf", $invoice->pdfOutput());
        }
    }
}

?>