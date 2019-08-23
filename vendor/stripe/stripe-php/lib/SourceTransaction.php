<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Stripe;

/**
 * Class SourceTransaction
 *
 * @property string $id
 * @property string $object
 * @property int $amount
 * @property int $created
 * @property string $customer_data
 * @property string $currency
 * @property string $type
 * @property mixed $ach_credit_transfer
 *
 * @package Stripe
 */
class SourceTransaction extends ApiResource
{
    const OBJECT_NAME = "source_transaction";
}

?>