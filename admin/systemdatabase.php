<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Database Status");
$aInt->title = $aInt->lang("utilities", "dbstatus");
$aInt->sidebar = "utilities";
$aInt->icon = "dbbackups";
$aInt->requiredFiles(array("backupfunctions"));
$whmcs = App::self();
$optimize = $whmcs->get_req_var("optimize");
$dlbackup = $whmcs->get_req_var("dlbackup");
$optimized = $whmcs->get_req_var("optimized");
if ($optimize) {
    check_token("WHMCS.admin.default");
    try {
        $tables = DI::make("db")->listTables();
        DI::make("db")->optimizeTables($tables);
    } catch (Exception $e) {
        WHMCS\Cookie::set("DatabaseException", WHMCS\Input\Sanitize::encode($e->getMessage()));
        redir("optimized=-1");
    }
    redir("optimized=1");
}
if ($dlbackup) {
    check_token("WHMCS.admin.default");
    if (class_exists("ZipArchive")) {
        $db_name = "";
        $whmcsAppConfig = $whmcs->getApplicationConfig();
        $db_name = $whmcsAppConfig["db_name"];
        set_time_limit(0);
        $zipFile = tempnam(sys_get_temp_dir(), "zip");
        $tempFilename = tempnam(sys_get_temp_dir(), "sql");
        try {
            $databaseConnection = $whmcs->getDatabaseObj();
            $database = new WHMCS\Database\Dumper\Database($databaseConnection);
            $database->dumpTo($tempFilename);
            $zip = new ZipArchive();
            $res = $zip->open($zipFile, ZipArchive::CREATE);
            if ($res === true) {
                $filename = (string) $db_name . ".sql";
                if ($zip->addFile($tempFilename, $filename)) {
                    $zip->setArchiveComment("WHMCS Generated mySQL Backup");
                    $zip->close();
                    header("Content-type: application/octet-stream");
                    header("Content-disposition: attachment; filename=" . $db_name . "_backup_" . date("Ymd_His") . ".zip");
                    readfile($zipFile);
                } else {
                    logActivity("An unknown error occurred adding the generated sql to the archive.");
                }
            }
            unlink($zipFile);
            unlink($tempFilename);
        } catch (Exception $e) {
            logActivity("An error occurred generating the backup archive. The error is: " . $e->getMessage());
            unlink($zipFile);
            unlink($tempFilename);
        }
    } else {
        infoBox($aInt->lang("backups", "backupDisabled"), $aInt->lang("backups", "zipExtensionRequired"));
    }
}
ob_start();
if ($optimized) {
    if (0 < $optimized) {
        infoBox($aInt->lang("system", "optcomplete"), $aInt->lang("system", "optcompleteinfo"));
    } else {
        if ($optimized < 0) {
            $error = WHMCS\Cookie::get("DatabaseException");
            WHMCS\Cookie::delete("DatabaseException");
            infoBox($aInt->lang("global", "erroroccurred"), WHMCS\Input\Sanitize::convertToCompatHtml($error), "error");
        }
    }
}
echo $infobox;
echo "\n<table width=760 align=center cellspacing=0 cellpadding=0><tr><td width=380 valign=top>\n\n<table bgcolor=#cccccc cellspacing=1 width=370 align=center>\n<tr style=\"text-align:center;font-weight:bold;background-color:#efefef\"><td>";
echo $aInt->lang("fields", "name");
echo "</td><td>";
echo $aInt->lang("fields", "rows");
echo "</td><td>";
echo $aInt->lang("fields", "size");
echo "</td></tr>\n";
$query = "SHOW TABLE STATUS";
$result = full_query($query);
for ($i = 0; $data = mysql_fetch_array($result); $i++) {
    $name = $data["Name"];
    $rows = $data["Rows"];
    $datalen = $data["Data_length"];
    $indexlen = $data["Index_length"];
    $totalsize = $datalen + $indexlen;
    $totalrows += $rows;
    $size += $totalsize;
    $reportarray[] = array("name" => $name, "rows" => $rows, "size" => round($totalsize / 1024, 2));
}
foreach ($reportarray as $key => $value) {
    if ($key < $i / 2) {
        echo "<tr bgcolor=#ffffff style=\"text-align:center\"><td>" . $value["name"] . "</td><td>" . $value["rows"] . "</td><td>" . $value["size"] . " " . $aInt->lang("fields", "kb") . "</td></tr>";
    }
}
echo "</table>\n\n</td><td align=\"center\" width=370 valign=top>\n\n<table bgcolor=#cccccc cellspacing=1 width=370>\n<tr style=\"text-align:center;font-weight:bold;background-color:#efefef\"><td>";
echo $aInt->lang("fields", "name");
echo "</td><td>";
echo $aInt->lang("fields", "rows");
echo "</td><td>";
echo $aInt->lang("fields", "size");
echo "</td></tr>\n";
foreach ($reportarray as $key => $value) {
    if ($i / 2 <= $key) {
        echo "<tr bgcolor=#ffffff style=\"text-align:center\"><td>" . $value["name"] . "</td><td>" . $value["rows"] . "</td><td>" . $value["size"] . " " . $aInt->lang("fields", "kb") . "</td></tr>";
    }
}
echo "</table>\n\n</td></tr></table>\n\n<p align=center><b>";
echo $aInt->lang("system", "totaltables");
echo ":</b> ";
echo $i;
echo " - <b>";
echo $aInt->lang("system", "totalrows");
echo ":</b> ";
echo $totalrows;
echo " - <B>";
echo $aInt->lang("system", "totalsize");
echo ":</B> ";
echo round($size / 1024, 2);
echo " ";
echo $aInt->lang("fields", "kb");
echo "</p>\n\n<form method=\"post\" action=\"";
echo $whmcs->getPhpSelf();
echo "\">\n<p align=center><input type=\"submit\" name=\"optimize\" value=\"";
echo $aInt->lang("system", "opttables");
echo "\" class=\"btn btn-warning\" /> <input type=\"submit\" name=\"dlbackup\" value=\"";
echo $aInt->lang("system", "dldbbackup");
echo "\" class=\"btn btn-warning\" /></p>\n</form>\n\n</td></tr></table>\n\n";
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->display();

?>