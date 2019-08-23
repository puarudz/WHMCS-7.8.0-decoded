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
namespace Bitpay\Util;

interface CurveParameterInterface
{
    public function aHex();
    public function bHex();
    public function gHex();
    public function gxHex();
    public function gyHex();
    public function hHex();
    public function nHex();
    public function pHex();
}

?>