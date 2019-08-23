<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Billing\Invoice;

class Snapshot extends \WHMCS\Model\AbstractModel
{
    protected $table = "mod_invoicedata";
    public $timestamps = false;
    protected $primaryKey = "invoiceid";
    public $unique = array("invoiceid");
    protected $columnMap = array("invoiceId" => "invoiceid", "clientsDetails" => "clientsdetails", "customFields" => "customfields");
    protected $fillable = array("invoiceid", "clientsdetails", "customfields");
    public function createTable($drop = false)
    {
        $schemaBuilder = \WHMCS\Database\Capsule::schema();
        if ($drop) {
            $schemaBuilder->dropIfExists($this->getTable());
        }
        if (!$schemaBuilder->hasTable($this->getTable())) {
            $schemaBuilder->create($this->getTable(), function ($table) {
                $table->unsignedInteger("invoiceid")->default(0);
                $table->text("clientsdetails");
                $table->text("customfields");
                $table->charset = "utf8";
                $table->collation = "utf8_unicode_ci";
            });
        }
    }
    public function invoice()
    {
        return $this->belongsTo("WHMCS\\Billing\\Invoice", "invoiceid");
    }
    public function getClientsDetailsAttribute()
    {
        $rawClientsDetails = $this->attributes["clientsdetails"];
        $clientsDetails = json_decode($rawClientsDetails, true);
        if (!is_null($clientsDetails) && json_last_error() === JSON_ERROR_NONE) {
            return $clientsDetails;
        }
        return safe_unserialize($rawClientsDetails);
    }
    public function getCustomFieldsAttribute()
    {
        $rawCustomFields = $this->attributes["customfields"];
        $customFields = json_decode($rawCustomFields, true);
        if (!is_null($customFields) && json_last_error() === JSON_ERROR_NONE) {
            return $customFields;
        }
        return safe_unserialize($rawCustomFields);
    }
    public function setClientsDetailsAttribute(array $clientsDetails)
    {
        $this->attributes["clientsDetails"] = json_encode($clientsDetails);
    }
    public function setCustomFieldsAttribute(array $customFields)
    {
        $this->attributes["customFields"] = json_encode($customFields);
    }
}

?>