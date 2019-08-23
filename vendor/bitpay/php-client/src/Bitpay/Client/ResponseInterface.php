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
namespace Bitpay\Client;

/**
 * @package Bitpay
 */
interface ResponseInterface
{
    /**
     * @return string
     */
    public function getBody();
    /**
     * Returns the status code of the response
     *
     * @return integer
     */
    public function getStatusCode();
    /**
     * Returns a $key => $value array of http headers
     *
     * @return array
     */
    public function getHeaders();
}

?>