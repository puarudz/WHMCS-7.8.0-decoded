<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Storage;

final class EncryptedTransientStorage extends AbstractDataStorage
{
    private $sessionKey = "transient_module_data";
    protected function readDataFromStorage()
    {
        $allModulesSessionData = \WHMCS\Session::get($this->sessionKey);
        if (empty($allModulesSessionData)) {
            return array();
        }
        $encryption = new \WHMCS\Security\Encryption\Aes();
        $encryption->setKey(hash("sha256", \DI::make("config")->cc_encryption_hash));
        $allModulesData = json_decode($encryption->decrypt($allModulesSessionData), true);
        if (!is_array($allModulesData)) {
            return array();
        }
        return $allModulesData;
    }
    protected function writeDataToStorage(array $allModulesData)
    {
        $encryption = new \WHMCS\Security\Encryption\Aes();
        $encryption->setKey(hash("sha256", \DI::make("config")->cc_encryption_hash));
        \WHMCS\Session::set($this->sessionKey, $encryption->encrypt(json_encode($allModulesData)));
    }
}

?>