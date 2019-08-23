<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("View Email Message Log", false);
$aInt->setClientsProfilePresets();
$whmcs = WHMCS\Application::getInstance();
$userid = $whmcs->get_req_var("userid");
$messageID = $whmcs->get_req_var("messageID");
$emailTemplate = WHMCS\Mail\Template::find($messageID);
$id = (int) $whmcs->get_req_var("id");
$aInt->assertClientBoundary($userid);
if ($displaymessage == "true") {
    $aInt->title = $aInt->lang("emails", "viewemail");
    $result = select_query("tblemails", "", array("id" => $id));
    $data = mysql_fetch_array($result);
    $date = $data["date"];
    $to = is_null($data["to"]) ? $aInt->lang("emails", "registeredemail") : $data["to"];
    $cc = $data["cc"];
    $bcc = $data["bcc"];
    $subject = $data["subject"];
    $message = $data["message"];
    $content = "<p><b>" . $aInt->lang("emails", "to") . ":</b> " . WHMCS\Input\Sanitize::makeSafeForOutput($to) . "<br />";
    if ($cc) {
        $content .= "<b>" . $aInt->lang("emails", "cc") . ":</b> " . WHMCS\Input\Sanitize::makeSafeForOutput($cc) . "<br />";
    }
    if ($bcc) {
        $content .= "<b>" . $aInt->lang("emails", "bcc") . ":</b> " . WHMCS\Input\Sanitize::makeSafeForOutput($bcc) . "<br />";
    }
    $content .= "<b>" . $aInt->lang("emails", "subject") . ":</b> <span id=\"subject\">" . WHMCS\Input\Sanitize::makeSafeForOutput($subject) . "</span></p>\n    " . $message;
    $aInt->title = $aInt->lang("emails", "viewemailmessage");
    $aInt->content = $content;
    $aInt->displayPopUp();
    exit;
}
if ($action == "send" && $messageID == 0) {
    redir("type=" . $type . "&id=" . $id, "sendmessage.php");
}
if ($action == "delete") {
    check_token("WHMCS.admin.default");
    delete_query("tblemails", array("id" => $id, "userid" => $userid));
    redir("userid=" . $userid);
}
$aInt->valUserID($userid);
ob_start();
$jscode = "";
if ($action == "send") {
    check_token("WHMCS.admin.default");
    $result = sendMessage($emailTemplate, $id, "", true);
    $queryStr = "userid=" . $userid;
    if ($result === true) {
        $queryStr .= "&success=1";
    } else {
        if ($result === false) {
            $queryStr .= "&error=1";
        } else {
            if (0 < strlen($result)) {
                $queryStr .= "&error=1";
                WHMCS\Session::set("EmailError", $result);
            }
        }
    }
    $whmcsConfig = $whmcs->getApplicationConfig();
    $smtp_debug = $whmcsConfig["smtp_debug"];
    if ($smtp_debug) {
        $debug = WHMCS\Session::set("SMTPDebug", base64_encode(ob_get_contents()));
    }
    redir($queryStr);
}
$aInt->deleteJSConfirm("doDelete", "emails", "suredelete", "clientsemails.php?userid=" . $userid . "&action=delete&id=");
$debug = base64_decode(WHMCS\Session::getAndDelete("SMTPDebug"));
if ($debug) {
    echo $debug;
}
$success = $whmcs->get_req_var("success");
$error = $whmcs->get_req_var("error");
if ($success) {
    infoBox($aInt->lang("global", "success"), $aInt->lang("email", "sentSuccessfully"), "success");
} else {
    if ($error) {
        $result = WHMCS\Session::get("EmailError");
        WHMCS\Session::delete("EmailError");
        if ($result) {
            infoBox($aInt->lang("global", "erroroccurred"), $result, "error");
        } else {
            infoBox($aInt->lang("global", "erroroccurred"), $aInt->lang("email", "emailAborted"), "warning");
        }
    }
}
if ($infobox) {
    echo $infobox;
}
$aInt->sortableTableInit("date", "DESC");
$result = select_query("tblemails", "COUNT(*)", array("userid" => $userid));
$data = mysql_fetch_array($result);
$numrows = $data[0];
$result = select_query("tblemails", "", array("userid" => $userid), $orderby, $order, $page * $limit . "," . $limit);
while ($data = mysql_fetch_array($result)) {
    $id = (int) $data["id"];
    $date = $data["date"];
    $date = fromMySQLDate($date, "time");
    $subject = $data["subject"];
    if ($subject == "") {
        $subject = $aInt->lang("emails", "nosubject");
    }
    $tabledata[] = array(WHMCS\Input\Sanitize::makeSafeForOutput($date), "<a href=\"#\" onClick=\"window.open('clientsemails.php?&displaymessage=true&id=" . $id . "','email_window','width=650,height=400,scrollbars=yes');return false\">" . WHMCS\Input\Sanitize::makeSafeForOutput($subject) . "</a>", "<a href=\"sendmessage.php?resend=true&emailid=" . $id . "\"><img src=\"images/icons/resendemail.png\" border=\"0\" alt=\"" . $aInt->lang("emails", "resendemail") . "\"></a>", "<a href=\"#\" onClick=\"doDelete('" . $id . "')\"><img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "delete") . "\" /></a>");
}
echo $aInt->sortableTable(array(array("date", $aInt->lang("fields", "date")), array("subject", $aInt->lang("emails", "subject")), "", ""), $tabledata);
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jquerycode = $jquerycode;
$aInt->jscode = $jscode;
$aInt->display();

?>