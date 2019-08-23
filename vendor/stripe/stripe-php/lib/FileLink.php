<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Stripe;

/**
 * Class FileLink
 *
 * @property string $id
 * @property string $object
 * @property int $created
 * @property bool $expired
 * @property int $expires_at
 * @property string $file
 * @property bool $livemode
 * @property StripeObject $metadata
 * @property string $url
 *
 * @package Stripe
 */
class FileLink extends ApiResource
{
    const OBJECT_NAME = "file_link";
    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;
}

?>