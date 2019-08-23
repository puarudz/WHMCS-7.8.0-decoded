<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version330 extends IncrementalVersion
{
    protected function runUpdateCode()
    {
        $cc_encryption_hash = "";
        include ROOTDIR . DIRECTORY_SEPARATOR . "configuration.php";
        $query = "SELECT id,AES_DECRYPT(cardnum,'" . $cc_encryption_hash . "') as cardnum,AES_DECRYPT(expdate,'" . $cc_encryption_hash . "') as expdate,AES_DECRYPT(issuenumber,'" . $cc_encryption_hash . "') as issuenumber,AES_DECRYPT(startdate,'" . $cc_encryption_hash . "') as startdate FROM tblclients";
        $result = mysql_query($query);
        while ($row = mysql_fetch_array($result)) {
            $id = $row["id"];
            $cardnum = $row["cardnum"];
            $cardexp = $row["expdate"];
            $cardissuenum = $row["issuenumber"];
            $cardstart = $row["startdate"];
            $query2 = "UPDATE tblclients SET cardnum=AES_ENCRYPT('" . $cardnum . "','54X6zoYZZnS35o6m5gEwGmYC6" . $cc_encryption_hash . "'),expdate=AES_ENCRYPT('" . $cardexp . "','54X6zoYZZnS35o6m5gEwGmYC6" . $cc_encryption_hash . "'),startdate=AES_ENCRYPT('" . $cardstart . "','54X6zoYZZnS35o6m5gEwGmYC6" . $cc_encryption_hash . "'),issuenumber=AES_ENCRYPT('" . $cardissuenum . "','54X6zoYZZnS35o6m5gEwGmYC6" . $cc_encryption_hash . "') WHERE id='" . $id . "'";
            $result2 = mysql_query($query2);
        }
        return $this;
    }
}

?>