<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("View Email Message Log");
$aInt->title = $aInt->lang("system", "emailmessagelog");
$aInt->sidebar = "utilities";
$aInt->icon = "logs";
$aInt->sortableTableInit("date");
$select_keyword = "SQL_CALC_FOUND_ROWS";
$result = select_query("tblemails,tblclients", (string) $select_keyword . " tblemails.id,tblemails.date,tblemails.subject,tblemails.userid,tblclients.firstname,tblclients.lastname", "tblemails.userid=tblclients.id", "tblemails`.`id", "DESC", $page * $limit . "," . $limit);
while ($data = mysql_fetch_array($result)) {
    $id = (int) $data["id"];
    $date = WHMCS\Input\Sanitize::makeSafeForOutput($data["date"]);
    $subject = WHMCS\Input\Sanitize::makeSafeForOutput($data["subject"]);
    $userid = (int) $data["userid"];
    $firstname = WHMCS\Input\Sanitize::makeSafeForOutput($data["firstname"]);
    $lastname = WHMCS\Input\Sanitize::makeSafeForOutput($data["lastname"]);
    $tabledata[] = array(fromMySQLDate($date, "time"), "<a href=\"#\" onClick=\"window.open('clientsemails.php?&displaymessage=true&id=" . $id . "','','width=650,height=400,scrollbars=yes');return false\">" . $subject . "</a>", "<a href=\"clientssummary.php?userid=" . $userid . "\">" . $firstname . " " . $lastname . "</a>", "<a href=\"sendmessage.php?resend=true&emailid=" . $id . "\"><img src=\"images/icons/resendemail.png\" border=\"0\" alt=\"" . $aInt->lang("emails", "resendemail") . "\"></a>");
}
if (!count($tabledata)) {
    $numrows = 0;
} else {
    $result = full_query("SELECT FOUND_ROWS()");
    $data = mysql_fetch_array($result);
    $numrows = $data[0];
}
$content = $aInt->sortableTable(array($aInt->lang("fields", "date"), $aInt->lang("fields", "subject"), $aInt->lang("system", "recipient"), ""), $tabledata);
$aInt->content = $content;
$aInt->display();

?>