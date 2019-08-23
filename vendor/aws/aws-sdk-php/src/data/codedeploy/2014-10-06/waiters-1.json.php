<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

// This file was auto-generated from sdk-root/src/data/codedeploy/2014-10-06/waiters-1.json
return ['version' => 2, 'waiters' => ['DeploymentSuccessful' => ['delay' => 15, 'operation' => 'GetDeployment', 'maxAttempts' => 120, 'acceptors' => [['expected' => 'Succeeded', 'matcher' => 'path', 'state' => 'success', 'argument' => 'deploymentInfo.status'], ['expected' => 'Failed', 'matcher' => 'path', 'state' => 'failure', 'argument' => 'deploymentInfo.status'], ['expected' => 'Stopped', 'matcher' => 'path', 'state' => 'failure', 'argument' => 'deploymentInfo.status']]]]];

?>