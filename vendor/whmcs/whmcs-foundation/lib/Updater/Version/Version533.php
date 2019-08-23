<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version533 extends IncrementalVersion
{
    protected $runUpdateCodeBeforeDatabase = true;
    protected function runUpdateCode()
    {
        $query = "ALTER TABLE  `tblsslorders` ADD  `provisiondate` DATE NOT NULL AFTER  `configdata`";
        mysql_query($query);
        return $this;
    }
}

?>