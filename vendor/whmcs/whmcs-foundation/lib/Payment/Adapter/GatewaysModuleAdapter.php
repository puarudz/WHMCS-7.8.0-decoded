<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Payment\Adapter;

class GatewaysModuleAdapter extends AbstractAdapter
{
    public function __construct($name)
    {
        parent::__construct($name);
        $configuration = $this->getConfigurationFromDefinedFunctions();
        $this->setConfigurationParameters($configuration);
        $this->detectCapabilitiesFromDefinedFunctions();
        $type = $this->detectSolutionTypeFromCapabilities();
        $this->setSolutionType($type);
        return $this;
    }
    protected function detectSolutionTypeFromCapabilities()
    {
        $type = "";
        if (!$this->refundCapable) {
            $type = \WHMCS\Payment\Solutions::TYPE_ALTERNATE;
        } else {
            if ($this->captureCapable && $this->linkCapable) {
                $type = \WHMCS\Payment\Solutions::TYPE_MULTI;
            } else {
                if ($this->linkCapable && !$this->captureCapable) {
                    $type = \WHMCS\Payment\Solutions::TYPE_ALTERNATE;
                } else {
                    if ($this->captureCapable) {
                        $type = \WHMCS\Payment\Solutions::TYPE_GATEWAY;
                    } else {
                        throw new \WHMCS\Payment\Exception\InvalidModuleException(sprintf("Payment solution module '%s' does not implement either a capture or link function", $name));
                    }
                }
            }
        }
        return $type;
    }
    protected function detectCapabilitiesFromDefinedFunctions()
    {
        $name = $this->getName();
        if (function_exists($name . "_link")) {
            $this->linkCapable = true;
        }
        if (function_exists($name . "_capture")) {
            $this->captureCapable = true;
        }
        if (function_exists($name . "_storeremote")) {
            $this->remotePaymentDetailsStorageCapable = true;
        }
        if (function_exists($name . "_refund")) {
            $this->refundCapable = true;
        }
        return $this;
    }
    protected function getConfigurationFromDefinedFunctions()
    {
        $name = $this->getName();
        $config = array();
        if (function_exists($name . "_config")) {
            $config = call_user_func($name . "_config");
        } else {
            if (function_exists($name . "_activate")) {
                global $GATEWAYMODULE;
                global $GatewayFieldDefines;
                $GatewayFieldDefines = array();
                if (!function_exists("defineGatewayField")) {
                    function defineGatewayField($gateway, $type, $name, $defaultvalue, $friendlyname, $size, $description)
                    {
                        global $GatewayFieldDefines;
                        if ($type == "dropdown") {
                            $options = $description;
                            $description = "";
                        } else {
                            $options = "";
                        }
                        $GatewayFieldDefines[$name] = array("FriendlyName" => $friendlyname, "Type" => $type, "Size" => $size, "Description" => $description, "Value" => $defaultvalue, "Options" => $options);
                    }
                }
                $visable_name = isset($GATEWAYMODULE[$name . "visiblename"]) ? $GATEWAYMODULE[$name . "visiblename"] : $name;
                $GatewayFieldDefines["FriendlyName"] = array("Type" => "System", "Value" => $visable_name);
                if (isset($GATEWAYMODULE[$name . "notes"])) {
                    $GatewayFieldDefines["UsageNotes"] = array("Type" => "System", "Value" => $GATEWAYMODULE[$name . "notes"]);
                }
                call_user_func($name . "_activate");
                $config = $GatewayFieldDefines;
            } else {
                throw new \WHMCS\Payment\Exception\InvalidModuleException(sprintf("Payment solution module '%s' does not implement a configuration function", $name));
            }
        }
        return $config;
    }
    public function captureTransaction(array $params)
    {
        $name = $this->getName();
        if (!$this->isCaptureCapable()) {
            throw new \BadMethodCallException(sprintf("Payment solution module '%s' does not implement a capture function", $name));
        }
        return call_user_func($name . "_capture", $params);
    }
    public function refundTransaction(array $params)
    {
        $name = $this->getName();
        if (!$this->isRefundCapable()) {
            throw new \BadMethodCallException(sprintf("Payment solution module '%s' does not implement a refund function", $name));
        }
        return call_user_func($name . "_refund", $params);
    }
    public function storePaymentDetailsRemotely(array $params)
    {
        $name = $this->getName();
        if (!$this->isRemotePaymentDetailsStorageCapable()) {
            throw new \BadMethodCallException(sprintf("Payment solution module '%s' does not implement a storeremote function", $name));
        }
        return call_user_func($name . "_storeremote", $params);
    }
    public function getHtmlLink(array $params = NULL)
    {
        $name = $this->getName();
        if (!$this->isLinkCapable()) {
            return parent::getHtmlLink($params);
        }
        return call_user_func($name . "_link", $params);
    }
}

?>