<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Payment\PayMethod\Adapter;

abstract class BankAccountModel extends BaseAdapterModel
{
    use \Illuminate\Database\Eloquent\SoftDeletes;
    protected $table = "tblbankaccts";
    protected $dates = array("deleted_at");
    public function createTable($drop = false)
    {
        $schemaBuilder = \WHMCS\Database\Capsule::schema();
        if ($drop) {
            $schemaBuilder->dropIfExists($this->getTable());
        }
        if (!$schemaBuilder->hasTable($this->getTable())) {
            $schemaBuilder->create($this->getTable(), function ($table) {
                $table->increments("id");
                $table->integer("pay_method_id")->default(0);
                $table->string("bank_name", 255)->default("");
                $table->string("acct_type", 255)->default("");
                $table->binary("bank_data")->default("");
                $table->timestamp("created_at")->default("0000-00-00 00:00:00");
                $table->timestamp("updated_at")->default("0000-00-00 00:00:00");
                $table->softDeletes();
                $table->index("pay_method_id", "tblbankaccts_pay_method_id");
            });
        }
    }
    public static function boot()
    {
        parent::boot();
        static::saving(function (BankAccountModel $model) {
            $sensitiveData = $model->getSensitiveData();
            $name = $model->getSensitiveDataAttributeName();
            $model->{$name} = $sensitiveData;
        });
    }
    public function getSensitiveDataAttributeName()
    {
        return "bank_data";
    }
}

?>