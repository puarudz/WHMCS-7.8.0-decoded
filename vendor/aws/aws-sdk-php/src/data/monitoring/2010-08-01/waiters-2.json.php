<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

// This file was auto-generated from sdk-root/src/data/monitoring/2010-08-01/waiters-2.json
return ['version' => 2, 'waiters' => ['AlarmExists' => ['delay' => 5, 'maxAttempts' => 40, 'operation' => 'DescribeAlarms', 'acceptors' => [['matcher' => 'path', 'expected' => true, 'argument' => 'length(MetricAlarms[]) > `0`', 'state' => 'success']]]]];

?>