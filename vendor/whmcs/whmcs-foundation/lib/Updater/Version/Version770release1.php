<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version770release1 extends IncrementalVersion
{
    protected $updateActions = array("registerSslStatusSyncCronTask");
    public function registerSslStatusSyncCronTask()
    {
        \WHMCS\Cron\Task\SslStatusSync::register();
        return $this;
    }
}

?>