<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Gateway\TCO;

class CallbackRequestHelper
{
    private $request = NULL;
    private $gatewayParams = array();
    private $useInline = NULL;
    private $invoiceId = 0;
    public function __construct(\WHMCS\Http\Message\ServerRequest $request)
    {
        $this->setRequest($request)->initialTcoGateway();
        $invoiceId = $this->getInvoiceId();
        if ($invoiceId) {
            $this->initialTcoGateway($invoiceId);
        }
    }
    private function initialTcoGateway($invoiceId = NULL)
    {
        if ($invoiceId) {
            $params = getGatewayVariables("tco", $invoiceId);
        } else {
            $params = getGatewayVariables("tco");
        }
        if (empty($params["type"])) {
            throw new \RuntimeException("Module Not Activated");
        }
        $this->setGatewayParams($params);
        return $this;
    }
    public function shouldInlineBeUsed()
    {
        if (!is_null($this->useInline)) {
            return $this->useInline;
        }
        $request = $this->getRequest();
        $params = $this->getGatewayParams();
        $rawItemId = $request->get("item_id_1");
        $itemId = (int) preg_replace("/[^0-9]/", "", $rawItemId);
        $notificationType = $request->get("message_type");
        $stdFlowTypes = array("INVOICE_STATUS_CHANGED", "ORDER_CREATED", "RECURRING_INSTALLMENT_SUCCESS");
        if (in_array($notificationType, $stdFlowTypes)) {
            if ($rawItemId != $itemId) {
                $this->useInline = true;
            } else {
                $this->useInline = false;
            }
        } else {
            if ($params["integrationMethod"] == "inline") {
                $this->useInline = true;
            } else {
                $this->useInline = false;
            }
        }
        return $this->useInline;
    }
    public function getInvoiceId()
    {
        if ($this->invoiceId) {
            return $this->invoiceId;
        }
        $request = $this->getRequest();
        $invoiceId = $request->get("x_invoice_num", null);
        if (!$invoiceId && $this->shouldInlineBeUsed()) {
            $invoiceId = $request->get("merchant_order_id", null);
        }
        return $invoiceId;
    }
    public function isClientCallback()
    {
        $request = $this->getRequest();
        if ($request->get("x_invoice_num", null) || $request->get("merchant_order_id", null)) {
            return true;
        }
        return false;
    }
    public function getCallable()
    {
        if ($this->shouldInlineBeUsed()) {
            $class = "WHMCS\\Module\\Gateway\\TCO\\Inline";
        } else {
            $class = "WHMCS\\Module\\Gateway\\TCO\\Standard";
        }
        if ($this->isClientCallback()) {
            $method = "clientCallback";
        } else {
            $method = "callback";
        }
        return array($class, $method);
    }
    protected function getRequest()
    {
        return $this->request;
    }
    protected function setRequest(\WHMCS\Http\Message\ServerRequest $request)
    {
        $this->request = $request;
        return $this;
    }
    private function setGatewayParams(array $params)
    {
        $this->gatewayParams = $params;
        return $this;
    }
    public function getGatewayParams()
    {
        return $this->gatewayParams;
    }
}

?>