<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Gateway\BP;

class Client extends \Bitpay\Client\Client
{
    public function createInvoice(\Bitpay\InvoiceInterface $invoice)
    {
        $request = $this->createNewRequest();
        $request->setMethod(\Bitpay\Client\Request::METHOD_POST);
        $request->setPath("invoices");
        $currency = $invoice->getCurrency();
        $item = $invoice->getItem();
        $buyer = $invoice->getBuyer();
        $buyerAddress = $buyer->getAddress();
        $this->checkPriceAndCurrency($item->getPrice(), $currency->getCode());
        $body = array("price" => $item->getPrice(), "currency" => $currency->getCode(), "posData" => $invoice->getPosData(), "notificationURL" => $invoice->getNotificationUrl(), "transactionSpeed" => $invoice->getTransactionSpeed(), "fullNotifications" => $invoice->isFullNotifications(), "notificationEmail" => $invoice->getNotificationEmail(), "redirectURL" => $invoice->getRedirectUrl(), "orderID" => $invoice->getOrderId(), "itemDesc" => $item->getDescription(), "itemCode" => $item->getCode(), "physical" => $item->isPhysical(), "buyerName" => trim(sprintf("%s %s", $buyer->getFirstName(), $buyer->getLastName())), "buyerAddress1" => isset($buyerAddress[0]) ? $buyerAddress[0] : "", "buyerAddress2" => isset($buyerAddress[1]) ? $buyerAddress[1] : "", "buyerCity" => $buyer->getCity(), "buyerState" => $buyer->getState(), "buyerZip" => $buyer->getZip(), "buyerCountry" => $buyer->getCountry(), "buyerEmail" => $buyer->getEmail(), "buyerPhone" => $buyer->getPhone(), "guid" => \Bitpay\Util\Util::guid(), "token" => $this->token->getToken());
        $request->setBody(json_encode($body));
        $this->addIdentityHeader($request);
        $this->addSignatureHeader($request);
        $this->request = $request;
        $this->response = $this->sendRequest($request);
        $body = json_decode($this->response->getBody(), true);
        $error_message = false;
        $error_message = !empty($body["error"]) ? $body["error"] : $error_message;
        $error_message = !empty($body["errors"]) ? $body["errors"] : $error_message;
        $error_message = is_array($error_message) ? implode("\n", $error_message) : $error_message;
        if (false !== $error_message) {
            throw new \Exception($error_message);
        }
        $data = $body["data"];
        $invoiceToken = new \Bitpay\Token();
        $paymentUrls = new \Bitpay\PaymentUrlSet();
        $paymentUrlData = array();
        if (array_key_exists("paymentUrls", $data) && $data["paymentUrls"] && is_array($data["paymentUrls"])) {
            $paymentUrlData = $data["paymentUrls"];
        }
        $invoice->setToken($invoiceToken->setToken($data["token"]))->setId($data["id"])->setUrl($data["url"])->setStatus($data["status"])->setBtcPrice($data["btcPrice"])->setPrice($data["price"])->setInvoiceTime($data["invoiceTime"] / 1000)->setExpirationTime($data["expirationTime"] / 1000)->setCurrentTime($data["currentTime"] / 1000)->setBtcPaid($data["btcPaid"])->setRate($data["rate"])->setExceptionStatus($data["exceptionStatus"])->setPaymentUrls($paymentUrls->setUrls($paymentUrlData));
        return $invoice;
    }
    public function resendIpnNotifications($invoiceId)
    {
        $request = $this->createNewRequest();
        $request->setMethod(\Bitpay\Client\Request::METHOD_POST);
        $request->setPath(sprintf("/invoices/%s/notifications", $invoiceId));
        $request->setBody(json_encode(array("guid" => \Bitpay\Util\Util::guid(), "token" => $this->token->getToken())));
        $this->addIdentityHeader($request);
        $this->addSignatureHeader($request);
        $this->request = $request;
        $this->response = $this->sendRequest($request);
        return true;
    }
    public function getPublicKey()
    {
        return $this->publicKey;
    }
}

?>