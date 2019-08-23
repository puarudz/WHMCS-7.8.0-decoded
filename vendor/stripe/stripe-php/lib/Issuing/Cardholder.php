<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Stripe\Issuing;

/**
 * Class Cardholder
 *
 * @property string $id
 * @property string $object
 * @property mixed $billing
 * @property int $created
 * @property string $email
 * @property bool $livemode
 * @property \Stripe\StripeObject $metadata
 * @property string $name
 * @property string $phone_number
 * @property string $status
 * @property string $type
 *
 * @package Stripe\Issuing
 */
class Cardholder extends \Stripe\ApiResource
{
    const OBJECT_NAME = "issuing.cardholder";
    use \Stripe\ApiOperations\All;
    use \Stripe\ApiOperations\Create;
    use \Stripe\ApiOperations\Retrieve;
    use \Stripe\ApiOperations\Update;
}

?>