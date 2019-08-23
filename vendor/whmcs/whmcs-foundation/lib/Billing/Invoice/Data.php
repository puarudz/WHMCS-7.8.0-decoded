<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Billing\Invoice;

class Data extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblinvoicedata";
    protected $fillable = array("invoice_id", "country");
    public function createTable($drop = false)
    {
        $schemaBuilder = \WHMCS\Database\Capsule::schema();
        if ($drop) {
            $schemaBuilder->dropIfExists($this->getTable());
        }
        if (!$schemaBuilder->hasTable($this->getTable())) {
            $schemaBuilder->create($this->getTable(), function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->increments("id");
                $table->unsignedInteger("invoice_id")->default(0);
                $table->string("country", 2)->default("");
                $table->timestamp("created_at")->default("0000-00-00 00:00:00");
                $table->timestamp("updated_at")->default("0000-00-00 00:00:00");
                $table->charset = "utf8";
                $table->collation = "utf8_unicode_ci";
                $table->unique("invoice_id");
            });
        }
    }
    public function invoice()
    {
        return $this->belongsTo("WHMCS\\Billing\\Invoice");
    }
}

?>