<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Stripe;

/**
 * Class WebhookEndpoint
 *
 * @property string $id
 * @property string $object
 * @property int $created
 * @property string[] $enabled_events
 * @property bool $livemode
 * @property string $secret
 * @property string $status
 * @property string $url
 *
 * @package Stripe
 */
class WebhookEndpoint extends ApiResource
{
    const OBJECT_NAME = "webhook_endpoint";
    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Delete;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;
}

?>