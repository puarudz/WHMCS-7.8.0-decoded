<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Service;

class Ssl extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblsslorders";
    protected $columnMap = array("certificateType" => "certtype", "configurationData" => "configdata");
    protected $dates = array("completionDate");
    protected $appends = array("validationType");
    protected $fillable = array("userid", "serviceid", "addon_id", "module");
    public $timestamps = false;
    const STATUS_AWAITING_CONFIGURATION = "Awaiting Configuration";
    const STATUS_CONFIGURATION_SUBMITTED = "Configuration Submitted";
    const STATUS_AWAITING_ISSUANCE = "Awaiting Issuance";
    const STATUS_COMPLETED = "Completed";
    const STATUS_CANCELLED = "Cancelled";
    public function client()
    {
        return $this->belongsTo("WHMCS\\User\\Client", "userid");
    }
    public function service()
    {
        return $this->belongsTo("WHMCS\\Service\\Service", "serviceid");
    }
    public function addon()
    {
        return $this->belongsTo("WHMCS\\Service\\Addon", "addon_id");
    }
    public function getConfigurationDataAttribute($value)
    {
        $jsonDecodedValue = json_decode($value, true);
        if (!is_null($jsonDecodedValue) && json_last_error() === JSON_ERROR_NONE) {
            return $jsonDecodedValue;
        }
        return safe_unserialize($value);
    }
    public function setConfigurationDataAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes["configdata"] = json_encode($value);
        } else {
            $this->attributes["configdata"] = $value;
        }
    }
    public function getValidationTypeAttribute()
    {
        if ($this->addonId) {
            $serverType = $this->addon->productAddon->module;
            $productKey = $this->addon->productAddon->moduleConfiguration->where("setting_name", "=", "configoption1")->first();
            if ($productKey) {
                $productKey = $productKey->value;
            }
        } else {
            $serverType = $this->service->product->module;
            $productKey = $this->service->product->moduleConfigOption1;
        }
        if ($serverType != "marketconnect") {
            return "";
        }
        $symantec = new \WHMCS\MarketConnect\Promotion\Service\Symantec();
        $sslTypes = $symantec->getSslTypes();
        if (in_array($productKey, $sslTypes["ov"])) {
            return "OV";
        }
        if (in_array($productKey, $sslTypes["ev"])) {
            return "EV";
        }
        if (in_array($productKey, $sslTypes["wildcard"])) {
            return "Wildcard";
        }
        return "DV";
    }
    public function getConfigurationUrl()
    {
        return \App::getSystemUrl(true) . "configuressl.php?cert=" . md5($this->id);
    }
    public function getUpgradeUrl()
    {
        $uri = routePath("store-ssl-certificates-index");
        $evCertsEnabled = \WHMCS\Product\Product::marketConnect()->whereIn("configoption1", (new \WHMCS\MarketConnect\Promotion\Service\Symantec())->getSslTypes()["ev"])->visible()->first();
        if ($evCertsEnabled) {
            $uri = routePath("store-ssl-certificates-ev");
        }
        return $uri;
    }
}

?>