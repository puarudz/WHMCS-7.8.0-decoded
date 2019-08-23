<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\ApplicationLink;

class Client extends \WHMCS\Model\AbstractModel
{
    protected $table = "tbloauthserver_clients";
    protected $scopePivotTable = "tbloauthserver_client_scopes";
    protected $scopePivotId = "client_id";
    protected $characterSeparated = array(" " => array("grantTypes", "redirectUri"));
    public function createTable($drop = false)
    {
        $schemaBuilder = \Illuminate\Database\Capsule\Manager::schema();
        if ($drop) {
            $schemaBuilder->dropIfExists($this->getTable());
            $schemaBuilder->dropIfExists($this->scopePivotTable);
        }
        if (!$schemaBuilder->hasTable($this->getTable())) {
            $schemaBuilder->create($this->getTable(), function ($table) {
                $table->increments("id");
                $table->string("identifier", 80)->unique();
                $table->string("secret", 255)->default("");
                $table->string("redirect_uri", 2000)->default("");
                $table->string("grant_types", 80)->default("");
                $table->string("user_id", 255)->default("");
                $table->integer("service_id", false, true)->default(0);
                $table->string("name")->default("");
                $table->string("description")->default("");
                $table->string("logo_uri")->default("");
                $table->integer("rsa_key_pair_id", false, true)->default(0);
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
        $scope = new Scope();
        $scope->createTable();
    }
    public static function boot()
    {
        parent::boot();
        static::deleting(function ($model) {
            $model->scopes()->detach();
            $model->rsaKeyPair()->delete();
        });
    }
    public function scopes()
    {
        return $this->belongsToMany("\\WHMCS\\ApplicationLink\\Scope", $this->scopePivotTable, $this->scopePivotId, "scope_id");
    }
    public function getFormattedScopes()
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
    public function setSecretAttribute($value)
    {
        $config = \Config::self();
        $key = $config->cc_encryption_hash;
        $this->attributes["secret"] = $this->aesEncryptValue($value, $key);
    }
    public function getDecryptedSecretAttribute()
    {
        $config = \Config::self();
        $key = $config->cc_encryption_hash;
        return $this->aesDecryptValue($this->attributes["secret"], $key);
    }
    public function user()
    {
        return $this->belongsTo("\\WHMCS\\User\\Client", "user_id", "uuid");
    }
    public function service()
    {
        return $this->belongsTo("\\WHMCS\\Service\\Service");
    }
    public function rsaKeyPair()
    {
        return $this->belongsTo("\\WHMCS\\Security\\Encryption\\RsaKeyPair");
    }
    public function toArray()
    {
        $data = parent::toArray();
        $data["client_id"] = $data["identifier"];
        $data["scope"] = $this->scope;
        $data["client_secret"] = $this->decryptedSecret;
        return $data;
    }
    public static function generateClientId($prefix = NULL)
    {
        if (is_null($prefix)) {
            $prefix = strtoupper(substr(preg_replace("/[^0-9a-zA-Z\\-]/", "", str_replace(" ", "-", \WHMCS\Config\Setting::getValue("CompanyName"))), 0, 16));
        }
        return $prefix . "." . base64_encode(\phpseclib\Crypt\Random::string(16));
    }
    public static function generateSecret()
    {
        return base64_encode(\phpseclib\Crypt\Random::string(64));
    }
}

?>