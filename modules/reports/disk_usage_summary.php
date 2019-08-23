<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}
$reportdata["title"] = "Disk Space & Bandwidth Usage Summary";
$reportdata["description"] = "This report shows the Disk Space & Bandwidth Usage Statistics for hosting accounts";
$reportdata["tableheadings"] = array("Client Name/Domain", "Disk Usage", "Disk Limit", "% Used", "BW Usage", "BW Limit", "% Used");
if ($_GET["action"] == "updatestats") {
    require "../includes/modulefunctions.php";
    ServerUsageUpdate();
}
$query2 = "SELECT * FROM tblservers ORDER BY `name` ASC";
$result2 = full_query($query2);
while ($data = mysql_fetch_array($result2)) {
    $serverid = $data["id"];
    $name = $data["name"];
    $ipaddress = $data["ipaddress"];
    $reportdata["tablevalues"][] = array("**<B>{$name}</B> - {$ipaddress}");
    $query = "SELECT tblhosting.domain,tblhosting.diskusage,tblhosting.disklimit,tblhosting.bwlimit,tblhosting.bwusage,tblhosting.domainstatus,tblclients.firstname,tblclients.lastname,tblclients.companyname,tblhosting.lastupdate FROM tblhosting INNER JOIN tblclients ON tblclients.id=tblhosting.userid WHERE tblhosting.server=" . (int) $serverid . " AND tblhosting.lastupdate!='0000-00-00 00:00:00' AND (domainstatus='Active' OR domainstatus='Suspended') ORDER BY tblhosting.domain ASC";
    $result = full_query($query);
    while ($data = mysql_fetch_array($result)) {
        $firstname = $data["firstname"];
        $lastname = $data["lastname"];
        $companyname = $data["companyname"];
        $name = "{$firstname} {$lastname}";
        if ($companyname != "") {
            $name .= " (" . $companyname . ")";
        }
        $domain = $data["domain"];
        $diskusage = $data["diskusage"];
        $disklimit = $data["disklimit"];
        $bwusage = $data["bwusage"];
        $bwlimit = $data["bwlimit"];
        $lastupdate = $data["lastupdate"];
        if ($disklimit == "0") {
            $percentused = "N/A";
        } else {
            @($percentused = number_format($diskusage / $disklimit * 100, 0, '.', ''));
        }
        if ($disklimit == "0") {
            $disklimit = "Unlimited";
        }
        if ($bwlimit == "0") {
            $bwpercentused = "N/A";
        } else {
            @($bwpercentused = number_format($bwusage / $bwlimit * 100, 0, '.', ''));
        }
        if ($bwlimit == "0") {
            $bwlimit = "Unlimited";
        }
        if ($percentused != "N/A") {
            $percentused .= "%";
        }
        if ($bwpercentused != "N/A") {
            $bwpercentused .= "%";
        }
        $reportdata["tablevalues"][] = array("{$name}<br>{$domain}", "{$diskusage} MB", "{$disklimit} MB", "{$percentused}", "{$bwusage} MB", "{$bwlimit} MB", "{$bwpercentused}");
    }
}
$data["footertext"] = "<p>Disk Space Usage Stats Last Updated at " . fromMySQLDate($lastupdate, "time") . " - <a href=\"" . $_SERVER["PHP_SELF"] . "?report=" . $_GET["report"] . "&action=updatestats\">Update Now</a></p>";

?>