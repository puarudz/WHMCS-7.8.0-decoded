<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Payment\PayMethod\Adapter;

abstract class BaseAdapterModel extends \WHMCS\Model\AbstractModel implements \WHMCS\Payment\Contracts\PayMethodAdapterInterface
{
    use \WHMCS\Payment\PayMethod\Traits\TypeTrait;
    use \WHMCS\Payment\PayMethod\Traits\PayMethodFactoryTrait;
    public $timestamps = true;
    public static function boot()
    {
        parent::boot();
        self::deleting(function (BaseAdapterModel $model) {
            if ($model instanceof \WHMCS\Payment\Contracts\SensitiveDataInterface) {
                $model->wipeSensitiveData();
                $model->save();
            }
        });
    }
    public function payMethod()
    {
        return $this->morphOne("WHMCS\\Payment\\PayMethod\\Model", "payment");
    }
    public function client()
    {
        return $this->payMethod->client();
    }
    public function contact()
    {
        return $this->payMethod->contact();
    }
    public function getEncryptionKey()
    {
        $key = "";
        if ($this->payMethod && $this->client) {
            $userId = $this->client->id;
            $cc_encryption_hash = \DI::make("config")["cc_encryption_hash"];
            $key = md5($cc_encryption_hash . $userId);
        }
        return $key;
    }
}

?>