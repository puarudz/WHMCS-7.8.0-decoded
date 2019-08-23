<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Stripe\Terminal;

/**
 * Class Reader
 *
 * @property string $id
 * @property string $object
 * @property bool $deleted
 * @property string $device_sw_version
 * @property string $device_type
 * @property string $ip_address
 * @property string $label
 * @property string $location
 * @property string $serial_number
 * @property string $status
 *
 * @package Stripe\Terminal
 */
class Reader extends \Stripe\ApiResource
{
    const OBJECT_NAME = "terminal.reader";
    use \Stripe\ApiOperations\All;
    use \Stripe\ApiOperations\Create;
    use \Stripe\ApiOperations\Delete;
    use \Stripe\ApiOperations\Retrieve;
    use \Stripe\ApiOperations\Update;
}

?>