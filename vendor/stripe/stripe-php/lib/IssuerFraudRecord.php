<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Stripe;

/**
 * Class IssuerFraudRecord
 *
 * @property string $id
 * @property string $object
 * @property string $charge
 * @property int $created
 * @property int $post_date
 * @property string $fraud_type
 * @property bool $livemode
 *
 * @package Stripe
 */
class IssuerFraudRecord extends ApiResource
{
    const OBJECT_NAME = "issuer_fraud_record";
    use ApiOperations\All;
    use ApiOperations\Retrieve;
}

?>