<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version520 extends IncrementalVersion
{
    protected function runUpdateCode()
    {
        include_once ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "functions.php";
        $newips = array();
        $query = "SELECT value FROM tblconfiguration WHERE setting='APIAllowedIPs'";
        $result = mysql_query($query);
        $data = mysql_fetch_array($result);
        $apiips = $data["value"];
        $apiips = explode("\n", $apiips);
        foreach ($apiips as $ip) {
            $newips[] = array("ip" => trim($ip), "note" => "");
        }
        $query = "UPDATE tblconfiguration SET value='" . mysql_real_escape_string(safe_serialize($newips)) . "' WHERE setting='APIAllowedIPs'";
        $result = mysql_query($query);
        $query = "SELECT value FROM tblconfiguration WHERE setting='SystemURL'";
        $result = mysql_query($query);
        $data = mysql_fetch_array($result);
        $sysurl = $data["value"];
        if ($sysurl == "http://www.yourdomain.com/whmcs/") {
            $sysurl = "http://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
            $sysurl = str_replace("?step=5", "", $sysurl);
            $sysurl = str_replace("install/install.php", "", $sysurl);
            $sysurl = str_replace("install2/install.php", "", $sysurl);
            $query = "UPDATE tblconfiguration SET value='" . mysql_real_escape_string($sysurl) . "' WHERE setting='SystemURL'";
            $result = mysql_query($query);
        }
        $query = "SELECT id,password FROM tblticketdepartments";
        $result = mysql_query($query);
        while ($row = mysql_fetch_array($result)) {
            $id = $row["id"];
            $value = encrypt($row["password"]);
            $query2 = "UPDATE tblticketdepartments SET password='" . $value . "' WHERE id='" . $id . "'";
            $result2 = mysql_query($query2);
        }
        $query = "SELECT value FROM tblconfiguration WHERE setting='FTPBackupPassword'";
        $result = mysql_query($query);
        $data = mysql_fetch_array($result);
        $ftppass = encrypt($data["value"]);
        $query = "UPDATE tblconfiguration SET value='" . $ftppass . "' WHERE setting='FTPBackupPassword'";
        $result = mysql_query($query);
        $query = "SELECT value FROM tblconfiguration WHERE setting='SMTPPassword'";
        $result = mysql_query($query);
        $data = mysql_fetch_array($result);
        $smtppass = encrypt($data["value"]);
        $query = "UPDATE tblconfiguration SET value='" . $smtppass . "' WHERE setting='SMTPPassword'";
        $result = mysql_query($query);
        return $this;
    }
}

?>