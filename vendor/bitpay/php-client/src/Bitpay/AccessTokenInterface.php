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
 * Interface for an access token for the given client
 *
 * @package Bitpay
 */
interface AccessTokenInterface
{
    /**
     * @return string
     */
    public function getId();
    /**
     * @return string
     */
    public function getEmail();
    /**
     * @return string
     */
    public function getLabel();
}

?>