<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("CLIENTAREA", true);
require "init.php";
header("Content-Type: application/rss+xml");
echo "<?xml version=\"1.0\" encoding=\"" . $CONFIG["Charset"] . "\"?>\n<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\">\n<channel>\n<atom:link href=\"" . $CONFIG["SystemURL"] . "/networkissuesrss.php\" rel=\"self\" type=\"application/rss+xml\" />\n<title><![CDATA[" . $CONFIG["CompanyName"] . "]]></title>\n<description><![CDATA[" . $CONFIG["CompanyName"] . " " . $_LANG["networkissuestitle"] . " " . $_LANG["rssfeed"] . "]]></description>\n<link>" . $CONFIG["SystemURL"] . "/networkissues.php</link>";
$result = select_query("tblnetworkissues", "*", "status != 'Resolved'", "startdate", "DESC");
while ($data = mysql_fetch_array($result)) {
    $id = $data["id"];
    $date = $data["startdate"];
    $title = $data["title"];
    $description = $data["description"];
    $formattedDate = WHMCS\Carbon::createFromFormat("Y-m-d H:i:s", $date)->format("r");
    echo "\n<item>\n    <title>" . $title . "</title>\n    <link>" . $CONFIG["SystemURL"] . "/networkissues.php?view=nid" . $id . "</link>\n    <pubDate>" . $formattedDate . "</pubDate>\n    <description><![CDATA[" . $description . "]]></description>\n</item>";
}
echo "\n</channel>\n</rss>";

?>