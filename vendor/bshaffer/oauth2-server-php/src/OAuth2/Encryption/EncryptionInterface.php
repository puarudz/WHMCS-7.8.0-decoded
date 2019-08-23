<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace OAuth2\Encryption;

interface EncryptionInterface
{
    public function encode($payload, $key, $algorithm = null);
    public function decode($payload, $key, $algorithm = null);
    public function urlSafeB64Encode($data);
    public function urlSafeB64Decode($b64);
}

?>