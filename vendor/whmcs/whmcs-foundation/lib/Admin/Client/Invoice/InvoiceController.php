<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Client\Invoice;

class InvoiceController
{
    public function capture(\WHMCS\Http\Message\ServerRequest $request)
    {
        $clientId = (int) $request->getAttribute("userId");
        $invoiceId = (int) $request->getAttribute("invoiceId");
        $invoice = \WHMCS\Billing\Invoice::find($invoiceId);
        if (!$invoice || $invoice->client->id != $clientId) {
        }
        $payMethods = $invoice->client->payMethods()->get();
        if ($invoice->paymentGateway) {
            $payMethods = $payMethods->forGateway($invoice->paymentGateway);
        }
        $body = view("admin.client.invoice.capture", array("payMethods" => $payMethods, "client" => $invoice->client, "invoice" => $invoice, "viewHelper" => new \WHMCS\Admin\Client\PayMethod\ViewHelper()));
        $body = (new \WHMCS\Admin\ApplicationSupport\View\PreRenderProcessor())->process($body);
        $response = new \WHMCS\Http\Message\JsonResponse(array("body" => $body));
        return $response;
    }
    public function doCapture(\WHMCS\Http\Message\ServerRequest $request)
    {
        try {
            $clientId = (int) $request->getAttribute("userId");
            $invoiceId = (int) $request->getAttribute("invoiceId");
            $payMethodId = (int) $request->get("paymentId");
            $payMethod = \WHMCS\Payment\PayMethod\Model::findForClient($payMethodId, $clientId);
            $invoice = \WHMCS\Billing\Invoice::find($invoiceId);
            if (!$payMethod || $payMethod->client->id != $clientId || !$invoice || $invoice->client->id != $clientId) {
                throw new \WHMCS\Payment\Exception\InvalidModuleException("Invalid Access Attempt");
            }
            if (in_array($invoice->status, array("Paid", "Cancelled"))) {
                throw new \WHMCS\Exception\Validation\InvalidValue("Invalid Status for Capture");
            }
            logActivity("Admin Initiated Payment Capture - Invoice ID: " . $invoice->id, $clientId);
            $success = $payMethod->capture($invoice, $request->getAttribute("cardcvv", ""));
            $response = new \WHMCS\Http\Message\JsonResponse(array("redirect" => "invoices.php?action=edit&id=" . $invoiceId . "&payment=" . $success));
        } catch (\Exception $e) {
            $body = $e->getMessage();
            $response = new \WHMCS\Http\Message\JsonResponse(array("body" => $body));
        }
        return $response;
    }
}

?>