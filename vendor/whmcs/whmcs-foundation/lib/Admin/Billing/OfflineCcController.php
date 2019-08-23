<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Billing;

class OfflineCcController
{
    public function getForm(\WHMCS\Http\Message\ServerRequest $request)
    {
        $invoice = \WHMCS\Billing\Invoice::find((int) $request->getAttribute("invoice_id"));
        if (!$invoice) {
            throw new \WHMCS\Exception("Invalid invoice ID");
        }
        $body = view("admin.billing.offline-cc.decrypt-form", array("invoice" => $invoice));
        $body = (new \WHMCS\Admin\ApplicationSupport\View\PreRenderProcessor())->process($body);
        return new \WHMCS\Http\Message\JsonResponse(array("body" => $body));
    }
    public function decryptCardData(\WHMCS\Http\Message\ServerRequest $request)
    {
        $submittedHash = $request->get("cchash");
        if ($submittedHash !== \DI::make("config")->cc_encryption_hash) {
            return new \WHMCS\Http\Message\JsonResponse(array("errorMsgTitle" => "", "errorMsg" => \AdminLang::trans("clients.incorrecthash")));
        }
        $payMethod = \WHMCS\Payment\PayMethod\Model::find($request->get("paymethod"));
        if (!$payMethod || $payMethod->getType() !== \WHMCS\Payment\Contracts\PayMethodTypeInterface::TYPE_CREDITCARD_LOCAL) {
            return new \WHMCS\Http\Message\JsonResponse(array("errorMsgTitle" => "", "errorMsg" => \AdminLang::trans("global.erroroccurred")));
        }
        $payment = $payMethod->payment;
        $cardData = array("cctype" => $payment->getCardType(), "ccnum" => $payment->getCardNumber(), "expdate" => $payment->getExpiryDate()->format("m/y"), "issuenumber" => $payment->getIssueNumber(), "startdate" => $payment->getStartDate() ? $payment->getStartDate()->format("m/y") : "");
        $body = view("admin.billing.offline-cc.decrypted-data", array("payMethod" => $payMethod, "cardData" => $cardData));
        $body = (new \WHMCS\Admin\ApplicationSupport\View\PreRenderProcessor())->process($body);
        return new \WHMCS\Http\Message\JsonResponse(array("body" => $body));
    }
    public function applyTransaction(\WHMCS\Http\Message\ServerRequest $request)
    {
        $invoice = \WHMCS\Billing\Invoice::find($request->getAttribute("invoice_id"));
        if (!$invoice) {
            throw new \WHMCS\Exception("Invalid invoice ID");
        }
        $payMethod = \WHMCS\Payment\PayMethod\Model::find($request->get("paymethod"));
        if ($request->get("success")) {
            $invoice->addPayment($invoice->balance, $request->get("transid"), 0, "offlinecc");
            if ($payMethod) {
                $invoice->payMethod()->associate($payMethod);
                $invoice->save();
            }
            addInvoicePayment($invoice->id, $request->get("transid"), "", "", "offlinecc");
        } else {
            sendMessage("Credit Card Payment Failed", $invoice->id);
        }
        return new \WHMCS\Http\Message\JsonResponse(array());
    }
}

?>