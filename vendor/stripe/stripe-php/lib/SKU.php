<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Stripe;

/**
 * Class SKU
 *
 * @property string $id
 * @property string $object
 * @property bool $active
 * @property mixed $attributes
 * @property int $created
 * @property string $currency
 * @property string $image
 * @property mixed $inventory
 * @property bool $livemode
 * @property StripeObject $metadata
 * @property mixed $package_dimensions
 * @property int $price
 * @property string $product
 * @property int $updated
 *
 * @package Stripe
 */
class SKU extends ApiResource
{
    const OBJECT_NAME = "sku";
    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Delete;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;
}

?>