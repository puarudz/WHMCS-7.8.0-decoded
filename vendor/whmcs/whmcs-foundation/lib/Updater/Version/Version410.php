<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version410 extends IncrementalVersion
{
    protected function runUpdateCode()
    {
        $cc_encryption_hash = "";
        include ROOTDIR . DIRECTORY_SEPARATOR . "configuration.php";
        $query = "SELECT id,AES_DECRYPT(cardnum,'54X6zoYZZnS35o6m5gEwGmYC6" . $cc_encryption_hash . "') as cardnum,AES_DECRYPT(expdate,'54X6zoYZZnS35o6m5gEwGmYC6" . $cc_encryption_hash . "') as expdate,AES_DECRYPT(issuenumber,'54X6zoYZZnS35o6m5gEwGmYC6" . $cc_encryption_hash . "') as issuenumber,AES_DECRYPT(startdate,'54X6zoYZZnS35o6m5gEwGmYC6" . $cc_encryption_hash . "') as startdate FROM tblclients WHERE cardnum!=''";
        $result = mysql_query($query);
        while ($row = mysql_fetch_array($result)) {
            $userid = $row["id"];
            $cardnum = $row["cardnum"];
            $cardexp = $row["expdate"];
            $cardissuenum = $row["issuenumber"];
            $cardstart = $row["startdate"];
            $cardlastfour = substr($cardnum, -4);
            $cchash = md5($cc_encryption_hash . $userid);
            $query2 = "UPDATE tblclients SET cardlastfour='" . $cardlastfour . "',cardnum=AES_ENCRYPT('" . $cardnum . "','" . $cchash . "'),expdate=AES_ENCRYPT('" . $cardexp . "','" . $cchash . "'),startdate=AES_ENCRYPT('" . $cardstart . "','" . $cchash . "'),issuenumber=AES_ENCRYPT('" . $cardissuenum . "','" . $cchash . "') WHERE id='" . $userid . "'";
            $result2 = mysql_query($query2);
        }
        return $this;
    }
}

?>