<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Stripe\Util;

/**
 * A very basic implementation of LoggerInterface that has just enough
 * functionality that it can be the default for this library.
 */
class DefaultLogger implements LoggerInterface
{
    public function error($message, array $context = [])
    {
        if (count($context) > 0) {
            throw new \Exception('DefaultLogger does not currently implement context. Please implement if you need it.');
        }
        error_log($message);
    }
}

?>