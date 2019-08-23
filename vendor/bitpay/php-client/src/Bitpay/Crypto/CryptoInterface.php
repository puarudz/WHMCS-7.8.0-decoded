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
namespace Bitpay\Crypto;

/**
 * All crypto extensions MUST support this interface
 */
interface CryptoInterface
{
    /**
     * If the users system supports the cryto extension, this should return
     * true, otherwise it should return false.
     *
     * @return boolean
     */
    public static function hasSupport();
    /**
     * @return array
     */
    public function getAlgos();
}

?>