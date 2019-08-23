<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

// This file was auto-generated from sdk-root/src/data/machinelearning/2014-12-12/waiters-2.json
return ['version' => 2, 'waiters' => ['DataSourceAvailable' => ['delay' => 30, 'operation' => 'DescribeDataSources', 'maxAttempts' => 60, 'acceptors' => [['expected' => 'COMPLETED', 'matcher' => 'pathAll', 'state' => 'success', 'argument' => 'Results[].Status'], ['expected' => 'FAILED', 'matcher' => 'pathAny', 'state' => 'failure', 'argument' => 'Results[].Status']]], 'MLModelAvailable' => ['delay' => 30, 'operation' => 'DescribeMLModels', 'maxAttempts' => 60, 'acceptors' => [['expected' => 'COMPLETED', 'matcher' => 'pathAll', 'state' => 'success', 'argument' => 'Results[].Status'], ['expected' => 'FAILED', 'matcher' => 'pathAny', 'state' => 'failure', 'argument' => 'Results[].Status']]], 'EvaluationAvailable' => ['delay' => 30, 'operation' => 'DescribeEvaluations', 'maxAttempts' => 60, 'acceptors' => [['expected' => 'COMPLETED', 'matcher' => 'pathAll', 'state' => 'success', 'argument' => 'Results[].Status'], ['expected' => 'FAILED', 'matcher' => 'pathAny', 'state' => 'failure', 'argument' => 'Results[].Status']]], 'BatchPredictionAvailable' => ['delay' => 30, 'operation' => 'DescribeBatchPredictions', 'maxAttempts' => 60, 'acceptors' => [['expected' => 'COMPLETED', 'matcher' => 'pathAll', 'state' => 'success', 'argument' => 'Results[].Status'], ['expected' => 'FAILED', 'matcher' => 'pathAny', 'state' => 'failure', 'argument' => 'Results[].Status']]]]];

?>