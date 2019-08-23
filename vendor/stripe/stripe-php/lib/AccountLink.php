<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Stripe;

/**
 * Class AccountLink
 *
 * @property string $object
 * @property int $created
 * @property int $expires_at
 * @property string $url
 *
 * @package Stripe
 */
class AccountLink extends ApiResource
{
    const OBJECT_NAME = "account_link";
    use ApiOperations\Create;
}

?>