<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Payment\PayMethod;

class Collection extends \Illuminate\Database\Eloquent\Collection
{
    public function forGateway($gatewayModule)
    {
        $gateway = new \WHMCS\Module\Gateway();
        $isCcGateway = false;
        if ($gateway->load($gatewayModule)) {
            $isCcGateway = in_array($gateway->getParam("type"), array("CC", "OfflineCC"));
        }
        return $this->filter(function (\WHMCS\Payment\Contracts\PayMethodInterface $adapter) use($gatewayModule, $isCcGateway) {
            if ($adapter->getGateway() && $adapter->getGateway()->getLoadedModule() === $gatewayModule) {
                return true;
            }
            if ($isCcGateway) {
                return $adapter->isLocalCreditCard();
            }
            return false;
        });
    }
    public function creditCards()
    {
        return $this->filter(function (\WHMCS\Payment\Contracts\PayMethodInterface $adapter) {
            return $adapter->isCreditCard();
        });
    }
    public function localCreditCards()
    {
        return $this->filter(function (\WHMCS\Payment\Contracts\PayMethodInterface $adapter) {
            return $adapter->getType() === \WHMCS\Payment\Contracts\PayMethodTypeInterface::TYPE_CREDITCARD_LOCAL;
        });
    }
    public function bankAccounts()
    {
        return $this->filter(function (\WHMCS\Payment\Contracts\PayMethodInterface $adapter) {
            return $adapter->getType() === \WHMCS\Payment\Contracts\PayMethodTypeInterface::TYPE_BANK_ACCOUNT;
        });
    }
    public function validateGateways()
    {
        return $this->filter(function (Model $payMethod) {
            return !$payMethod->isUsingInactiveGateway();
        });
    }
    public function sortByExpiryDate($expiringFirst = false)
    {
        return $this->sort(function (Model $payMethod1, Model $payMethod2) use($expiringFirst) {
            if (!$payMethod1->isCreditCard() || !$payMethod2->isCreditCard()) {
                return 0;
            }
            $expiryDate1 = $payMethod1->payment->getExpiryDate();
            $expiryDate2 = $payMethod2->payment->getExpiryDate();
            $diff = ($expiryDate2 ? $expiryDate2->getTimestamp() : 0) - ($expiryDate1 ? $expiryDate1->getTimestamp() : 0);
            if ($expiringFirst) {
                $diff = 0 - $diff;
            }
            return $diff;
        });
    }
}

?>