<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

/**
 * Bootstrapping File for phpseclib
 *
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 */
if (extension_loaded('mbstring')) {
    // 2 - MB_OVERLOAD_STRING
    if (ini_get('mbstring.func_overload') & 2) {
        throw new \UnexpectedValueException('Overloading of string functions using mbstring.func_overload ' . 'is not supported by phpseclib.');
    }
}

?>