<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function processUtf8Mb4($message)
{
    global $whmcs;
    $cutUtf8Mb4 = $whmcs->get_config("CutUtf8Mb4");
    if (!$cutUtf8Mb4) {
        return $message;
    }
    $emojis = array("/[\\x{1F600}\\x{1F601}]/u" => ":)", "/[\\x{1F603}-\\x{1F606}]/u" => ":D", "/[\\x{1F609}\\x{1F60A}]/u" => ";)", "/\\x{1F610}/u" => ":|", "/[\\x{1F612}\\x{1F61E}\\x{1F61F}]/u" => ":(", "/\\x{1F61B}/u" => ":P", "/\\x{1F622}/u" => ":'(");
    $cleanText = $message;
    $cleanText = preg_replace(array_keys($emojis), array_values($emojis), $cleanText);
    $removePatterns = array("/[\\x{1F600}-\\x{1F64F}]/u", "/[\\x{1F300}-\\x{1F5FF}]/u", "/[\\x{1F680}-\\x{1F6FF}]/u", "/[\\x{2600}-\\x{26FF}]/u", "/[\\x{2700}-\\x{27BF}]/u");
    $cleanText = preg_replace($removePatterns, "", $cleanText);
    return $cleanText;
}
function getTimeBetweenDates($lastreply, $from = "now")
{
    $datetime = strtotime($from);
    $date2 = strtotime($lastreply);
    $holdtotsec = $datetime - $date2;
    $holdtotmin = ($datetime - $date2) / 60;
    $holdtothr = ($datetime - $date2) / 3600;
    $holdtotday = intval(($datetime - $date2) / 86400);
    $holdhr = intval($holdtothr - $holdtotday * 24);
    $holdmr = intval($holdtotmin - ($holdhr * 60 + $holdtotday * 1440));
    $holdsr = intval($holdtotsec - ($holdhr * 3600 + $holdmr * 60 + 86400 * $holdtotday));
    return array("days" => $holdtotday, "hours" => $holdhr, "minutes" => $holdmr, "seconds" => $holdsr);
}
function getShortLastReplyTime($lastreply)
{
    $timeparts = gettimebetweendates($lastreply);
    $str = "";
    if (0 < $timeparts["days"]) {
        $str .= $timeparts["days"] . "d ";
    }
    $str .= $timeparts["hours"] . "h ";
    $str .= $timeparts["minutes"] . "m";
    return $str;
}
function getLastReplyTime($lastreply)
{
    $timeparts = gettimebetweendates($lastreply);
    $str = "";
    if (0 < $timeparts["days"]) {
        $str .= $timeparts["days"] . " Days ";
    }
    $str .= $timeparts["hours"] . " Hours ";
    $str .= $timeparts["minutes"] . " Minutes ";
    $str .= $timeparts["seconds"] . " Seconds ";
    $str .= "Ago";
    return $str;
}
function getTicketDuration($start, $end)
{
    $timeparts = gettimebetweendates($start, $end);
    $str = "";
    if (0 < $timeparts["days"]) {
        $str .= $timeparts["days"] . " " . Lang::trans("days") . " ";
    }
    if (0 < $timeparts["hours"]) {
        $str .= $timeparts["hours"] . " " . Lang::trans("hours") . " ";
    }
    if (0 < $timeparts["minutes"]) {
        $str .= $timeparts["minutes"] . " " . Lang::trans("minutes") . " ";
    }
    $str .= $timeparts["seconds"] . " " . Lang::trans("seconds") . " ";
    return $str;
}
function getStatusColour($tstatus, $htmlOutput = true)
{
    global $_LANG;
    static $ticketcolors = array();
    if (!array_key_exists($tstatus, $ticketcolors)) {
        $ticketcolors[$tstatus] = $color = get_query_val("tblticketstatuses", "color", array("title" => $tstatus));
    } else {
        $color = $ticketcolors[$tstatus];
    }
    if ($htmlOutput) {
        $langstatus = preg_replace("/[^a-z]/i", "", strtolower($tstatus));
        if ($_LANG["supportticketsstatus" . $langstatus]) {
            $tstatus = $_LANG["supportticketsstatus" . $langstatus];
        }
        $statuslabel = "";
        if ($color) {
            $statuslabel .= "<span style=\"color:" . $color . "\">";
        }
        $statuslabel .= $tstatus;
        if ($color) {
            $statuslabel .= "</span>";
        }
        return $statuslabel;
    }
    return $color;
}
function ticketAutoHyperlinks($message)
{
    return autoHyperLink($message);
}
function AddNote($tid, $message, $markdown = false)
{
    if (!function_exists("getAdminName")) {
        require ROOTDIR . "/includes/adminfunctions.php";
    }
    $attachments = uploadTicketAttachments(true);
    if (!$attachments && defined("APICALL") && ($attachment = App::getFromRequest("attachments"))) {
        if (!is_array($attachment)) {
            $attachment = json_decode(base64_decode($attachment), true);
        }
        if (is_array($attachment)) {
            $attachments = saveTicketAttachmentsFromApiCall($attachment, true);
        }
    }
    $message = processutf8mb4($message);
    insert_query("tblticketnotes", array("ticketid" => $tid, "date" => "now()", "admin" => getAdminName(), "message" => $message, "attachments" => $attachments ?: "", "editor" => $markdown ? "markdown" : "plain"));
    addTicketLog($tid, "Ticket Note Added");
    run_hook("TicketAddNote", array("ticketid" => $tid, "message" => $message, "adminid" => $_SESSION["adminid"], "attachments" => $attachments));
}
function AdminRead($tid)
{
    $result = select_query("tbltickets", "adminunread", array("id" => $tid));
    $data = mysql_fetch_assoc($result);
    $adminread = $data["adminunread"];
    $adminreadarray = $adminread ? explode(",", $adminread) : array();
    if (!in_array($_SESSION["adminid"], $adminreadarray)) {
        $adminreadarray[] = $_SESSION["adminid"];
        update_query("tbltickets", array("adminunread" => implode(",", $adminreadarray)), array("id" => $tid));
    }
}
function ClientRead($tid)
{
    update_query("tbltickets", array("clientunread" => ""), array("id" => $tid));
}
function addTicketLog($tid, $action)
{
    if (isset($_SESSION["adminid"])) {
        if (!function_exists("getAdminName")) {
            require ROOTDIR . "/includes/adminfunctions.php";
        }
        $action .= " (by " . getAdminName() . ")";
    }
    insert_query("tblticketlog", array("date" => "now()", "tid" => $tid, "action" => $action));
}
function AddtoLog($tid, $action)
{
    addticketlog($tid, $action);
}
function getDepartmentName($deptId)
{
    static $departmentNames = NULL;
    if (is_null($departmentNames)) {
        $departmentNames = WHMCS\Support\Department::all()->pluck("name", "id")->toArray();
    }
    $departmentName = "";
    if (array_key_exists($deptId, $departmentNames)) {
        $departmentName = $departmentNames[$deptId];
    }
    return $departmentName;
}
function ticketGenerateAttachmentsListFromString($attachmentsString)
{
    $attachmentsOutput = "";
    $attachmentsString = trim($attachmentsString);
    if ($attachmentsString) {
        $attachmentsOutput .= "<br /><br /><strong>Attachments</strong><br />";
        $attachments = explode("|", $attachmentsString);
        foreach ($attachments as $i => $attachment) {
            $attachmentsOutput .= $i + 1 . ". " . substr($attachment, 7) . "<br />";
        }
    }
    return $attachmentsOutput;
}
function openNewTicket($userid, $contactid, $deptid, $tickettitle, $message, $urgency, $attachmentsString = "", array $from = array(), $relatedservice = "", $ccemail = "", $noemail = "", $admin = "", $markdown = false)
{
    global $CONFIG;
    $result = select_query("tblticketdepartments", "", array("id" => $deptid));
    $data = mysql_fetch_array($result);
    $deptid = $data["id"];
    $noautoresponder = $data["noautoresponder"];
    if (!$deptid) {
        exit("Department Not Found. Exiting.");
    }
    $ccemail = trim($ccemail);
    $tickettitle = processutf8mb4($tickettitle);
    $message = processutf8mb4($message);
    if ($userid) {
        $name = $email = "";
        if (0 < $contactid) {
            $data = get_query_vals("tblcontacts", "firstname,lastname,email", array("id" => $contactid, "userid" => $userid));
            $ccemail .= $ccemail ? "," . $data["email"] : $data["email"];
        } else {
            $data = get_query_vals("tblclients", "firstname,lastname,email", array("id" => $userid));
        }
        if ($admin) {
            $message = str_replace("[NAME]", $data["firstname"] . " " . $data["lastname"], $message);
            $message = str_replace("[FIRSTNAME]", $data["firstname"], $message);
            $message = str_replace("[EMAIL]", $data["email"], $message);
        }
        $clientname = $data["firstname"] . " " . $data["lastname"];
    } else {
        if ($admin) {
            $message = str_replace("[NAME]", $from["name"], $message);
            $message = str_replace("[FIRSTNAME]", current(explode(" ", $from["name"])), $message);
            $message = str_replace("[EMAIL]", $from["email"], $message);
        }
        $clientname = $from["name"];
    }
    $ccemail = implode(",", array_unique(explode(",", $ccemail)));
    foreach ($ccemail as $key => $value) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            unset($ccemail[$key]);
        }
    }
    $length = 8;
    $seeds = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $c = NULL;
    $seeds_count = strlen($seeds) - 1;
    for ($i = 0; $i < $length; $i++) {
        $c .= $seeds[rand(0, $seeds_count)];
    }
    $tid = genTicketMask();
    if (!in_array($urgency, array("High", "Medium", "Low"))) {
        $urgency = "Medium";
    }
    $editor = $markdown ? "markdown" : "plain";
    $table = "tbltickets";
    $array = array("tid" => $tid, "userid" => $userid, "contactid" => $contactid, "did" => $deptid, "date" => "now()", "title" => $tickettitle, "message" => $message, "urgency" => $urgency, "status" => "Open", "attachment" => $attachmentsString, "lastreply" => "now()", "name" => $from["name"], "email" => $from["email"], "c" => $c, "clientunread" => "1", "adminunread" => "", "service" => $relatedservice, "cc" => $ccemail, "editor" => $editor);
    if ($admin) {
        if (!function_exists("getAdminName")) {
            include_once ROOTDIR . "/includes/adminfunctions.php";
        }
        $array["admin"] = getAdminName();
    }
    $id = insert_query($table, $array);
    $tid = genTicketMask($id);
    update_query("tbltickets", array("tid" => $tid), array("id" => $id));
    if (!$noemail) {
        if ($admin) {
            sendMessage("Support Ticket Opened by Admin", $id);
        } else {
            if (!$noautoresponder) {
                sendMessage("Support Ticket Opened", $id);
            }
        }
    }
    $deptname = getdepartmentname($deptid);
    if (!$noemail) {
        $changes = array();
        $changes["Opened"] = array("new" => $message);
        $changes["Who"] = $admin ? $array["admin"] : $clientname;
        if ($attachmentsString) {
            $changes["Attachments"] = ticketgenerateattachmentslistfromstring($attachmentsString);
        }
        WHMCS\Tickets::notifyTicketChanges($id, $changes, getDepartmentNotificationIds($deptid));
    }
    if ($admin) {
        addticketlog($id, "New Support Ticket Opened");
    } else {
        addticketlog($id, "New Support Ticket Opened");
    }
    if ($admin) {
        run_hook("TicketOpenAdmin", array("ticketid" => $id, "ticketmask" => $tid, "userid" => $userid, "deptid" => $deptid, "deptname" => $deptname, "subject" => $tickettitle, "message" => $message, "priority" => $urgency));
    } else {
        run_hook("TicketOpen", array("ticketid" => $id, "ticketmask" => $tid, "userid" => $userid, "deptid" => $deptid, "deptname" => $deptname, "subject" => $tickettitle, "message" => $message, "priority" => $urgency));
    }
    return array("ID" => $id, "TID" => $tid, "C" => $c, "Subject" => $tickettitle);
}
function AddReply($ticketid, $userid, $contactid, $message, $admin, $attachmentsString = "", $from = "", $status = "", $noemail = "", $api = false, $markdown = false, $changes = array())
{
    global $CONFIG;
    if (!is_array($from)) {
        $from = array("name" => "", "email" => "");
    }
    $adminname = "";
    $message = processutf8mb4($message);
    if ($admin) {
        $data = get_query_vals("tbltickets", "userid,contactid,name,email", array("id" => $ticketid));
        if (0 < $data["userid"]) {
            if (0 < $data["contactid"]) {
                $data = get_query_vals("tblcontacts", "firstname,lastname,email", array("id" => $data["contactid"], "userid" => $data["userid"]));
            } else {
                $data = get_query_vals("tblclients", "firstname,lastname,email", array("id" => $data["userid"]));
            }
            $message = str_replace("[NAME]", $data["firstname"] . " " . $data["lastname"], $message);
            $message = str_replace("[FIRSTNAME]", $data["firstname"], $message);
            $message = str_replace("[EMAIL]", $data["email"], $message);
        } else {
            $message = str_replace("[NAME]", $data["name"], $message);
            $message = str_replace("[FIRSTNAME]", current(explode(" ", $data["name"])), $message);
            $message = str_replace("[EMAIL]", $data["email"], $message);
        }
        if (!function_exists("getAdminName")) {
            require ROOTDIR . "/includes/adminfunctions.php";
        }
        $adminname = $api ? $admin : getAdminName((int) $admin);
    }
    $editor = $markdown ? "markdown" : "plain";
    $table = "tblticketreplies";
    $array = array("tid" => $ticketid, "userid" => $userid, "contactid" => $contactid, "name" => $from["name"], "email" => $from["email"], "date" => "now()", "message" => $message, "admin" => $adminname, "attachment" => $attachmentsString, "editor" => $editor);
    $ticketreplyid = insert_query($table, $array);
    $data = WHMCS\Database\Capsule::table("tbltickets")->find($ticketid, array("tid", "did", "title", "urgency", "flag", "status"));
    $tid = $data->tid;
    $deptid = $data->did;
    $tickettitle = $data->title;
    $urgency = $data->urgency;
    $flagadmin = $data->flag;
    $oldStatus = $data->status;
    if ($userid || $contactid) {
        $clientname = $contactid ? WHMCS\User\Client\Contact::find($contactid)->fullName : WHMCS\User\Client::find($userid)->fullName;
    } else {
        $clientname = $from["name"];
    }
    $deptname = getdepartmentname($deptid);
    if ($admin) {
        if ($status == "") {
            $status = "Answered";
        }
        $updateqry = array("status" => $status, "clientunread" => "1", "lastreply" => "now()");
        if ($CONFIG["TicketLastReplyUpdateClientOnly"]) {
            unset($updateqry["lastreply"]);
        }
        update_query("tbltickets", $updateqry, array("id" => $ticketid));
        addticketlog($ticketid, "New Ticket Response");
        if (!$noemail) {
            sendMessage("Support Ticket Reply", $ticketid, $ticketreplyid);
        }
        run_hook("TicketAdminReply", array("ticketid" => $ticketid, "replyid" => $ticketreplyid, "deptid" => $deptid, "deptname" => $deptname, "subject" => $tickettitle, "message" => $message, "priority" => $urgency, "admin" => $adminname, "status" => $status));
    } else {
        $status = "Customer-Reply";
        $updateqry = array("status" => "Customer-Reply", "clientunread" => "1", "adminunread" => "", "lastreply" => "now()");
        $UpdateLastReplyTimestamp = WHMCS\Application::getInstance()->get_config("UpdateLastReplyTimestamp");
        if ($UpdateLastReplyTimestamp == "statusonly" && ($oldStatus == $status || $oldStatus == "Open" && $status == "Customer-Reply")) {
            unset($updateqry["lastreply"]);
        }
        update_query("tbltickets", $updateqry, array("id" => $ticketid));
        addticketlog($ticketid, "New Ticket Response made by User");
        run_hook("TicketUserReply", array("ticketid" => $ticketid, "replyid" => $ticketreplyid, "userid" => $userid, "deptid" => $deptid, "deptname" => $deptname, "subject" => $tickettitle, "message" => $message, "priority" => $urgency, "status" => $status));
    }
    if ($oldStatus != $status) {
        $changes["Status"] = array("old" => $oldStatus, "new" => $status);
    }
    $changes["Reply"] = array("new" => $message);
    if ($attachmentsString) {
        $changes["Attachments"] = ticketgenerateattachmentslistfromstring($attachmentsString);
    }
    $recipients = array();
    if (!$admin) {
        $changes["Who"] = $clientname;
        $recipients = $flagadmin ? array($flagadmin) : !$noemail ? getDepartmentNotificationIds($deptid) : array();
    } else {
        $changes["Who"] = $adminname;
    }
    WHMCS\Tickets::notifyTicketChanges($ticketid, $changes, $recipients);
}
function processPoppedTicket($to, $name, $email, $subject, $message, $attachment, array $cc = array())
{
    $decodestring = $subject . "##||-MESSAGESPLIT-||##" . $message;
    $decodestring = pipeDecodeString($decodestring);
    $decodestring = explode("##||-MESSAGESPLIT-||##", $decodestring);
    list($subject, $body) = $decodestring;
    processPipedTicket($to, $name, $email, $subject, $body, $attachment, $cc);
}
function processPipedTicket($to, $name, $email, $subject, $message, $attachment, array $cc = array())
{
    global $whmcs;
    global $CONFIG;
    global $supportticketpipe;
    global $pipenonregisteredreplyonly;
    $supportticketpipe = true;
    $raw_message = $message;
    $result = select_query("tblticketspamfilters", "", "");
    $subject = processutf8mb4($subject);
    $message = processutf8mb4($message);
    $mailstatus = "";
    $mailLogAttachment = $attachment;
    while ($data = mysql_fetch_array($result)) {
        $id = $data["id"];
        $type = $data["type"];
        $content = $data["content"];
        if ($type == "sender") {
            if (strtolower($content) == strtolower($email)) {
                $mailstatus = "Blocked Sender";
            }
        } else {
            if ($type == "subject") {
                if (strpos("x" . strtolower($subject), strtolower($content))) {
                    $mailstatus = "Blocked Subject";
                }
            } else {
                if ($type == "phrase" && strpos("x" . strtolower($message), strtolower($content))) {
                    $mailstatus = "Blocked Phrase";
                }
            }
        }
    }
    $responses = run_hook("TicketPiping", array("to" => $to, "cc" => $cc, "name" => $name, "email" => $email, "subject" => $subject, "body" => $message, "attachments" => $attachment));
    foreach ($responses as $response) {
        if (array_key_exists("skipProcessing", $response) && $response["skipProcessing"]) {
            $table = "tblticketmaillog";
            $array = array("date" => "now()", "to" => $to, "cc" => implode(",", $cc), "name" => $name, "email" => $email, "subject" => $subject, "message" => $message, "status" => "Skipped importing due to hook", "attachment" => $mailLogAttachment);
            insert_query($table, htmlspecialchars_array($array));
            return false;
        }
    }
    if (!$mailstatus) {
        $tid = 0;
        $pos = strpos($subject, "[Ticket ID: ");
        if ($pos === false) {
        } else {
            $tid = substr($subject, $pos + 12);
            $tid = substr($tid, 0, strpos($tid, "]"));
            $ticket = WHMCS\Database\Capsule::table("tbltickets")->where("tid", "=", $tid)->first(array("id", "status", "merged_ticket_id"));
            if ($ticket) {
                if ($ticket->merged_ticket_id) {
                    $tid = $ticket->merged_ticket_id;
                    $ticketStatus = WHMCS\Database\Capsule::table("tbltickets")->where("tid", "=", $ticket->merged_ticket_id)->value("status");
                } else {
                    $tid = $ticket->id;
                    $ticketStatus = $ticket->status;
                }
            } else {
                $tid = 0;
            }
        }
        $to = trim($to);
        $toemails = explode(",", $to);
        $deptid = "";
        foreach ($toemails as $toemail) {
            $result = select_query("tblticketdepartments", "", array("email" => trim(strtolower($toemail))));
            $data = mysql_fetch_array($result);
            $deptid = $data["id"];
            if ($deptid) {
                break;
            }
        }
        if (!$deptid) {
            $result = select_query("tblticketdepartments", "", array("hidden" => ""), "order", "ASC", "1");
            $data = mysql_fetch_array($result);
            $deptid = $data["id"];
        }
        if (!$deptid) {
            $mailstatus = "Department Not Found";
        } else {
            $to = $data["email"];
            $deptclientsonly = $data["clientsonly"];
            $deptpiperepliesonly = $data["piperepliesonly"];
            $noautoresponder = $data["noautoresponder"];
            if ($to == $email) {
                $mailstatus = "Blocked Potential Email Loop";
            } else {
                $messagebackup = $message;
                $result = select_query("tblticketbreaklines", "", "", "id", "ASC");
                while ($data = mysql_fetch_array($result)) {
                    $breakpos = strpos($message, $data["breakline"]);
                    if ($breakpos) {
                        $message = substr($message, 0, $breakpos);
                    }
                }
                if (!$message) {
                    $message = $messagebackup;
                }
                $message = trim($message);
                $result = select_query("tbladmins", "id", array("email" => $email));
                $data = mysql_fetch_array($result);
                $adminid = $data["id"];
                if ($adminid) {
                    if ($tid) {
                        addreply($tid, "", "", htmlspecialchars_array($message), $adminid, $attachment, "", "", "", false, false);
                        $mailLogAttachment = "";
                        $mailstatus = "Ticket Reply Imported Successfully";
                    } else {
                        $mailstatus = "Ticket ID Not Found";
                    }
                } else {
                    $result = select_query("tblclients", "id", array("email" => $email));
                    $data = mysql_fetch_array($result);
                    $userid = $data["id"];
                    $ccemail = $contactid = "";
                    $from = array();
                    if (!$userid) {
                        $contactsByEmail = WHMCS\User\Client\Contact::where("email", $email)->get();
                        if ($contactsByEmail->count() === 1) {
                            $userid = $contactsByEmail->first()->userid;
                            $contactid = $contactsByEmail->first()->id;
                        } else {
                            $userid = 0;
                            $contactid = 0;
                        }
                        if ($userid) {
                            $ccemail = $email;
                        }
                    }
                    if ($deptclientsonly == "on" && !$userid) {
                        $mailstatus = "Unregistered Email Address";
                        if (!$noautoresponder) {
                            sendMessage("Clients Only Bounce Message", "", array($name, $email));
                        }
                    } else {
                        $clientTicket = true;
                        if (empty($userid)) {
                            $from["name"] = $name;
                            $from["email"] = $email;
                            $clientTicket = false;
                        }
                        $filterdate = date("YmdHis", mktime(date("H"), date("i") - 15, date("s"), date("m"), date("d"), date("Y")));
                        $query = "SELECT count(*) FROM tbltickets WHERE date>'" . $filterdate . "' AND ( email='" . mysql_real_escape_string($email) . "'";
                        if ($userid) {
                            $query .= " OR userid=" . (int) $userid;
                        }
                        $query .= " )";
                        $result = full_query($query);
                        $data = mysql_fetch_array($result);
                        $numtickets = $data[0];
                        $ticketEmailLimit = (int) $whmcs->get_config("TicketEmailLimit");
                        if (!$ticketEmailLimit) {
                            $ticketEmailLimit = 10;
                        }
                        if ($ticketEmailLimit < $numtickets) {
                            $mailstatus = "Exceeded Limit of " . $ticketEmailLimit . " Tickets within 15 Minutes";
                        } else {
                            run_hook("TransliterateTicketText", array("subject" => $subject, "message" => $message));
                            if ($tid) {
                                $closedTicketStatuses = array();
                                $result2 = select_query("tblticketstatuses", "title", array("showactive" => 0, "showawaiting" => 0, "autoclose" => 0));
                                while ($data2 = mysql_fetch_array($result2)) {
                                    $closedTicketStatuses[] = $data2["title"];
                                }
                                if (isset($ticketStatus) && in_array($ticketStatus, $closedTicketStatuses) && $whmcs->get_config("PreventEmailReopening")) {
                                    $mailstatus = "Ticket Reopen via Email Stopped";
                                    if (!$noautoresponder) {
                                        sendMessage("Closed Ticket Bounce Message", $tid, array($name, $email, "clientTicket" => $clientTicket));
                                    }
                                } else {
                                    $ticket = new WHMCS\Tickets();
                                    $ticket->setID($tid);
                                    addreply($tid, $userid, $contactid, htmlspecialchars_array($message), "", $attachment, htmlspecialchars_array($from), "", "", false, false);
                                    $mailLogAttachment = "";
                                    $mailstatus = "Ticket Reply Imported Successfully";
                                }
                            } else {
                                if ($pipenonregisteredreplyonly && !$userid) {
                                    $mailstatus = "Blocked Ticket Opening from Unregistered User";
                                } else {
                                    if ($deptpiperepliesonly) {
                                        $mailstatus = "Only Replies Allowed by Email";
                                        if (!$noautoresponder) {
                                            sendMessage("Replies Only Bounce Message", "", array($name, $email));
                                        }
                                    } else {
                                        $ccemail .= implode(",", $cc);
                                        $emailTicketCcAllowed = (bool) WHMCS\Config\Setting::getValue("TicketAddCarbonCopyRecipients");
                                        if (!$emailTicketCcAllowed) {
                                            $ccemail = "";
                                        }
                                        opennewticket(htmlspecialchars_array($userid), htmlspecialchars_array($contactid), htmlspecialchars_array($deptid), htmlspecialchars_array($subject), htmlspecialchars_array($message), "Medium", $attachment, htmlspecialchars_array($from), "", htmlspecialchars_array($ccemail), "", "", false);
                                        $mailLogAttachment = "";
                                        $mailstatus = "Ticket Imported Successfully";
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    } else {
        if ($attachment) {
            $attachment = explode("|", $attachment);
            $attachmentStorage = Storage::ticketAttachments();
            foreach ($attachment as $file) {
                $attachmentStorage->deleteAllowNotPresent($file);
            }
        }
    }
    if (!$mailstatus) {
        $mailstatus = "Ticket Import Failed";
        $mailLogAttachment = $attachment;
    }
    $table = "tblticketmaillog";
    $array = array("date" => "now()", "to" => $to, "cc" => $cc, "name" => $name, "email" => $email, "subject" => $subject, "message" => $message, "status" => $mailstatus, "attachment" => $mailLogAttachment);
    insert_query($table, htmlspecialchars_array($array));
}
function convertIniSize($size)
{
    $multipliers = array("K" => 1024, "M" => 1024 * 1024, "G" => 1024 * 1024 * 1024);
    $mod = strtoupper(substr($size, -1, 1));
    $mult = $multipliers[$mod] ?: 1;
    if (1 < $mult) {
        $size = (int) substr($size, 0, -1);
    }
    return $size * $mult;
}
function checkTicketAttachmentSize()
{
    $postMaxSizeIniSetting = ini_get("post_max_size");
    $postMaxSize = convertinisize($postMaxSizeIniSetting);
    $contentLength = (int) $_SERVER["CONTENT_LENGTH"];
    if (!$contentLength) {
        return true;
    }
    if ($postMaxSize < $contentLength) {
        logActivity(sprintf("A ticket attachment submission of %d bytes total was rejected due to PHP post_max_size setting being too small (%s or %d bytes).", $contentLength, $postMaxSizeIniSetting, $postMaxSize));
        return false;
    }
    $uploadMaxFileSizeIniSetting = ini_get("upload_max_filesize");
    $uploadMaxFileSize = convertinisize($uploadMaxFileSizeIniSetting);
    if (isset($_FILES)) {
        if (is_array($_FILES["attachments"]["error"])) {
            $fileTooLarge = in_array(UPLOAD_ERR_INI_SIZE, $_FILES["attachments"]["error"]);
        } else {
            $fileTooLarge = $_FILES["attachments"]["error"] == UPLOAD_ERR_INI_SIZE;
        }
        if ($fileTooLarge) {
            logActivity(sprintf("A ticket attachment was rejected due to PHP upload_max_filesize setting being too small (%s or %d bytes).", $uploadMaxFileSizeIniSetting, $uploadMaxFileSize));
            return false;
        }
    }
    return true;
}
function uploadTicketAttachments($isAdmin = false)
{
    $attachments = array();
    foreach (WHMCS\File\Upload::getUploadedFiles("attachments") as $uploadedFile) {
        if ($isAdmin || WHMCS\File\Upload::isExtensionAllowed($uploadedFile->getCleanName())) {
            $attachments[] = $uploadedFile->storeAsTicketAttachment();
        }
    }
    return implode("|", $attachments);
}
function saveTicketAttachmentsFromApiCall(array $attachmentArray = array(), $isAdmin = false)
{
    $attachments = array();
    if (0 < count($attachmentArray)) {
        $storage = Storage::ticketAttachments();
        foreach ($attachmentArray as $attachment) {
            if (array_key_exists("name", $attachment) && array_key_exists("data", $attachment)) {
                $filename = $attachment["name"];
                $filenameParts = explode(".", $filename);
                $extension = end($filenameParts);
                if ($isAdmin || WHMCS\File\Upload::isExtensionAllowed($filename)) {
                    $filename = implode(array_slice($filenameParts, 0, -1));
                    $filename = preg_replace("/[^a-zA-Z0-9-_ ]/", "", $filename);
                    if (!$filename) {
                        $filename = md5(time());
                    }
                    while (true) {
                        $fileNameToSave = (new WHMCS\Utility\Random())->number(6) . "_" . $filename . "." . $extension;
                        if (!$storage->has($fileNameToSave)) {
                            break;
                        }
                    }
                    $storage->put($fileNameToSave, base64_decode($attachment["data"]));
                    $attachments[] = $fileNameToSave;
                }
            }
        }
    }
    return implode("|", $attachments);
}
function checkTicketAttachmentExtension($file_name)
{
    return WHMCS\File\Upload::isExtensionAllowed($file_name);
}
function pipeDecodeString($input)
{
    $input = preg_replace("/(=\\?[^?]+\\?(q|b)\\?[^?].{0,75}\\?=)(\\s)+=\\?/i", "\\1=?", $input);
    while (preg_match("/(=\\?([^?]+)\\?(q|b)\\?([^?].{0,75})\\?=)/i", $input, $matches)) {
        list(, $encoded, $charset, $encoding, $text) = $matches;
        switch (strtolower($encoding)) {
            case "b":
                $text = base64_decode($text);
                break;
            case "q":
                $text = str_replace("_", " ", $text);
                preg_match_all("/=([a-f0-9]{2})/i", $text, $matches);
                foreach ($matches[1] as $value) {
                    $text = str_replace("=" . $value, chr(hexdec($value)), $text);
                }
                break;
        }
        $input = str_replace($encoded, $text, $input);
    }
    return $input;
}
function deleteTicket($ticketid, $replyid = 0)
{
    $ticketid = (int) $ticketid;
    $replyid = (int) $replyid;
    $attachments = array();
    $where = 0 < $replyid ? array("id" => $replyid) : array("tid" => $ticketid);
    $result = select_query("tblticketreplies", "attachment", $where);
    while ($data = mysql_fetch_array($result)) {
        $attachments[] = $data["attachment"];
    }
    if (!$replyid) {
        $data = get_query_vals("tbltickets", "did, attachment", array("id" => $ticketid));
        $deptid = $data["did"];
        $attachments[] = $data["attachment"];
    }
    foreach ($attachments as $attachment) {
        if ($attachment) {
            $attachment = explode("|", $attachment);
            foreach ($attachment as $filename) {
                try {
                    Storage::ticketAttachments()->deleteAllowNotPresent($filename);
                } catch (Exception $e) {
                    throw new WHMCS\Exception\Fatal("Could not delete file: " . htmlentities($e->getMessage()));
                }
            }
        }
    }
    if (!$replyid) {
        if (!function_exists("getCustomFields")) {
            require_once ROOTDIR . "/includes/customfieldfunctions.php";
        }
        $customfields = getCustomFields("support", $deptid, $ticketid, true);
        foreach ($customfields as $field) {
            delete_query("tblcustomfieldsvalues", array("fieldid" => $field["id"], "relid" => $ticketid));
        }
        delete_query("tbltickettags", array("ticketid" => $ticketid));
        delete_query("tblticketnotes", array("ticketid" => $ticketid));
        delete_query("tblticketlog", array("tid" => $ticketid));
        delete_query("tblticketreplies", array("tid" => $ticketid));
        delete_query("tbltickets", array("id" => $ticketid));
        logActivity("Deleted Ticket - Ticket ID: " . $ticketid);
        run_hook("TicketDelete", array("ticketId" => $ticketid, "adminId" => WHMCS\Session::get("adminid")));
    } else {
        delete_query("tblticketreplies", array("id" => $replyid));
        addticketlog($ticketid, "Deleted Ticket Reply (ID: " . $replyid . ")");
        logActivity("Deleted Ticket Reply - ID: " . $replyid);
        run_hook("TicketDeleteReply", array("ticketId" => $ticketid, "replyId" => $replyid, "adminId" => WHMCS\Session::get("adminid")));
    }
}
function genTicketMask($id = "")
{
    global $CONFIG;
    $lowercase = "abcdefghijklmnopqrstuvwxyz";
    $uppercase = "ABCDEFGHIJKLMNOPQRSTUVYWXYZ";
    $ticketmaskstr = "";
    $ticketmask = trim($CONFIG["TicketMask"]);
    if (!$ticketmask) {
        $ticketmask = "%n%n%n%n%n%n";
    }
    $masklen = strlen($ticketmask);
    for ($i = 0; $i < $masklen; $i++) {
        $maskval = $ticketmask[$i];
        if ($maskval == "%") {
            $i++;
            $maskval .= $ticketmask[$i];
            if ($maskval == "%A") {
                $ticketmaskstr .= $uppercase[rand(0, 25)];
            } else {
                if ($maskval == "%a") {
                    $ticketmaskstr .= $lowercase[rand(0, 25)];
                } else {
                    if ($maskval == "%n") {
                        $ticketmaskstr .= strlen($ticketmaskstr) ? rand(0, 9) : rand(1, 9);
                    } else {
                        if ($maskval == "%y") {
                            $ticketmaskstr .= date("Y");
                        } else {
                            if ($maskval == "%m") {
                                $ticketmaskstr .= date("m");
                            } else {
                                if ($maskval == "%d") {
                                    $ticketmaskstr .= date("d");
                                } else {
                                    if ($maskval == "%i") {
                                        $ticketmaskstr .= $id;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } else {
            $ticketmaskstr .= $maskval;
        }
    }
    $tid = get_query_val("tbltickets", "id", array("tid" => $ticketmaskstr));
    if ($tid) {
        $ticketmaskstr = genTicketMask($id);
    }
    return $ticketmaskstr;
}
function getKBAutoSuggestions($text)
{
    $kbarticles = array();
    $hookret = run_hook("SubmitTicketAnswerSuggestions", array("text" => $text));
    if (count($hookret)) {
        foreach ($hookret as $hookdat) {
            foreach ($hookdat as $arrdata) {
                $kbarticles[] = $arrdata;
            }
        }
    } else {
        $ignorewords = array("able", "about", "above", "according", "accordingly", "across", "actually", "after", "afterwards", "again", "against", "ain't", "allow", "allows", "almost", "alone", "along", "already", "also", "although", "always", "among", "amongst", "another", "anybody", "anyhow", "anyone", "anything", "anyway", "anyways", "anywhere", "apart", "appear", "appreciate", "appropriate", "aren't", "around", "aside", "asking", "associated", "available", "away", "awfully", "became", "because", "become", "becomes", "becoming", "been", "before", "beforehand", "behind", "being", "believe", "below", "beside", "besides", "best", "better", "between", "beyond", "both", "brief", "c'mon", "came", "can't", "cannot", "cant", "cause", "causes", "certain", "certainly", "changes", "clearly", "come", "comes", "concerning", "consequently", "consider", "considering", "contain", "containing", "contains", "corresponding", "could", "couldn't", "course", "currently", "definitely", "described", "despite", "didn't", "different", "does", "doesn't", "doing", "don't", "done", "down", "downwards", "during", "each", "eight", "either", "else", "elsewhere", "enough", "entirely", "especially", "even", "ever", "every", "everybody", "everyone", "everything", "everywhere", "exactly", "example", "except", "fifth", "first", "five", "followed", "following", "follows", "former", "formerly", "forth", "four", "from", "further", "furthermore", "gets", "getting", "given", "gives", "goes", "going", "gone", "gotten", "greetings", "hadn't", "happens", "hardly", "hasn't", "have", "haven't", "having", "he's", "hello", "help", "hence", "here", "here's", "hereafter", "hereby", "herein", "hereupon", "hers", "herself", "himself", "hither", "hopefully", "howbeit", "however", "i'll", "i've", "ignored", "immediate", "inasmuch", "indeed", "indicate", "indicated", "indicates", "inner", "insofar", "instead", "into", "inward", "isn't", "it'd", "it'll", "it's", "itself", "just", "keep", "keeps", "kept", "know", "known", "knows", "last", "lately", "later", "latter", "latterly", "least", "less", "lest", "let's", "like", "liked", "likely", "little", "look", "looking", "looks", "mainly", "many", "maybe", "mean", "meanwhile", "merely", "might", "more", "moreover", "most", "mostly", "much", "must", "myself", "name", "namely", "near", "nearly", "necessary", "need", "needs", "neither", "never", "nevertheless", "next", "nine", "nobody", "none", "noone", "normally", "nothing", "novel", "nowhere", "obviously", "often", "okay", "once", "ones", "only", "onto", "other", "others", "otherwise", "ought", "ours", "ourselves", "outside", "over", "overall", "particular", "particularly", "perhaps", "placed", "please", "plus", "possible", "presumably", "probably", "provides", "quite", "rather", "really", "reasonably", "regarding", "regardless", "regards", "relatively", "respectively", "right", "said", "same", "saying", "says", "second", "secondly", "seeing", "seem", "seemed", "seeming", "seems", "seen", "self", "selves", "sensible", "sent", "serious", "seriously", "seven", "several", "shall", "should", "shouldn't", "since", "some", "somebody", "somehow", "someone", "something", "sometime", "sometimes", "somewhat", "somewhere", "soon", "sorry", "specified", "specify", "specifying", "still", "such", "sure", "take", "taken", "tell", "tends", "than", "thank", "thanks", "thanx", "that", "that's", "thats", "their", "theirs", "them", "themselves", "then", "thence", "there", "there's", "thereafter", "thereby", "therefore", "therein", "theres", "thereupon", "these", "they", "they'd", "they'll", "they're", "they've", "think", "third", "this", "thorough", "thoroughly", "those", "though", "three", "through", "throughout", "thru", "thus", "together", "took", "toward", "towards", "tried", "tries", "truly", "trying", "twice", "under", "unfortunately", "unless", "unlikely", "until", "unto", "upon", "used", "useful", "uses", "using", "usually", "value", "various", "very", "want", "wants", "wasn't", "we'd", "we'll", "we're", "we've", "welcome", "well", "went", "were", "weren't", "what", "what's", "whatever", "when", "whence", "whenever", "where", "where's", "whereafter", "whereas", "whereby", "wherein", "whereupon", "wherever", "whether", "which", "while", "whither", "who's", "whoever", "whole", "whom", "whose", "will", "willing", "wish", "with", "within", "without", "won't", "wonder", "would", "wouldn't", "you'd", "you'll", "you're", "you've", "your", "yours", "yourself", "yourselves", "zero");
        $text = str_replace("\n", " ", $text);
        $textparts = explode(" ", strtolower($text));
        $validword = 0;
        foreach ($textparts as $k => $v) {
            if (in_array($v, $ignorewords) || strlen($textparts[$k]) <= 3 || 100 <= $validword) {
                unset($textparts[$k]);
            } else {
                $validword++;
            }
        }
        $kbarticles = getKBAutoSuggestionsQuery("title", $textparts, "5");
        if (count($kbarticles) < 5) {
            $numleft = 5 - count($kbarticles);
            $kbarticles = array_merge($kbarticles, getKBAutoSuggestionsQuery("article", $textparts, $numleft, $kbarticles));
        }
    }
    return $kbarticles;
}
function getKBAutoSuggestionsQuery($field, $textparts, $limit, $existingkbarticles = "")
{
    $kbarticles = array();
    $where = "";
    foreach ($textparts as $textpart) {
        $where .= (string) $field . " LIKE '%" . db_escape_string($textpart) . "%' OR ";
    }
    $where = !$where ? "id!=''" : substr($where, 0, -4);
    if (is_array($existingkbarticles)) {
        $existingkbids = array();
        foreach ($existingkbarticles as $v) {
            $existingkbids[] = (int) $v["id"];
        }
        $where = "(" . $where . ")";
        if (0 < count($existingkbids)) {
            $where .= " AND id NOT IN (" . db_build_in_array($existingkbids) . ")";
        }
    }
    $result = full_query("SELECT id,parentid FROM tblknowledgebase WHERE " . $where . " ORDER BY useful DESC LIMIT 0," . (int) $limit);
    while ($data = mysql_fetch_array($result)) {
        $articleid = $data["id"];
        $parentid = $data["parentid"];
        if ($parentid) {
            $articleid = $parentid;
        }
        $result2 = full_query("SELECT tblknowledgebaselinks.categoryid FROM tblknowledgebase INNER JOIN tblknowledgebaselinks ON tblknowledgebase.id=tblknowledgebaselinks.articleid INNER JOIN tblknowledgebasecats ON tblknowledgebasecats.id=tblknowledgebaselinks.categoryid WHERE (tblknowledgebase.id=" . (int) $articleid . " OR tblknowledgebase.parentid=" . (int) $articleid . ") AND tblknowledgebasecats.hidden=''");
        $data = mysql_fetch_array($result2);
        $categoryid = $data["categoryid"];
        if ($categoryid) {
            $result2 = full_query("SELECT * FROM tblknowledgebase WHERE (id=" . (int) $articleid . " OR parentid=" . (int) $articleid . ") AND (language='" . db_escape_string(WHMCS\Session::get("Language")) . "' OR language='') ORDER BY language DESC");
            $data = mysql_fetch_array($result2);
            $title = $data["title"];
            $article = $data["article"];
            $views = $data["views"];
            $kbarticles[] = array("id" => $articleid, "category" => $categoryid, "title" => $title, "article" => ticketsummary($article), "text" => $article);
        }
    }
    return $kbarticles;
}
function ticketsummary($text, $length = 100)
{
    $tail = "...";
    $text = strip_tags($text);
    $txtl = strlen($text);
    if ($length < $txtl) {
        for ($i = 1; $text[$length - $i] != " "; $i++) {
            if ($i == $length) {
                return substr($text, 0, $length) . $tail;
            }
        }
        $text = substr($text, 0, $length - $i + 1) . $tail;
    }
    return $text;
}
function getTicketContacts($userid)
{
    $contacts = "";
    $result = select_query("tblcontacts", "", array("userid" => $userid, "email" => array("sqltype" => "NEQ", "value" => "")));
    while ($data = mysql_fetch_array($result)) {
        $contacts .= "<option value=\"" . $data["id"] . "\"";
        if (isset($_POST["contactid"]) && $_POST["contactid"] == $data["id"]) {
            $contacts .= " selected";
        }
        $contacts .= ">" . $data["firstname"] . " " . $data["lastname"] . " - " . $data["email"] . "</option>";
    }
    if ($contacts) {
        return "<select name=\"contactid\" class=\"form-control select-inline\"><option value=\"0\">None</option>" . $contacts . "</select>";
    }
}
function getTicketAttachmentsInfo($ticketId, $attachment, $type = "ticket", $relatedId = 0)
{
    $PHP_SELF = App::getPhpSelf();
    $attachments = array();
    if ($attachment) {
        $attachment = explode("|", $attachment);
        foreach ($attachment as $num => $filename) {
            $file = substr($filename, 7);
            switch ($type) {
                case "note":
                    $attachments[] = array("filename" => $file, "isImage" => isAttachmentAnImage($filename), "removed" => false, "dllink" => "dl.php?type=an&id=" . $relatedId . "&i=" . $num, "deletelink" => (string) $PHP_SELF . "?action=viewticket&id=" . $ticketId . "&removeattachment=true&type=n&" . "idsd=" . $relatedId . "&filecount=" . $num . generate_token("link"));
                    break;
                case "reply":
                    $attachments[] = array("filename" => $file, "isImage" => isAttachmentAnImage($filename), "removed" => false, "dllink" => "dl.php?type=ar&id=" . $relatedId . "&i=" . $num, "deletelink" => (string) $PHP_SELF . "?action=viewticket&id=" . $ticketId . "&removeattachment=true&type=r&" . "idsd=" . $relatedId . "&filecount=" . $num . generate_token("link"));
                    break;
                case "removed":
                    $attachments[] = array("filename" => $file, "isImage" => false, "removed" => true, "dllink" => "", "deletelink" => "");
                    break;
                default:
                    $attachments[] = array("filename" => $file, "isImage" => isAttachmentAnImage($filename), "removed" => false, "dllink" => "dl.php?type=a&id=" . $ticketId . "&i=" . $num, "deletelink" => (string) $PHP_SELF . "?action=viewticket&id=" . $ticketId . "&removeattachment=true&" . "idsd=" . $ticketId . "&filecount=" . $num . generate_token("link"));
            }
        }
    }
    return $attachments;
}
function isAttachmentAnImage($file)
{
    if (!$file) {
        return false;
    }
    try {
        return (bool) getimagesizefromstring(Storage::ticketAttachments()->read($file));
    } catch (Exception $e) {
        return false;
    }
}
function getAdminDepartmentAssignments()
{
    static $DepartmentIDs = array();
    if (count($DepartmentIDs)) {
        return $DepartmentIDs;
    }
    $result = select_query("tbladmins", "supportdepts", array("id" => $_SESSION["adminid"]));
    $data = mysql_fetch_array($result);
    $supportdepts = $data["supportdepts"];
    $supportdepts = explode(",", $supportdepts);
    foreach ($supportdepts as $k => $v) {
        if (!$v) {
            unset($supportdepts[$k]);
        }
    }
    $DepartmentIDs = $supportdepts;
    return $supportdepts;
}
function getDepartments()
{
    $departmentsarray = array();
    $result = select_query("tblticketdepartments", "id,name", "");
    $departmentsarray = array();
    while ($data = mysql_fetch_array($result)) {
        $id = $data["id"];
        $name = getdepartmentname($data["id"]);
        $departmentsarray[$id] = $name;
    }
    return $departmentsarray;
}
function validateAdminTicketAccess($ticketid)
{
    $data = get_query_vals("tbltickets", "id,did,flag,merged_ticket_id", array("id" => $ticketid));
    $id = $data["id"];
    $deptid = $data["did"];
    $flag = $data["flag"];
    $mergedTicketId = $data["merged_ticket_id"];
    if (!$id) {
        return "invalidid";
    }
    if (!in_array($deptid, getadmindepartmentassignments()) && !checkPermission("Access All Tickets Directly", true)) {
        return "deptblocked";
    }
    if ($flag && $flag != $_SESSION["adminid"] && !checkPermission("View Flagged Tickets", true) && !checkPermission("Access All Tickets Directly", true)) {
        return "flagged";
    }
    if ($mergedTicketId) {
        return "merged" . $mergedTicketId;
    }
    return false;
}
function genPredefinedRepliesList($cat, $predefq = "")
{
    global $aInt;
    $catscontent = "";
    $repliescontent = "";
    if (!$predefq) {
        if (!$cat) {
            $cat = 0;
        }
        $result = select_query("tblticketpredefinedcats", "", array("parentid" => $cat), "name", "ASC");
        $i = 0;
        while ($data = mysql_fetch_array($result)) {
            $id = $data["id"];
            $name = $data["name"];
            $catscontent .= "<td width=\"33%\">" . DI::make("asset")->imgTag("folder.gif", "Folder", array("align" => "absmiddle")) . " <a href=\"#\" onclick=\"selectpredefcat('" . $id . "');return false\">" . $name . "</a></td>";
            $i++;
            if ($i % 3 == 0) {
                $catscontent .= "</tr><tr>";
                $i = 0;
            }
        }
    }
    $where = $predefq ? array("name" => array("sqltype" => "LIKE", "value" => $predefq)) : array("catid" => $cat);
    $result = select_query("tblticketpredefinedreplies", "", $where, "name", "ASC");
    while ($data = mysql_fetch_array($result)) {
        $id = $data["id"];
        $name = $data["name"];
        $reply = strip_tags($data["reply"]);
        $shortreply = substr($reply, 0, 100) . "...";
        $shortreply = str_replace(chr(10), " ", $shortreply);
        $shortreply = str_replace(chr(13), " ", $shortreply);
        $repliescontent .= "&nbsp;" . DI::make("asset")->imgTag("article.gif", "Article", array("align" => "absmiddle")) . "<a href=\"#\" onclick=\"selectpredefreply('" . $id . "');return false\">" . $name . "</a> - " . $shortreply . "<br>";
    }
    $content = "";
    if ($catscontent) {
        $content .= "<strong>" . $aInt->lang("support", "categories") . "</strong><br><br><table width=\"95%\"><tr>" . $catscontent . "</tr></table><br>";
    }
    if ($repliescontent) {
        if ($predefq) {
            $content .= "<strong>" . $aInt->lang("global", "searchresults") . "</strong><br><br>" . $repliescontent;
        } else {
            $content .= "<strong>" . $aInt->lang("support", "replies") . "</strong><br><br>" . $repliescontent;
        }
    }
    if (!$content) {
        if ($predefq) {
            $content .= "<strong>" . $aInt->lang("global", "searchresults") . "</strong><br><br>" . $aInt->lang("global", "nomatchesfound") . "<br>";
        } else {
            $content .= "<span style=\"line-height:22px;\">" . $aInt->lang("support", "catempty") . "</span><br>";
        }
    }
    $result = select_query("tblticketpredefinedcats", "parentid", array("id" => $cat));
    $data = mysql_fetch_array($result);
    if (0 < $cat || $predefq) {
        $content .= "<br /><a href=\"#\" onclick=\"selectpredefcat('0');return false\"><img src=\"images/icons/navrotate.png\" align=\"top\" /> " . $aInt->lang("support", "toplevel") . "</a>";
    }
    if (0 < $cat) {
        $content .= " &nbsp;<a href=\"#\" onclick=\"selectpredefcat('" . $data[0] . "');return false\"><img src=\"images/icons/navback.png\" align=\"top\" /> " . $aInt->lang("support", "uponelevel") . "</a>";
    }
    return $content;
}
function closeTicket($id)
{
    global $whmcs;
    $ticket = WHMCS\Database\Capsule::table("tbltickets")->find($id);
    if (is_null($ticket)) {
        return false;
    }
    if ($ticket->status == "Closed") {
        return false;
    }
    $changes = array();
    if (defined("CLIENTAREA")) {
        addticketlog($id, "Closed by Client");
        $changes["Who"] = WHMCS\Session::get("cid") ? WHMCS\User\Client\Contact::find(WHMCS\Session::get("cid"))->fullName : WHMCS\User\Client::find(WHMCS\Session::get("uid"))->fullName;
    } else {
        if (defined("ADMINAREA") || defined("APICALL")) {
            addticketlog($id, "Status changed to Closed");
            $changes["Who"] = getAdminName(WHMCS\Session::get("adminid"));
        } else {
            addticketlog($id, "Ticket Auto Closed For Inactivity");
            $changes["Who"] = "System";
        }
    }
    $changes["Status"] = array("old" => $ticket->status, "new" => "Closed");
    update_query("tbltickets", array("status" => "Closed"), array("id" => $ticket->id));
    $skipFeedbackRequest = false;
    $skipNotification = false;
    $responses = run_hook("TicketClose", array("ticketid" => $id));
    foreach ($responses as $response) {
        if (array_key_exists("skipFeedbackRequest", $response) && $response["skipFeedbackRequest"]) {
            $skipFeedbackRequest = true;
        }
        if (array_key_exists("skipNotification", $response) && $response["skipNotification"]) {
            $skipNotification = true;
        }
    }
    if (!$skipFeedbackRequest) {
        $department = WHMCS\Database\Capsule::table("tblticketdepartments")->find($ticket->did);
        if ($department->feedback_request) {
            $feedbackcheck = get_query_val("tblticketfeedback", "id", array("ticketid" => $id));
            if (!$feedbackcheck) {
                sendMessage("Support Ticket Feedback Request", $id);
            }
        }
    }
    if (!$skipNotification) {
        WHMCS\Tickets::notifyTicketChanges($id, $changes);
    }
    return true;
}
function getDepartmentNotificationIds($departmentId)
{
    $admins = WHMCS\User\Admin::join("tbladminroles", "tbladmins.roleid", "=", "tbladminroles.id")->where("tbladmins.disabled", "=", "0")->where("tbladminroles.supportemails", "=", "1")->where("tbladmins.ticketnotifications", "!=", "")->get(array("tbladmins.id", "tbladmins.supportdepts", "tbladmins.ticketnotifications"));
    $notificationAdmins = array();
    foreach ($admins as $admin) {
        if (in_array($departmentId, $admin->supportDepartmentIds) && in_array($departmentId, $admin->receivesTicketNotifications)) {
            $notificationAdmins[] = $admin->id;
        }
    }
    return $notificationAdmins;
}
function checkTicketChanges($ticketId, stdClass $ticketInfo = NULL)
{
    $changeList = array();
    $lastReplyId = (int) App::getFromRequest("lastReplyId");
    $currentSubject = App::getFromRequest("currentSubject");
    $currentStatus = App::getFromRequest("currentStatus");
    $currentCc = App::getFromRequest("currentCc");
    $currentUserId = App::getFromRequest("currentUserId");
    $currentDepartmentId = App::getFromRequest("currentDepartmentId");
    $currentFlag = App::getFromRequest("currentFlag");
    $currentPriority = App::getFromRequest("currentPriority");
    if (!$ticketInfo) {
        $ticketInfo = WHMCS\Database\Capsule::table("tbltickets")->where("tbltickets.id", $ticketId)->leftJoin("tblticketreplies", function (Illuminate\Database\Query\JoinClause $query) use($lastReplyId) {
            $query->on("tbltickets.id", "=", "tblticketreplies.tid")->on("tblticketreplies.id", ">", WHMCS\Database\Capsule::raw($lastReplyId));
        })->groupBy("tblticketreplies.tid")->orderBy("tblticketreplies.id", "DESC")->first(array("tbltickets.status", "tbltickets.cc", "tbltickets.userid", "tbltickets.did", "tbltickets.flag", "tbltickets.urgency", "tbltickets.title", "tblticketreplies.id as lastReplyId", "tblticketreplies.admin as replyAdminName", "tblticketreplies.userid as replyUserId"));
    }
    if (!is_null($ticketInfo->lastReplyId)) {
        if ($ticketInfo->replyAdminName) {
            $changeList[] = AdminLang::trans("support.newReply");
        } else {
            if ($ticketInfo->replyUserId) {
                $changeList[] = AdminLang::trans("support.newReplyByClient");
            }
        }
    }
    if ($ticketInfo->status != $currentStatus) {
        $changeList[] = AdminLang::trans("support.statusChange", array(":oldStatus" => $currentStatus, ":newStatus" => $ticketInfo->status));
    }
    if ($ticketInfo->cc != $currentCc) {
        $changeList[] = AdminLang::trans("support.ccChange", array(":oldCc" => $currentCc, ":newCc" => $ticketInfo->cc));
    }
    if ($ticketInfo->userid != $currentUserId) {
        $changeList[] = AdminLang::trans("support.userChange", array(":oldUser" => $currentUserId, ":newUser" => $ticketInfo->userid));
    }
    if ($ticketInfo->did != $currentDepartmentId) {
        $oldDid = getdepartmentname($currentDepartmentId);
        $newDid = getdepartmentname($ticketInfo->did);
        $changeList[] = AdminLang::trans("support.departmentChange", array(":oldDepartment" => $oldDid, ":newDepartment" => $newDid));
    }
    if ($ticketInfo->flag != $currentFlag) {
        $oldFlag = $currentFlag ? getAdminName($currentFlag) : "Unassigned";
        $newFlag = $ticketInfo->flag ? getAdminName($ticketInfo->flag) : "Unassigned";
        $changeList[] = AdminLang::trans("support.flagChange", array(":oldFlag" => $oldFlag, ":newFlag" => $newFlag));
    }
    if ($ticketInfo->urgency != $currentPriority) {
        $changeList[] = AdminLang::trans("support.priorityChange", array(":oldPriority" => $currentPriority, ":newPriority" => $ticketInfo->urgency));
    }
    if ($ticketInfo->title != $currentSubject) {
        $changeList[] = AdminLang::trans("support.subjectChange", array(":oldSubject" => $currentSubject, ":newSubject" => $ticketInfo->title));
    }
    return $changeList;
}
function removeAttachmentsFromClosedTickets($removeAttachmentsPeriod = 0)
{
    $migrationProgress = WHMCS\File\Migration\FileAssetMigrationProgress::forAssetType(WHMCS\File\FileAsset::TYPE_TICKET_ATTACHMENTS)->first();
    if ($migrationProgress) {
        return array("removed" => 0, "left" => 0, "limitHit" => false, "error" => "system.migrationInProgress");
    }
    $removedCount = 0;
    $remainingRecords = 0;
    if ($removeAttachmentsPeriod) {
        if ($removeAttachmentsPeriod instanceof WHMCS\Carbon) {
            $removeAttachmentsBefore = $removeAttachmentsPeriod;
        } else {
            if (is_int($removeAttachmentsPeriod)) {
                $removeAttachmentsBefore = WHMCS\Carbon::today()->subMonthsNoOverflow($removeAttachmentsPeriod);
            }
        }
        $closedTicketStatuses = WHMCS\Database\Capsule::table("tblticketstatuses")->where("showactive", 0)->where("showawaiting", 0)->pluck("title");
        $response = removeAttachmentsFromClosedTicketsTable("tblticketreplies", $removeAttachmentsBefore, $closedTicketStatuses);
        $removedCount += $response["removedCount"];
        $remainingRecords += $response["remainingRecords"];
        $response = removeAttachmentsFromClosedTicketsTable("tblticketnotes", $removeAttachmentsBefore, $closedTicketStatuses);
        $removedCount += $response["removedCount"];
        $remainingRecords += $response["remainingRecords"];
        $response = removeAttachmentsFromClosedTicketsTable("tbltickets", $removeAttachmentsBefore, $closedTicketStatuses);
        $removedCount += $response["removedCount"];
        $remainingRecords += $response["remainingRecords"];
    }
    return array("removed" => $removedCount, "left" => $remainingRecords, "limitHit" => 0 < $remainingRecords);
}
function removeAttachmentsFromClosedTicketsTable($table, $removeAttachmentsBefore, $closedTicketStatuses)
{
    $idField = $table . ".id";
    $joinField = NULL;
    $attachmentFieldName = "attachment";
    if ($table == "tblticketreplies") {
        $joinField = "tblticketreplies.tid";
    } else {
        if ($table == "tblticketnotes") {
            $joinField = "tblticketnotes.ticketid";
            $attachmentFieldName = "attachments";
        }
    }
    $query = WHMCS\Database\Capsule::table($table)->where($table . ".attachments_removed", 0)->whereIn("tbltickets.status", $closedTicketStatuses)->where("tbltickets.lastreply", "<", $removeAttachmentsBefore->toDateTimeString())->where($table . "." . $attachmentFieldName, "!=", "");
    if (!is_null($joinField)) {
        $query->join("tbltickets", "tbltickets.id", "=", $joinField);
    }
    if ($table == "tbltickets") {
        $query->where("merged_ticket_id", 0);
    }
    $prunedIds = array();
    foreach ($query->orderBy($idField)->limit(1000)->pluck($table . "." . $attachmentFieldName, $table . ".id") as $ticketId => $ticketAttachments) {
        $attachments = explode("|", $ticketAttachments);
        foreach ($attachments as $attachment) {
            try {
                Storage::ticketAttachments()->deleteAllowNotPresent($attachment);
            } catch (Exception $e) {
                logActivity("Automated Prune Ticket Attachments: Unable to" . " remove attachment '" . $attachment . "': " . $e->getMessage());
            }
        }
        $prunedIds[] = $ticketId;
    }
    WHMCS\Database\Capsule::table($table)->whereIn("id", $prunedIds)->update(array("attachments_removed" => "1"));
    return array("removedCount" => count($prunedIds), "remainingRecords" => $query->count($idField));
}

?>