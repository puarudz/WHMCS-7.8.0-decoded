<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version350 extends IncrementalVersion
{
    protected $runUpdateCodeBeforeDatabase = true;
    protected function runUpdateCode()
    {
        $query = "ALTER TABLE tblupgrades ADD `orderid` INT( 1 ) NOT NULL AFTER `id`";
        $result = mysql_query($query);
        $query = "SELECT * FROM tblorders WHERE upgradeids!=''";
        $result = mysql_query($query);
        while ($data = mysql_fetch_array($result)) {
            $orderid = $data["id"];
            $upgradeids = $data["upgradeids"];
            $upgradeids = explode(",", $upgradeids);
            foreach ($upgradeids as $upgradeid) {
                if ($upgradeid) {
                    $query2 = "UPDATE tblupgrades SET orderid='" . $orderid . "' WHERE id='" . $upgradeid . "'";
                    $result2 = mysql_query($query2);
                }
            }
        }
        return $this;
    }
}

?>