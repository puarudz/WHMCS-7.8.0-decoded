<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

// This file was auto-generated from sdk-root/src/data/dynamodb/2012-08-10/paginators-1.json
return ['pagination' => ['BatchGetItem' => ['input_token' => 'RequestItems', 'output_token' => 'UnprocessedKeys'], 'ListTables' => ['input_token' => 'ExclusiveStartTableName', 'limit_key' => 'Limit', 'output_token' => 'LastEvaluatedTableName', 'result_key' => 'TableNames'], 'Query' => ['input_token' => 'ExclusiveStartKey', 'limit_key' => 'Limit', 'output_token' => 'LastEvaluatedKey', 'result_key' => 'Items'], 'Scan' => ['input_token' => 'ExclusiveStartKey', 'limit_key' => 'Limit', 'output_token' => 'LastEvaluatedKey', 'result_key' => 'Items']]];

?>