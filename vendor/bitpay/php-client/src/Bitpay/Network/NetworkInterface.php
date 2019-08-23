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
interface NetworkInterface
{
    /**
     * Name of network, currently on livenet and testnet
     *
     * @return string
     */
    public function getName();
    /**
     * @return string
     */
    public function getAddressVersion();
    /**
     * The host that is used to interact with this network
     *
     * @see https://github.com/bitpay/insight
     * @see https://github.com/bitpay/insight-api
     *
     * @return string
     */
    public function getApiHost();
    /**
     * The port of the host
     *
     * @return integer
     */
    public function getApiPort();
}

?>