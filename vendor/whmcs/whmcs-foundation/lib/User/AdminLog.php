<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\User;

class AdminLog extends \WHMCS\Model\AbstractModel
{
    protected $table = "tbladminlog";
    protected $columnMap = array("username" => "adminusername");
    public $timestamps = false;
    public $unique = array("sessionid");
    public function admin()
    {
        return $this->belongsTo("\\WHMCS\\User\\Admin", "adminusername", "username");
    }
    public function scopeOnline($query)
    {
        return $query->where("lastvisit", ">", \WHMCS\Carbon::now()->subMinutes(15))->groupBy("adminusername")->orderBy("lastvisit");
    }
}

?>