<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\ApplicationLink\Scope;

class UserAuthorization extends \WHMCS\Model\AbstractModel
{
    protected $table = "tbloauthserver_user_authz";
    protected $primaryKey = "id";
    protected $scopePivotTable = "tbloauthserver_user_authz_scopes";
    protected $scopePivotId = "user_authz_id";
    protected $dates = array("expires");
    public function createTable($drop = false)
    {
        $schemaBuilder = \Illuminate\Database\Capsule\Manager::schema();
        if ($drop) {
            $schemaBuilder->dropIfExists($this->getTable());
            $schemaBuilder->dropIfExists($this->scopePivotTable);
        }
        if (!$schemaBuilder->hasTable($this->getTable())) {
            $schemaBuilder->create($this->getTable(), function ($table) {
                $table->integer("id", true);
                $table->string("user_id", 255)->default("");
                $table->integer("client_id", false, true)->default(0);
                $table->timestamp("expires")->default("0000-00-00 00:00:00");
                $table->timestamp("created_at")->default("0000-00-00 00:00:00");
                $table->timestamp("updated_at")->default("0000-00-00 00:00:00");
            });
        }
        if (!$schemaBuilder->hasTable($this->scopePivotTable)) {
            $self = $this;
            $schemaBuilder->create($this->scopePivotTable, function ($table) use($self) {
                $table->integer($self->scopePivotId, false, true)->default(0);
                $table->integer("scope_id", false, true)->default(0);
                $table->index(array($self->scopePivotId, "scope_id"), (string) $self->scopePivotTable . "_scope_id_index");
            });
        }
        $scope = new \WHMCS\ApplicationLink\Scope();
        $scope->createTable();
    }
    public function scopes()
    {
        return $this->belongsToMany("\\WHMCS\\ApplicationLink\\Scope", $this->scopePivotTable, $this->scopePivotId, "scope_id");
    }
    protected function getFormattedScopes()
    {
        $scopes = $this->scopes()->get();
        $spaceDelimitedScopes = "";
        foreach ($scopes as $scope) {
            $spaceDelimitedScopes .= " " . $scope->scope;
        }
        return trim($spaceDelimitedScopes);
    }
    public function getScopeAttribute()
    {
        return $this->getFormattedScopes();
    }
    public function user()
    {
        return $this->belongsTo("\\WHMCS\\User\\Client", "user_id", "uuid");
    }
    public function client()
    {
        return $this->belongsTo("\\WHMCS\\ApplicationLink\\Client", "client_id", "identifier");
    }
    public function toArray()
    {
        $data = parent::toArray();
        $data["expires"] = $this->expires->timestamp;
        $data["scope"] = $this->scope;
        return $data;
    }
}

?>