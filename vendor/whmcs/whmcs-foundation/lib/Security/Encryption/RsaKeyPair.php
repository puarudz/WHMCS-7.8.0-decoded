<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Security\Encryption;

class RsaKeyPair extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblrsakeypairs";
    public function createTable($drop = false)
    {
        $schemaBuilder = \Illuminate\Database\Capsule\Manager::schema();
        if ($drop) {
            $schemaBuilder->dropIfExists($this->getTable());
        }
        if (!$schemaBuilder->hasTable($this->getTable())) {
            $schemaBuilder->create($this->getTable(), function ($table) {
                $table->integer("id", true);
                $table->string("identifier", 96)->default("");
                $table->text("private_key")->default("");
                $table->text("public_key")->default("");
                $table->string("algorithm", 16)->default("RS256");
                $table->string("name", 255)->default("");
                $table->string("description", 255)->default("");
                $table->timestamp("created_at")->default("0000-00-00 00:00:00");
                $table->timestamp("updated_at")->default("0000-00-00 00:00:00");
            });
        }
    }
    public function setPrivateKeyAttribute($value)
    {
        $config = \Config::self();
        $key = $config->cc_encryption_hash;
        $this->attributes["private_key"] = $this->aesEncryptValue($value, $key);
    }
    public function getDecryptedPrivateKeyAttribute()
    {
        $config = \Config::self();
        $key = $config->cc_encryption_hash;
        return $this->aesDecryptValue($this->attributes["private_key"], $key);
    }
    public static function factoryKeyPair($keySize = 4096)
    {
        $rsa = new \phpseclib\Crypt\RSA();
        $rsa->setHash("sha256");
        $keys = $rsa->createKey($keySize);
        $keyPair = new static();
        $keyPair->identifier = bin2hex(\phpseclib\Crypt\Random::string(32));
        $keyPair->privateKey = $keys["privatekey"];
        $keyPair->publicKey = $keys["publickey"];
        $keyPair->save();
        return $keyPair;
    }
    public function getPublicRsaAttribute()
    {
        $rsa = new \phpseclib\Crypt\RSA();
        $rsa->loadKey($this->publicKey);
        $rsa->setHash("sha256");
        return $rsa;
    }
    public function getPrivateRsaAttribute()
    {
        $rsa = new \phpseclib\Crypt\RSA();
        $rsa->loadKey($this->decryptedPrivateKey);
        $rsa->setHash("sha256");
        return $rsa;
    }
}

?>