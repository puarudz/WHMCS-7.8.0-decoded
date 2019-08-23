<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

// This file was auto-generated from sdk-root/src/data/sqs/2012-11-05/waiters-2.json
return ['version' => 2, 'waiters' => ['QueueExists' => ['acceptors' => [['expected' => 200, 'matcher' => 'status', 'state' => 'success'], ['expected' => 'QueueDoesNotExist', 'matcher' => 'error', 'state' => 'retry']], 'delay' => 5, 'maxAttempts' => 40, 'operation' => 'GetQueueUrl']]];

?>