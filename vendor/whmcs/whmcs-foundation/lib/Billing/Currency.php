<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Billing;

class Currency extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblcurrencies";
    public $timestamps = false;
    const DEFAULT_CURRENCY_ID = 1;
    public function scopeDefaultCurrency($query)
    {
        return $query->where("default", 1);
    }
    public function scopeDefaultSorting($query)
    {
        return $query->orderBy("default", "desc")->orderBy("code");
    }
}

?>