<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Payment\PayMethod\Adapter;

abstract class CreditCardModel extends BaseAdapterModel implements \WHMCS\Payment\Contracts\CreditCardDetailsInterface
{
    use \Illuminate\Database\Eloquent\SoftDeletes;
    protected $table = "tblcreditcards";
    protected $dates = array("expiry_date", "deleted_at");
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
                $table->string("card_type", 255)->default("");
                $table->string("last_four", 255)->default("");
                $table->dateTime("expiry_date")->default("0000-00-00 00:00:00");
                $table->binary("card_data")->default("");
                $table->timestamp("created_at")->default("0000-00-00 00:00:00");
                $table->timestamp("updated_at")->default("0000-00-00 00:00:00");
                $table->softDeletes();
                $table->index("pay_method_id", "tblcreditcards_pay_method_id");
            });
        }
    }
    public function isExpired()
    {
        return $this->expiry_date->lt(\WHMCS\Carbon::now());
    }
}

?>