<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\ApplicationLink;

class ApplicationLink extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblapplinks";
    protected $primaryKey = "id";
    protected $fillable = array("module_type", "module_name");
    public function createTable($drop = false)
    {
        $schemaBuilder = \Illuminate\Database\Capsule\Manager::schema();
        if ($drop) {
            $schemaBuilder->dropIfExists($this->getTable());
        }
        if (!$schemaBuilder->hasTable($this->getTable())) {
            $schemaBuilder->create($this->getTable(), function ($table) {
                $table->increments("id");
                $table->string("module_type", 20)->default("");
                $table->string("module_name", 50)->default("");
                $table->tinyInteger("is_enabled")->default(0);
                $table->timestamp("created_at")->default("0000-00-00 00:00:00");
                $table->timestamp("updated_at")->default("0000-00-00 00:00:00");
            });
        }
    }
    public function links()
    {
        return $this->hasMany("\\WHMCS\\ApplicationLink\\Links", "applink_id");
    }
    public function log()
    {
        return $this->hasMany("\\WHMCS\\ApplicationLink\\Log", "applink_id");
    }
}

?>