<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Payment\PayMethod\Traits;

trait RemoteBankAccountDetailsTrait
{
    use SensitiveDataTrait;
    public function getSensitiveDataAttributeName()
    {
        return "bank_data";
    }
    public function getAccountNumber()
    {
        return (string) $this->getSensitiveProperty("accountNumber");
    }
    public function setAccountNumber($value)
    {
        $this->setSensitiveProperty("accountNumber", substr($value, -4));
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
    public function getDisplayName()
    {
        $bankName = $this->getName();
        if (!$bankName) {
            $bankName = "Bank Account";
        }
        return implode("-", array($bankName, substr($this->getAccountNumber(), -4)));
    }
}

?>