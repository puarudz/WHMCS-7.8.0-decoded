<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module;

class RegistrarSetting extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblregistrars";
    public $timestamps = false;
    protected $fillable = array("registrar");
    public function scopeRegistrar(\Illuminate\Database\Eloquent\Builder $query, $registrarName)
    {
        return $query->where("registrar", "=", $registrarName);
    }
    public function scopeSetting(\Illuminate\Database\Eloquent\Builder $query, $registrarSettingName)
    {
        return $query->where("setting", "=", $registrarSettingName);
    }
    public function getValueAttribute($value)
    {
        if (!empty($value)) {
            $value = $this->decrypt($value);
        }
        return $value;
    }
    public function setValueAttribute($value)
    {
        $this->attributes["value"] = $this->encrypt($value);
    }
}

?>