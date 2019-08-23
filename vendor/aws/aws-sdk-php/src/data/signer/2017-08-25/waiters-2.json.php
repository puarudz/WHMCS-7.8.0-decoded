<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

// This file was auto-generated from sdk-root/src/data/signer/2017-08-25/waiters-2.json
return ['version' => 2, 'waiters' => ['SuccessfulSigningJob' => ['delay' => 20, 'operation' => 'DescribeSigningJob', 'maxAttempts' => 25, 'acceptors' => [['expected' => 'Succeeded', 'matcher' => 'path', 'state' => 'success', 'argument' => 'status'], ['expected' => 'Failed', 'matcher' => 'path', 'state' => 'failure', 'argument' => 'status'], ['expected' => 'ResourceNotFoundException', 'matcher' => 'error', 'state' => 'failure']]]]];

?>