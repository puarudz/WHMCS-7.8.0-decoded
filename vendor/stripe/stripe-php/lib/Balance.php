<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Stripe;

/**
 * Class Balance
 *
 * @property string $object
 * @property array $available
 * @property array $connect_reserved
 * @property bool $livemode
 * @property array $pending
 *
 * @package Stripe
 */
class Balance extends SingletonApiResource
{
    const OBJECT_NAME = "balance";
    /**
     * @param array|string|null $opts
     *
     * @return Balance
     */
    public static function retrieve($opts = null)
    {
        return self::_singletonRetrieve($opts);
    }
}

?>