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
class Customnet implements NetworkInterface
{
    protected $host_url;
    protected $host_port;
    public $isPortRequiredInUrl;
    public function __construct($url, $port, $isPortRequiredInUrl = false)
    {
        $this->host_url = $url;
        $this->host_port = $port;
        $this->isPortRequiredInUrl = $isPortRequiredInUrl;
    }
    public function getName()
    {
        return 'Custom Network';
    }
    public function getAddressVersion()
    {
        return 0x0;
    }
    public function getApiHost()
    {
        return $this->host_url;
    }
    public function getApiPort()
    {
        return $this->host_port;
    }
}

?>