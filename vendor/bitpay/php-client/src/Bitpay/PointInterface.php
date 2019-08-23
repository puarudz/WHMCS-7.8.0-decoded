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
interface PointInterface extends \Serializable
{
    /**
     * Infinity constant
     *
     * @var string
     */
    const INFINITY = 'inf';
    /**
     * @return string
     */
    public function getX();
    /**
     * @return string
     */
    public function getY();
    /**
     * @return boolean
     */
    public function isInfinity();
}

?>