<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Gateway\BP\Key;

class PrivateKey extends \Bitpay\PrivateKey
{
    public function setHex($hex)
    {
        $this->hex = $hex;
        $this->dec = \Bitpay\Util\Util::decodeHex($this->hex);
    }
}

?>