<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version630rc1 extends IncrementalVersion
{
    protected $updateActions = array("insertUpgradeTimeForMDE");
    public function insertUpgradeTimeForMDE()
    {
        \WHMCS\Config\Setting::setValue("MDEFromTime", \WHMCS\Carbon::now());
        return $this;
    }
}

?>