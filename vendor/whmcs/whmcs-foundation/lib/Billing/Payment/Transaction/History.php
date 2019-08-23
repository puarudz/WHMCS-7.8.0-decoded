<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Billing\Payment\Transaction;

class History extends \WHMCS\Model\AbstractModel
{
    protected $table = "tbltransaction_history";
    protected $dates = array("dateCreated", "dateUpdated");
    protected $booleans = array("completed");
    protected $columnMap = array("invoiceId" => "invoice_id", "transactionId" => "transaction_id", "remoteStatus" => "remote_status", "additionalInformation" => "additional_information", "currencyId" => "currency_id");
    protected $fillable = array("invoice_id", "gateway", "transaction_id");
    public static function boot()
    {
        parent::boot();
        static::addGlobalScope("order", function (\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->orderBy("id", "desc");
        });
    }
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
                $table->string("gateway", 32)->default("");
                $table->string("transaction_id", 255)->default("");
                $table->string("remote_status", 255)->default("");
                $table->boolean("completed")->default(0);
                $table->string("description", 255)->default("");
                $table->text("additional_information");
                $table->float("amount", 14, 2)->default(0);
                $table->unsignedInteger("currency_id")->default(0);
                $table->timestamp("created_at")->default("0000-00-00 00:00:00");
                $table->timestamp("updated_at")->default("0000-00-00 00:00:00");
                $table->index("transaction_id", "transaction_id");
            });
        }
    }
    public function invoice()
    {
        return $this->belongsTo("WHMCS\\Billing\\Invoice");
    }
    public function currency()
    {
        $this->hasOne("WHMCS\\Billing\\Currency");
    }
    public function getAdditionalInformationAttribute($information)
    {
        return json_decode($information, true);
    }
    public function setAdditionalInformationAttribute(array $value = array())
    {
        $this->attributes["additional_information"] = json_encode($value);
    }
}

?>