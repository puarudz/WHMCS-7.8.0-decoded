<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\File\Migration\Processor;

interface MigrationProcessorInterface
{
    public function migrate();
    public function getMigrationProgress();
}

?>