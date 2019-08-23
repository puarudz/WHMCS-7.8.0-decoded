<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Fraud\MaxMind;

class Payment extends \WHMCS\Model\AbstractModel
{
    protected $table = "mod_maxmind_payment";
    protected $fillable = array("processor", "whmcs_module");
    public function createTable($drop = false)
    {
        $schemaBuilder = \WHMCS\Database\Capsule::schema();
        if ($drop) {
            $schemaBuilder->dropIfExists($this->getTable());
        }
        if (!$schemaBuilder->hasTable($this->getTable())) {
            $schemaBuilder->create($this->getTable(), function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->increments("id");
                $table->char("processor", 128)->default("");
                $table->char("whmcs_module", 128)->default("");
                $table->timestamp("created_at")->default("0000-00-00 00:00:00");
                $table->timestamp("updated_at")->default("0000-00-00 00:00:00");
                $table->unique("whmcs_module");
                $table->index(array("processor", "whmcs_module"), "index_modules");
            });
            \WHMCS\Database\Capsule::table($this->getTable())->insert($this->getDefaultTableData());
        }
    }
    protected function getDefaultTableData()
    {
        return array(array("processor" => "authorizenet", "whmcs_module" => "acceptjs"), array("processor" => "authorizenet", "whmcs_module" => "authorize"), array("processor" => "authorizenet", "whmcs_module" => "authorizecim"), array("processor" => "authorizenet", "whmcs_module" => "authorizeecheck"), array("processor" => "authorizenet", "whmcs_module" => "planetauthorize"), array("processor" => "bluepay", "whmcs_module" => "bluepay"), array("processor" => "bluepay", "whmcs_module" => "bluepayecheck"), array("processor" => "bluepay", "whmcs_module" => "bluepayremote"), array("processor" => "ccavenue", "whmcs_module" => "ccavenue"), array("processor" => "ccavenue", "whmcs_module" => "ccavenuev2"), array("processor" => "eway", "whmcs_module" => "ewaytokens"), array("processor" => "mollie", "whmcs_module" => "mollieideal"), array("processor" => "moneris_solutions", "whmcs_module" => "moneris"), array("processor" => "moneris_solutions", "whmcs_module" => "monerisvault"), array("processor" => "skrill", "whmcs_module" => "moneybookers"), array("processor" => "skrill", "whmcs_module" => "skrill"), array("processor" => "optimal_payments", "whmcs_module" => "optimalpayments"), array("processor" => "paypal", "whmcs_module" => "payflowpro"), array("processor" => "paypal", "whmcs_module" => "paypal"), array("processor" => "paypal", "whmcs_module" => "paypalexpress"), array("processor" => "paypal", "whmcs_module" => "paypalpaymentspro"), array("processor" => "paypal", "whmcs_module" => "paypalpaymentsproref"), array("processor" => "payza", "whmcs_module" => "payza"), array("processor" => "psigate", "whmcs_module" => "psigate"), array("processor" => "securetrading", "whmcs_module" => "securetrading"), array("processor" => "stripe", "whmcs_module" => "stripe"), array("processor" => "sagepay", "whmcs_module" => "sagepayrepeats"), array("processor" => "sagepay", "whmcs_module" => "sagepaytokens"), array("processor" => "sagepay", "whmcs_module" => "protx"), array("processor" => "sagepay", "whmcs_module" => "protxvspform"), array("processor" => "usa_epay", "whmcs_module" => "usaepay"), array("processor" => "worldpay", "whmcs_module" => "worldpay"), array("processor" => "worldpay", "whmcs_module" => "worldpayfuturepay"), array("processor" => "worldpay", "whmcs_module" => "worldpayinvisible"), array("processor" => "worldpay", "whmcs_module" => "worldpayinvisiblexml"));
    }
    public static function getPaymentModule($paymentModule)
    {
        try {
            $processor = self::where("whmcs_module", $paymentModule)->firstOrFail()->processor;
        } catch (\Exception $e) {
            $processor = "other";
        }
        return $processor;
    }
}

?>