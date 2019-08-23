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
$reportdata["title"] = "Product Suspensions";
$reportdata["description"] = "This report allows you to review all suspended products and the reasons specified for their suspensions";
$reportdata["tableheadings"] = array("Service ID", "Client Name", "Product Name", "Domain", "Next Due Date", "Suspend Reason");
$result = select_query("tblhosting", "tblhosting.*,tblclients.firstname,tblclients.lastname,tblproducts.name", array("domainstatus" => "Suspended"), "id", "ASC", "", "tblclients ON tblclients.id=tblhosting.userid INNER JOIN tblproducts ON tblproducts.id=tblhosting.packageid");
while ($data = mysql_fetch_array($result)) {
    $serviceid = $data["id"];
    $userid = $data["userid"];
    $clientname = $data["firstname"] . " " . $data["lastname"];
    $productname = $data["name"];
    $domain = $data["domain"];
    $nextduedate = $data["nextduedate"];
    $suspendreason = $data["suspendreason"];
    if (!$suspendreason) {
        $suspendreason = 'Overdue on Payment';
    }
    $nextduedate = fromMySQLDate($nextduedate);
    $reportdata["tablevalues"][] = array('<a href="clientshosting.php?userid=' . $userid . '&id=' . $serviceid . '">' . $serviceid . '</a>', '<a href="clientssummary.php?userid=' . $userid . '">' . $clientname . '</a>', $productname, $domain, $nextduedate, $suspendreason);
}
$data["footertext"] = '';

?>