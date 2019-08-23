<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

/**
 * @license Copyright 2011-2014 BitPay Inc., MIT License 
 * see https://github.com/bitpay/php-bitpay-client/blob/master/LICENSE
 */
namespace Bitpay\Network;

/**
 *
 * @package Bitcore
 */
class Livenet implements NetworkInterface
{
    public function getName()
    {
        return 'livenet';
    }
    public function getAddressVersion()
    {
        return 0x0;
    }
    public function getApiHost()
    {
        return 'bitpay.com';
    }
    public function getApiPort()
    {
        return 443;
    }
}

?>