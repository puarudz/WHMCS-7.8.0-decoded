<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Product;

class Server extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblservers";
    public $timestamps = false;
    protected $columnMap = array("ipAddress" => "ipaddress", "assignedIpAddresses" => "assignedips", "monthlyCost" => "monthlycost", "dataCenter" => "noc", "statusAddress" => "statusaddress", "nameserverOne" => "nameserver1", "nameserverOneIpAddress" => "nameserver1ip", "nameserverTwo" => "nameserver2", "nameserverTwoIpAddress" => "nameserver2ip", "nameserverThree" => "nameserver3", "nameserverThreeIpAddress" => "nameserver3ip", "nameserverFour" => "nameserver4", "nameserverFourIpAddress" => "nameserver4ip", "nameserverFive" => "nameserver5", "nameserverFiveIpAddress" => "nameserver5ip", "maxAccounts" => "maxaccounts", "accessHash" => "accesshash");
    protected $appends = array("activeAccountsCount");
    public static function boot()
    {
        parent::boot();
        static::addGlobalScope("ordered", function (\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->orderBy("tblservers.name");
        });
        static::deleted(function (Server $server) {
            Server\Remote::where("server_id", $server->id)->delete();
        });
    }
    public function services()
    {
        return $this->hasMany("\\WHMCS\\Service\\Service", "server");
    }
    public function addons()
    {
        return $this->hasMany("\\WHMCS\\Service\\Addon", "server");
    }
    public function scopeOfModule(\Illuminate\Database\Eloquent\Builder $query, $module)
    {
        return $query->where("type", $module);
    }
    public function scopeEnabled(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where("disabled", 0);
    }
    public function scopeDefault(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where("active", 1);
    }
    public function getActiveAccountsCountAttribute()
    {
        $activeStatuses = array("Active", "Suspended");
        return $this->services()->whereIn("domainstatus", $activeStatuses)->count() + $this->addons()->whereIn("status", $activeStatuses)->count();
    }
    public function getModuleInterface()
    {
        $moduleInterface = new \WHMCS\Module\Server();
        $moduleInterface->load($this->type);
        return $moduleInterface;
    }
    public function remote()
    {
        return $this->hasOne("WHMCS\\Product\\Server\\Remote");
    }
}

?>