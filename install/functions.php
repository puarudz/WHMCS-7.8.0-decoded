<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function mysql_import_file($filename, $basedir = NULL)
{
    if (!$basedir) {
        $basedir = dirname(__FILE__) . "/sql/";
    }
    $querycount = 0;
    $queryerrors = "";
    if (file_exists($basedir . $filename)) {
        $lines = file($basedir . $filename);
        if (!$lines) {
            $errmsg = "cannot open file " . $filename;
            return false;
        }
        $scriptfile = false;
        foreach ($lines as $line) {
            $line = trim($line);
            if (substr($line, 0, 2) != "--") {
                $scriptfile .= " " . $line;
            }
        }
        $queries = explode(";", $scriptfile);
        foreach ($queries as $query) {
            $query = trim($query);
            $querycount++;
            if ($query == "") {
                continue;
            }
            if (!mysql_query($query)) {
                $queryerrors .= "Line " . $querycount . " - " . mysql_error() . "<br>";
            }
        }
        if ($queryerrors) {
            echo "<b>Errors Occurred</b><br><br>Please open a ticket with the debug information below for support<br><br>File: " . $filename . "<br>" . $queryerrors;
        }
        return true;
    } else {
        $errmsg = "cannot open file " . $filename;
        return false;
    }
}
function getConfigurationFileContentWithNewCcHash($newCcHash)
{
    $newline = PHP_EOL;
    $attachments_dir = "";
    $downloads_dir = "";
    $customadminpath = "";
    $db_host = "";
    $db_username = "";
    $db_password = "";
    $db_name = "";
    $license = "";
    $templates_compiledir = "";
    $mysql_charset = "";
    $api_access_key = "";
    $autoauthkey = "";
    $display_errors = false;
    $error_reporting = 0;
    include ROOTDIR . DIRECTORY_SEPARATOR . "configuration.php";
    $output = sprintf("<?php%s" . "\$license = '%s';%s" . "\$db_host = '%s';%s" . "\$db_username = '%s';%s" . "\$db_password = '%s';%s" . "\$db_name = '%s';%s" . "\$cc_encryption_hash = '%s';%s" . "\$templates_compiledir = '%s';%s", $newline, WHMCS\Input\Sanitize::escapeSingleQuotedString($license), $newline, WHMCS\Input\Sanitize::escapeSingleQuotedString($db_host), $newline, WHMCS\Input\Sanitize::escapeSingleQuotedString($db_username), $newline, WHMCS\Input\Sanitize::escapeSingleQuotedString($db_password), $newline, WHMCS\Input\Sanitize::escapeSingleQuotedString($db_name), $newline, WHMCS\Input\Sanitize::escapeSingleQuotedString($newCcHash), $newline, WHMCS\Input\Sanitize::escapeSingleQuotedString($templates_compiledir), $newline);
    if ($mysql_charset) {
        $output .= sprintf("\$mysql_charset = '%s';%s", WHMCS\Input\Sanitize::escapeSingleQuotedString($mysql_charset), $newline);
    }
    if ($attachments_dir) {
        $output .= sprintf("\$attachments_dir = '%s';%s", WHMCS\Input\Sanitize::escapeSingleQuotedString($attachments_dir), $newline);
    }
    if ($downloads_dir) {
        $output .= sprintf("\$downloads_dir = '%s';%s", WHMCS\Input\Sanitize::escapeSingleQuotedString($downloads_dir), $newline);
    }
    if ($customadminpath) {
        $output .= sprintf("\$customadminpath = '%s';%s", WHMCS\Input\Sanitize::escapeSingleQuotedString($customadminpath), $newline);
    }
    if ($api_access_key) {
        $output .= sprintf("\$api_access_key = '%s';%s", WHMCS\Input\Sanitize::escapeSingleQuotedString($api_access_key), $newline);
    }
    if ($autoauthkey) {
        $output .= sprintf("\$autoauthkey = '%s';%s", WHMCS\Input\Sanitize::escapeSingleQuotedString($autoauthkey), $newline);
    }
    if ($display_errors) {
        $output .= sprintf("\$display_errors = %s;%s", "true", $newline);
    }
    if ($error_reporting) {
        $output .= sprintf("\$error_reporting = %s;%s", $error_reporting, $newline);
    }
    return $output;
}

?>