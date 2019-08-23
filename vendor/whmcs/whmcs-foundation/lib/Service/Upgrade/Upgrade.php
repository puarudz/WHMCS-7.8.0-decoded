<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Service\Upgrade;

class Upgrade extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblupgrades";
    protected $columnMap = array("userId" => "userid", "orderId" => "orderid", "entityId" => "relid", "originalValue" => "originalvalue", "newValue" => "newvalue", "upgradeAmount" => "amount", "recurringChange" => "recurringchange");
    protected $dates = array("date");
    protected $casts = array("calculation" => "array");
    public $timestamps = false;
    public $currency = NULL;
    public $applyTax = false;
    public $localisedNewCycle = NULL;
    const TYPE_SERVICE = "service";
    const TYPE_ADDON = "addon";
    const TYPE_PACKAGE = "package";
    const TYPE_CONFIGOPTIONS = "configoptions";
    public function service()
    {
        return $this->hasOne("WHMCS\\Service\\Service", "id", "relid");
    }
    public function addon()
    {
        return $this->hasOne("WHMCS\\Service\\Addon", "id", "relid");
    }
    public function originalProduct()
    {
        return $this->hasOne("WHMCS\\Product\\Product", "id", "originalvalue");
    }
    public function newProduct()
    {
        return $this->hasOne("WHMCS\\Product\\Product", "id", "newvalue");
    }
    public function originalAddon()
    {
        return $this->hasOne("WHMCS\\Product\\Addon", "id", "originalvalue");
    }
    public function newAddon()
    {
        return $this->hasOne("WHMCS\\Product\\Addon", "id", "newvalue");
    }
}

?>