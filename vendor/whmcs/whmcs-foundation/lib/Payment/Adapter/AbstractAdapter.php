<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Payment\Adapter;

abstract class AbstractAdapter implements AdapterInterface
{
    protected $type = "";
    protected $name = "";
    protected $config = array();
    protected $captureCapable = false;
    protected $refundCapable = false;
    protected $remotePaymentDetailsStorageCapable = false;
    protected $linkCapable = false;
    public function __construct($name = "")
    {
        if (!$name) {
            $name = $this->getName();
        }
        $this->setName($name);
        return $this;
    }
    public function setName($name)
    {
        if (!is_string($name) || $name == "") {
            throw new \InvalidArgumentException("Name must be a non-empty string");
        }
        $this->name = $name;
        return $this;
    }
    public function getName()
    {
        return $this->name;
    }
    public function getConfigurationParameters()
    {
        return $this->config;
    }
    public function setConfigurationParameters(array $configuration)
    {
        $this->config = $configuration;
    }
    public function getSolutionType()
    {
        return $this->type;
    }
    public function setSolutionType($type)
    {
        if (!\WHMCS\Payment\Solutions::isValidSolutionType($type)) {
            throw new \InvalidArgumentException(sprintf("Unknown Payment Solution type '%s'", $type));
        }
        $this->type = $type;
    }
    public function isLinkCapable()
    {
        return $this->linkCapable;
    }
    public function isCaptureCapable()
    {
        return $this->captureCapable;
    }
    public function isRefundCapable()
    {
        return $this->refundCapable;
    }
    public function isRemotePaymentDetailsStorageCapable()
    {
        return $this->remotePaymentDetailsStorageCapable;
    }
    public function getHtmlLink(array $params = NULL)
    {
        foreach (array("systemurl", "invoiceid", "langpaynow") as $element) {
            if (!isset($params[$element])) {
                $params[$element] = "";
            }
        }
        $html = "<form method=\"post\" action=\"%s/creditcard.php\" name=\"paymentfrm\">" . "<input type=\"hidden\" name=\"invoiceid\" value=\"%s\">" . "<input type=\"submit\" value=\"%s\">" . "</form>";
        return sprintf($html, $params["systemurl"], $params["invoiceid"], $params["langpaynow"]);
    }
    public function captureTransaction(array $params)
    {
        throw new \WHMCS\Payment\Exception\MethodNotImplemented(sprintf("Method %s has been called, but is not defined in class %s", "captureTransaction", get_class($this)));
    }
    public function refundTransaction(array $params)
    {
        throw new \WHMCS\Payment\Exception\MethodNotImplemented(sprintf("Method %s has been called, but is not defined in class %s", "refundTransaction", get_class($this)));
    }
    public function storePaymentDetailsRemotely(array $params)
    {
        throw new \WHMCS\Payment\Exception\MethodNotImplemented(sprintf("Method %s has been called, but is not defined in class %s", "storePaymentDetailsRemotely", get_class($this)));
    }
}

?>