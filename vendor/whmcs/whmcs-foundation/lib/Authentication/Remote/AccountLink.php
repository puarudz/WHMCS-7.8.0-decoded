<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Authentication\Remote;

class AccountLink extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblauthn_account_links";
    protected $columnMap = array("clientId" => "client_id", "contactId" => "contact_id");
    public function createTable($drop = false)
    {
        $schemaBuilder = \Illuminate\Database\Capsule\Manager::schema();
        if ($drop) {
            $schemaBuilder->dropIfExists($this->getTable());
        }
        if (!$schemaBuilder->hasTable($this->getTable())) {
            $schemaBuilder->create($this->getTable(), function ($table) {
                $table->increments("id");
                $table->char("provider", 32);
                $table->char("remote_user_id")->nullable();
                $table->integer("client_id")->nullable();
                $table->integer("contact_id")->nullable();
                $table->text("metadata")->nullable();
                $table->nullableTimestamps();
                $table->unique(array("provider", "remote_user_id"));
            });
        }
    }
    public function client()
    {
        return $this->belongsTo("\\WHMCS\\User\\Client", "client_id");
    }
    public function contact()
    {
        return $this->belongsTo("\\WHMCS\\User\\Client\\Contact", "contact_id");
    }
    public function scopeViaProvider(\Illuminate\Database\Eloquent\Builder $query, Providers\AbstractRemoteAuthProvider $provider)
    {
        return $query->where("provider", "=", $provider::NAME);
    }
}

?>