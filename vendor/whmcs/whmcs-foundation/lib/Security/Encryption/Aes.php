<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Security\Encryption;

class Aes extends \phpseclib\Crypt\AES
{
    public function __construct($mode = \phpseclib\Crypt\Base::MODE_CBC)
    {
        parent::__construct($mode);
        $this->setKeyLength(256);
    }
    public function encrypt($plaintext)
    {
        $binaryCipherText = parent::encrypt($plaintext);
        $hexCipherText = bin2hex($binaryCipherText);
        return $hexCipherText;
    }
    public function decrypt($ciphertext)
    {
        $binaryCipherText = $this->hex2bin($ciphertext);
        $plainText = parent::decrypt($binaryCipherText);
        return $plainText;
    }
    public function hex2bin($hexInput)
    {
        if (function_exists("hex2bin")) {
            return hex2bin($hexInput);
        }
        $len = strlen($hexInput);
        if ($len % 2 != 0) {
            return false;
        }
        if (strspn($hexInput, "0123456789abcdefABCDEF") != $len) {
            return false;
        }
        $output = "";
        $i = 0;
        while ($i < $len) {
            $output .= pack("H*", substr($hexInput, $i, 2));
            $i += 2;
        }
        return $output;
    }
}

?>