<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Payment\PayMethod\Traits;

trait BankAccountDetailsTrait
{
    use SensitiveDataTrait;
    public function getSensitiveDataAttributeName()
    {
        return "bank_data";
    }
    public function getRoutingNumber()
    {
        return (string) $this->getSensitiveProperty("routingNumber");
    }
    public function setRoutingNumber($value)
    {
        $this->setSensitiveProperty("routingNumber", $value);
        return $this;
    }
    public function getAccountNumber()
    {
        return (string) $this->getSensitiveProperty("accountNumber");
    }
    public function setAccountNumber($value)
    {
        $this->setSensitiveProperty("accountNumber", $value);
        return $this;
    }
    public function getAccountType()
    {
        return (string) $this->acct_type;
    }
    public function setAccountType($value)
    {
        $this->acct_type = $value;
        return $this;
    }
    public function getBankName()
    {
        return (string) $this->bank_name;
    }
    public function setBankName($value)
    {
        $this->bank_name = $value;
        return $this;
    }
    public function getAccountHolderName()
    {
        return (string) $this->getSensitiveProperty("accountHolderName");
    }
    public function setAccountHolderName($value)
    {
        $this->setSensitiveProperty("accountHolderName", $value);
        return $this;
    }
    public function validateRequiredValuesPreSave()
    {
        if (!$this->getAccountHolderName()) {
            throw new \WHMCS\Exception("Account holder name is required");
        }
        if (!$this->getRoutingNumber()) {
            throw new \WHMCS\Exception("Routing number is required");
        }
        if (!$this->getAccountNumber()) {
            throw new \WHMCS\Exception("Account number is required");
        }
        return $this;
    }
    public function getDisplayName()
    {
        $bankName = $this->getBankName();
        if (!$bankName) {
            $bankName = "Bank Account";
        }
        return implode("-", array($bankName, substr($this->getAccountNumber(), -4)));
    }
}

?>