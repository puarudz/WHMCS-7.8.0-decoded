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
namespace Bitpay;

/**
 * @package Bitcore
 */
interface KeyInterface extends \Serializable
{
    /**
     * Generates a new key
     */
    public function generate();
    /**
     * @return boolean
     */
    public function isValid();
}

?>