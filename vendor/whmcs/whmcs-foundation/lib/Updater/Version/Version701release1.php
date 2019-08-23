<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version701release1 extends IncrementalVersion
{
    protected $updateActions = array("removeAdminForceSSLSetting");
    public function removeAdminForceSSLSetting()
    {
        \WHMCS\Database\Capsule::table("tblconfiguration")->where("setting", "=", "AdminForceSSL")->delete();
        return $this;
    }
}

?>