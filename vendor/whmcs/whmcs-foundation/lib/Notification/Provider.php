<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Notification;

class Provider extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblnotificationproviders";
    public $fillable = array("name");
    protected $casts = array("settings" => "array", "active" => "boolean");
    public function createTable($drop = false)
    {
        $schemaBuilder = \WHMCS\Database\Capsule::schema();
        if ($drop) {
            $schemaBuilder->dropIfExists($this->getTable());
        }
        if (!$schemaBuilder->hasTable($this->getTable())) {
            $schemaBuilder->create($this->getTable(), function ($table) {
                $table->increments("id");
                $table->string("name", 255)->default("");
                $table->text("settings");
                $table->tinyInteger("active")->default(0);
                $table->timestamp("created_at")->default("0000-00-00 00:00:00");
                $table->timestamp("updated_at")->default("0000-00-00 00:00:00");
            });
        }
    }
    public function scopeActive($query)
    {
        return $query->where("active", "=", "1");
    }
    public function initObject()
    {
        $notificationsInterface = new \WHMCS\Module\Notification();
        if (!$notificationsInterface->load($this->name)) {
            throw new \WHMCS\Exception("Invalid provider");
        }
        $className = "WHMCS\\Module\\Notification\\" . $this->name . "\\" . $this->name;
        if (class_exists($className)) {
            $module = new $className();
            if (!$module instanceof \WHMCS\Module\Contracts\NotificationModuleInterface) {
                throw new \WHMCS\Exception(sprintf("Notification provider \"%s\" must implement %s", $this->name, "WHMCS\\Module\\Contracts\\NotificationModuleInterface"));
            }
            return $module;
        }
        throw new \WHMCS\Exception("Invalid provider class name");
    }
}

?>