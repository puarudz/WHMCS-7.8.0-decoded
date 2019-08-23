<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

/**
 * @license Copyright 2011-2015 BitPay Inc., MIT License
 * see https://github.com/bitpay/php-bitpay-client/blob/master/LICENSE
 */
namespace Bitpay\Network;

/**
 * @package Bitpay
 */
class Testnet implements NetworkInterface
{
    public function getName()
    {
        return 'testnet';
    }
    public function getAddressVersion()
    {
        return 0x6f;
    }
    public function getApiHost()
    {
        return 'test.bitpay.com';
    }
    public function getApiPort()
    {
        // SSL port
        return 443;
    }
}

?>