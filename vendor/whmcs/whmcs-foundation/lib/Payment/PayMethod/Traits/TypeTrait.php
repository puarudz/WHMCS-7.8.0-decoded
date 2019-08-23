<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Payment\PayMethod\Traits;

trait TypeTrait
{
    public function getType($instance = NULL)
    {
        if (!$instance) {
            $instance = $this;
        }
        $thisType = substr(strrchr(get_class($instance), "\\"), 1);
        $types = $this->getSupportedPayMethodTypes();
        if (in_array($thisType, $types)) {
            return $thisType;
        }
        foreach ($types as $type) {
            if ($thisType instanceof $type) {
                return $type;
            }
        }
        throw new \RuntimeException("Indeterminate type " . get_class($instance));
    }
    public function getTypeDescription($instance = NULL)
    {
        $type = $this->getType($instance);
        switch ($type) {
            case \WHMCS\Payment\Contracts\PayMethodTypeInterface::TYPE_BANK_ACCOUNT:
                $description = "Bank Account";
                break;
            case \WHMCS\Payment\Contracts\PayMethodTypeInterface::TYPE_REMOTE_BANK_ACCOUNT:
                $description = "Payment Account";
                break;
            case \WHMCS\Payment\Contracts\PayMethodTypeInterface::TYPE_CREDITCARD_LOCAL:
            default:
                $description = "Credit Card";
        }
        return $description;
    }
    public function isManageable()
    {
        $type = $this->getType();
        if ($type == \WHMCS\Payment\Contracts\PayMethodTypeInterface::TYPE_CREDITCARD_REMOTE_UNMANAGED) {
            return false;
        }
        return true;
    }
    public function isLocalCreditCard()
    {
        return $this->getType() == \WHMCS\Payment\Contracts\PayMethodTypeInterface::TYPE_CREDITCARD_LOCAL;
    }
    public function isRemoteCreditCard()
    {
        return $this->getType() == \WHMCS\Payment\Contracts\PayMethodTypeInterface::TYPE_CREDITCARD_REMOTE_MANAGED;
    }
    public function isCreditCard()
    {
        return $this->isLocalCreditCard() || $this->isRemoteCreditCard();
    }
    public function isBankAccount()
    {
        return $this->getType() == \WHMCS\Payment\Contracts\PayMethodTypeInterface::TYPE_BANK_ACCOUNT;
    }
    public function isRemoteBankAccount()
    {
        return $this->getType() == \WHMCS\Payment\Contracts\PayMethodTypeInterface::TYPE_REMOTE_BANK_ACCOUNT;
    }
    public function getSupportedPayMethodTypes()
    {
        return array(\WHMCS\Payment\Contracts\PayMethodTypeInterface::TYPE_CREDITCARD_LOCAL, \WHMCS\Payment\Contracts\PayMethodTypeInterface::TYPE_CREDITCARD_REMOTE_MANAGED, \WHMCS\Payment\Contracts\PayMethodTypeInterface::TYPE_CREDITCARD_REMOTE_UNMANAGED, \WHMCS\Payment\Contracts\PayMethodTypeInterface::TYPE_BANK_ACCOUNT, \WHMCS\Payment\Contracts\PayMethodTypeInterface::TYPE_REMOTE_BANK_ACCOUNT);
    }
}

?>