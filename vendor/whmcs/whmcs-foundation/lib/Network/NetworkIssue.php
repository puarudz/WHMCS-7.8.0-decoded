<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Network;

class NetworkIssue extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblnetworkissues";
    protected $columnMap = array("affectedType" => "type", "affectedOther" => "affecting", "affectedServerId" => "server", "lastUpdateDate" => "lastupdate");
    protected $dates = array("startdate", "enddate", "lastupdate");
    public static function boot()
    {
        parent::boot();
        static::addGlobalScope("order", function (\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->orderBy("tblnetworkissues.startdate", "DESC")->orderBy("tblnetworkissues.enddate")->orderBy("tblnetworkissues.id");
        });
    }
}

?>