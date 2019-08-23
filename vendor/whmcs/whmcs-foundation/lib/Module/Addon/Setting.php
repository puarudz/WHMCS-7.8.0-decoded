<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Addon;

class Setting extends \WHMCS\Model\AbstractModel
{
    protected $table = "tbladdonmodules";
    protected $fillable = array("module", "setting");
    public $timestamps = false;
    public function scopeModule($query, $module)
    {
        return $query->where("module", $module);
    }
}

?>