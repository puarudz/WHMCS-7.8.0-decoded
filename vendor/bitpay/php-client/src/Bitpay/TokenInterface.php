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
namespace Bitpay;

/**
 * @package Bitpay
 */
interface TokenInterface
{
    /**
     * @return string
     */
    public function getToken();
    /**
     * @return string
     */
    public function getResource();
    /**
     * @return string
     */
    public function getFacade();
    /**
     * @return \DateTime
     */
    public function getCreatedAt();
    /**
     * @return array
     */
    public function getPolicies();
    /**
     * @return string
     */
    public function getPairingCode();
    /**
     * @return \DateTime
     */
    public function getPairingExpiration();
}

?>