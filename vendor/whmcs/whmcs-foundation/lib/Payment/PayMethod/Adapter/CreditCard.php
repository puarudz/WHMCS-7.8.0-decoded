<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Payment\PayMethod\Adapter;

class CreditCard extends CreditCardModel
{
    use \WHMCS\Payment\PayMethod\Traits\CreditCardDetailsTrait {
        getRawSensitiveData as ccGetRawSensitiveData;
    }
    public static function boot()
    {
        parent::boot();
        static::saving(function (CreditCard $model) {
            if (!(new \WHMCS\Gateways())->isLocalCreditCardStorageEnabled(!defined("ADMINAREA"))) {
                $model->setLastFour("");
                $model->setCardType("");
                $model->expiry_date = "";
            }
            $sensitiveData = $model->getSensitiveData();
            $name = $model->getSensitiveDataAttributeName();
            $model->{$name} = $sensitiveData;
        });
    }
    protected function getRawSensitiveData()
    {
        if (!(new \WHMCS\Gateways())->isLocalCreditCardStorageEnabled(!defined("ADMINAREA"))) {
            return null;
        }
        return $this->ccGetRawSensitiveData();
    }
    public function getDisplayName()
    {
        return implode("-", array($this->card_type, $this->last_four));
    }
}

?>