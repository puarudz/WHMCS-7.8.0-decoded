<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

chdir(dirname(__FILE__));
//require_once '../library/HTMLPurifier.path.php';
shell_exec('php ../maintenance/generate-schema-cache.php');
require_once '../library/HTMLPurifier.path.php';
require_once 'HTMLPurifier.includes.php';
$begin = xdebug_memory_usage();
$schema = HTMLPurifier_ConfigSchema::makeFromSerial();
echo xdebug_memory_usage() - $begin;
// vim: et sw=4 sts=4

?>