<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Stripe;

/**
 * Class Discount
 *
 * @property string $object
 * @property Coupon $coupon
 * @property string $customer
 * @property int $end
 * @property int $start
 * @property string $subscription
 *
 * @package Stripe
 */
class Discount extends StripeObject
{
    const OBJECT_NAME = "discount";
}

?>