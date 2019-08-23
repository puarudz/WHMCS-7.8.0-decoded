<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Gateway\CCAvenue;

class CCAvenue
{
    protected $key = NULL;
    protected $crypt = NULL;
    public static function factory($key)
    {
        $self = new self();
        $self->key = hex2bin(md5($key));
        $crypt = new \phpseclib\Crypt\Rijndael();
        $crypt->setIV(pack("C*", 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15));
        $crypt->setKey($self->key);
        $self->crypt = $crypt;
        return $self;
    }
    public function encrypt($plainText)
    {
        return bin2hex($this->crypt->encrypt($plainText));
    }
    public function decrypt($encryptedText)
    {
        return $this->crypt->decrypt(hex2bin($encryptedText));
    }
}

?>