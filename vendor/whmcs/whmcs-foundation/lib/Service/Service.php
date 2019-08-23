<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Service;

class Service extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblhosting";
    protected $columnMap = array("clientId" => "userid", "productId" => "packageid", "serverId" => "server", "registrationDate" => "regdate", "paymentGateway" => "paymentmethod", "status" => "domainstatus", "promotionId" => "promoid", "overrideAutoSuspend" => "overideautosuspend", "overrideSuspendUntilDate" => "overidesuspenduntil", "bandwidthUsage" => "bwusage", "bandwidthLimit" => "bwlimit", "lastUpdateDate" => "lastupdate", "firstPaymentAmount" => "firstpaymentamount", "recurringAmount" => "amount", "recurringFee" => "amount");
    protected $dates = array("registrationDate", "overrideSuspendUntilDate", "lastUpdateDate");
    protected $booleans = array("overideautosuspend");
    protected $appends = array("serviceProperties");
    protected $hidden = array("password");
    const STATUS_PENDING = "Pending";
    const STATUS_ACTIVE = "Active";
    const STATUS_SUSPENDED = "Suspended";
    public function scopeUserId($query, $userId)
    {
        return $query->where("userid", "=", $userId);
    }
    public function scopeActive($query)
    {
        return $query->where("domainstatus", self::STATUS_ACTIVE);
    }
    public function scopeMarketConnect($query)
    {
        $marketConnectProductIds = \WHMCS\Product\Product::marketConnect()->pluck("id");
        return $query->whereIn("packageid", $marketConnectProductIds);
    }
    public function scopeIsConsideredActive(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->whereIn("domainstatus", array(Service::STATUS_ACTIVE, Service::STATUS_SUSPENDED));
    }
    public function scopeIsNotRecurring(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->whereIn("billingcycle", array("Free", "Free Account", "One Time"));
    }
    public function client()
    {
        return $this->belongsTo("WHMCS\\User\\Client", "userid");
    }
    public function product()
    {
        return $this->belongsTo("WHMCS\\Product\\Product", "packageid");
    }
    public function addons()
    {
        return $this->hasMany("WHMCS\\Service\\Addon", "hostingid");
    }
    public function order()
    {
        return $this->belongsTo("WHMCS\\Order\\Order", "orderid");
    }
    public function cancellationRequests()
    {
        return $this->hasMany("WHMCS\\Service\\CancellationRequest", "relid");
    }
    public function ssl()
    {
        return $this->hasMany("WHMCS\\Service\\Ssl", "serviceid")->where("addon_id", "=", 0);
    }
    public function hasAvailableUpgrades()
    {
        return 0 < $this->product->upgradeProducts->count();
    }
    public function failedActions()
    {
        return $this->hasMany("WHMCS\\Module\\Queue", "service_id")->where("service_type", "=", "service");
    }
    public function customFieldValues()
    {
        return $this->hasMany("WHMCS\\CustomField\\CustomFieldValue", "relid");
    }
    protected function getCustomFieldType()
    {
        return "product";
    }
    protected function getCustomFieldRelId()
    {
        return $this->product->id;
    }
    public function getServicePropertiesAttribute()
    {
        return new Properties($this);
    }
    public function canBeUpgraded()
    {
        return $this->status == "Active";
    }
    public function isService()
    {
        return true;
    }
    public function isAddon()
    {
        return false;
    }
    public function serverModel()
    {
        return $this->hasOne("\\WHMCS\\Product\\Server", "id", "server");
    }
}

?>