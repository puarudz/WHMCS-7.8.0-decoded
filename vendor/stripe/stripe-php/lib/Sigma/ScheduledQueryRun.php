<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Stripe\Sigma;

/**
 * Class Authorization
 *
 * @property string $id
 * @property string $object
 * @property int $created
 * @property int $data_load_time
 * @property string $error
 * @property \Stripe\FileUpload $file
 * @property bool $livemode
 * @property int $result_available_until
 * @property string $sql
 * @property string $status
 * @property string $title
 *
 * @package Stripe\Sigma
 */
class ScheduledQueryRun extends \Stripe\ApiResource
{
    const OBJECT_NAME = "scheduled_query_run";
    use \Stripe\ApiOperations\All;
    use \Stripe\ApiOperations\Retrieve;
    public static function classUrl()
    {
        return "/v1/sigma/scheduled_query_runs";
    }
}

?>