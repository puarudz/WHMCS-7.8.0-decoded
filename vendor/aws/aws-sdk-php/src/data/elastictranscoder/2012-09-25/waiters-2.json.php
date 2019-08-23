<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

// This file was auto-generated from sdk-root/src/data/elastictranscoder/2012-09-25/waiters-2.json
return ['version' => 2, 'waiters' => ['JobComplete' => ['delay' => 30, 'operation' => 'ReadJob', 'maxAttempts' => 120, 'acceptors' => [['expected' => 'Complete', 'matcher' => 'path', 'state' => 'success', 'argument' => 'Job.Status'], ['expected' => 'Canceled', 'matcher' => 'path', 'state' => 'failure', 'argument' => 'Job.Status'], ['expected' => 'Error', 'matcher' => 'path', 'state' => 'failure', 'argument' => 'Job.Status']]]]];

?>