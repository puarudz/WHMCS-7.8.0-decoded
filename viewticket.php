<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("CLIENTAREA", true);
require "init.php";
require "includes/ticketfunctions.php";
require "includes/clientfunctions.php";
require "includes/customfieldfunctions.php";
$tid = $whmcs->get_req_var("tid");
$c = $whmcs->get_req_var("c");
$closeticket = $whmcs->get_req_var("closeticket");
$postreply = $whmcs->get_req_var("postreply");
$replyname = $whmcs->get_req_var("replyname");
$replyemail = $whmcs->get_req_var("replyemail");
$replymessage = $whmcs->get_req_var("replymessage");
$loggedInUserId = WHMCS\Session::get("uid");
$loggedInContactId = WHMCS\Session::get("cid");
$c = preg_replace("/[^A-Za-z0-9]/", "", $c);
$clientname = $clientemail = "";
$pagetitle = $_LANG["supportticketsviewticket"];
$breadcrumbnav = "<a href=\"index.php\">" . $_LANG["globalsystemname"] . "</a> > <a href=\"clientarea.php\">" . $_LANG["clientareatitle"] . "</a> > <a href=\"supporttickets.php\">" . $_LANG["supportticketspagetitle"] . "</a> > <a href=\"viewticket.php?tid=" . $tid . "&amp;c=" . $c . "\">" . $_LANG["supportticketsviewticket"] . "</a>";
$pageicon = "images/supporttickets_big.gif";
$templatefile = "viewticket";
$displayTitle = Lang::trans("supportticketsviewticket");
$tagline = "";
initialiseClientArea($pagetitle, $displayTitle, $tagline, $pageicon, $breadcrumbnav);
checkContactPermission("tickets");
$usingsupportmodule = false;
if ($CONFIG["SupportModule"]) {
    if (!isValidforPath($CONFIG["SupportModule"])) {
        exit("Invalid Support Module");
    }
    $supportmodulepath = "modules/support/" . $CONFIG["SupportModule"] . "/viewticket.php";
    if (file_exists($supportmodulepath)) {
        $usingsupportmodule = true;
        $templatefile = "";
        require $supportmodulepath;
        outputClientArea($templatefile);
        exit;
    }
}
$result = select_query("tbltickets", "", array("tid" => $tid, "c" => $c));
$data = mysql_fetch_array($result);
$ticketData = $data;
$ticketId = $data["id"];
$tid = $data["tid"];
$c = $data["c"];
$userid = $data["userid"];
$cc = $data["cc"];
if ($data["merged_ticket_id"]) {
    $ticket = WHMCS\Database\Capsule::table("tbltickets")->find($data["merged_ticket_id"], array("tid", "c"));
    redir("tid=" . $ticket->tid . "&c=" . $ticket->c);
}
if (!$ticketId) {
    $smarty->assign("error", true);
    $smarty->assign("invalidTicketId", true);
} else {
    $smarty->assign("invalidTicketId", false);
    if ($CONFIG["RequireLoginforClientTickets"] && $userid && (!$loggedInUserId || $loggedInUserId != $userid)) {
        $goto = "viewticket";
        require "login.php";
    }
    $tickets = new WHMCS\Tickets();
    $tickets->setID($ticketId);
    $AccessedTicketIDs = WHMCS\Session::get("AccessedTicketIDs");
    $AccessedTicketIDsArray = explode(",", $AccessedTicketIDs);
    $AccessedTicketIDsArray[] = $ticketId;
    WHMCS\Session::set("AccessedTicketIDs", implode(",", $AccessedTicketIDsArray));
    if ($whmcs->get_req_var("feedback") && $tickets->getDepartmentFeedbackNotifications()) {
        Menu::primarySidebar("ticketFeedback");
        Menu::secondarySidebar("ticketFeedback");
        $templatefile = "ticketfeedback";
        $smartyvalues["displayTitle"] = Lang::trans("ticketfeedbackrequest");
        $smartyvalues["tagline"] = Lang::trans("ticketfeedbackforticket") . $tid;
        $smartyvalues["id"] = $ticketId;
        $smartyvalues["tid"] = $tid;
        $smartyvalues["c"] = $c;
        $status = $data["status"];
        $closedcheck = get_query_val("tblticketstatuses", "id", array("title" => $status, "showactive" => "0"));
        $smartyvalues["stillopen"] = !$closedcheck ? true : false;
        $feedbackcheck = get_query_val("tblticketfeedback", "id", array("ticketid" => $ticketId));
        $smartyvalues["feedbackdone"] = $feedbackcheck;
        $date = $data["date"];
        $smartyvalues["opened"] = WHMCS\Carbon::createFromFormat("Y-m-d H:i:s", $date)->format("l, jS F Y H:ia");
        $lastreply = get_query_val("tblticketreplies", "date", array("tid" => $ticketId), "id", "DESC");
        if (!$lastreply) {
            $lastreply = $date;
        }
        $smartyvalues["lastreply"] = WHMCS\Carbon::createFromFormat("Y-m-d H:i:s", $lastreply)->format("l, jS F Y H:ia");
        $duration = getTicketDuration($date, $lastreply);
        $smartyvalues["duration"] = $duration;
        $ratings = array();
        for ($i = 1; $i <= 10; $i++) {
            $ratings[] = $i;
        }
        $smartyvalues["ratings"] = $ratings;
        $comments = $whmcs->get_req_var("comments");
        $staffinvolved = array();
        $sql = "SELECT DISTINCT tblticketreplies.admin,tbladmins.id AS staffid FROM tblticketreplies" . " LEFT JOIN tbladmins ON CONCAT(tbladmins.firstname, \" \", tbladmins.lastname)=tblticketreplies.admin" . " WHERE tblticketreplies.tid=?";
        $staffList = WHMCS\Database\Capsule::connection()->select($sql, array($ticketId));
        foreach ($staffList as $staffMember) {
            $adminInvolved = trim($staffMember->admin);
            if ($adminInvolved) {
                $staffinvolved[$staffMember->staffid] = $adminInvolved;
            }
            if (!isset($comments[$staffMember->staffid])) {
                $comments[$staffMember->staffid] = "";
            }
        }
        $smartyvalues["staffinvolved"] = $staffinvolved;
        $smartyvalues["staffinvolvedtext"] = implode(", ", $staffinvolved);
        $smartyvalues["rate"] = $whmcs->get_req_var("rate");
        if (!isset($comments["generic"])) {
            $comments["generic"] = "";
        }
        $smartyvalues["comments"] = $comments;
        $errormessage = "";
        $smartyvalues["success"] = false;
        if ($whmcs->get_req_var("validate")) {
            check_token();
            foreach ($staffinvolved as $staffid => $staffname) {
                if (!$whmcs->get_req_var("rate", $staffid)) {
                    $errormessage .= "<li>" . Lang::trans("feedbacksupplyrating", array(":staffname" => $staffname)) . "</li>";
                }
            }
            $smartyvalues["errormessage"] = $errormessage;
            if (!$errormessage) {
                foreach ($staffinvolved as $staffid => $staffname) {
                    insert_query("tblticketfeedback", array("ticketid" => $ticketId, "adminid" => $staffid, "rating" => $whmcs->get_req_var("rate", $staffid), "comments" => $whmcs->get_req_var("comments", $staffid), "datetime" => "now()", "ip" => WHMCS\Utility\Environment\CurrentUser::getIP()));
                }
                if (trim($whmcs->get_req_var("comments", "generic"))) {
                    insert_query("tblticketfeedback", array("ticketid" => $ticketId, "adminid" => "0", "rating" => "0", "comments" => $whmcs->get_req_var("comments", "generic"), "datetime" => "now()", "ip" => WHMCS\Utility\Environment\CurrentUser::getIP()));
                }
                $smartyvalues["success"] = true;
            }
        }
        outputClientArea($templatefile);
        exit;
    } else {
        if ($closeticket) {
            closeTicket($ticketId);
            redir("tid=" . $tid . "&c=" . $c);
        }
        $rating = $whmcs->get_req_var("rating");
        if ($rating) {
            $rating = explode("_", $rating);
            $replyid = isset($rating[0]) && 4 < strlen($rating[0]) ? substr($rating[0], 4) : "";
            $ratingscore = isset($rating[1]) ? $rating[1] : "";
            if (is_numeric($replyid) && is_numeric($ratingscore)) {
                update_query("tblticketreplies", array("rating" => $ratingscore), array("id" => $replyid, "tid" => $ticketId));
            }
            redir("tid=" . $tid . "&c=" . $c);
        }
        $action = App::getFromRequest("action");
        if ($action) {
            check_token();
            $email = trim(App::getFromRequest("email"));
            try {
                $cc = explode(",", $cc);
                switch ($action) {
                    case "delete":
                        if (!in_array($email, $cc)) {
                            throw new WHMCS\Exception\Validation\InvalidValue(Lang::trans("support.deleteEmailNotExisting", array(":email" => $email)));
                        }
                        $cc = array_flip($cc);
                        unset($cc[$email]);
                        $cc = array_filter(array_flip($cc));
                        $data = array("success" => true, "message" => Lang::trans("support.successDelete", array(":email" => $email)));
                        break;
                    case "add":
                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            throw new WHMCS\Exception\Validation\InvalidValue(Lang::trans("support.invalidEmail", array(":email" => $email)));
                        }
                        if (in_array($email, $cc)) {
                            throw new WHMCS\Exception\Validation\InvalidValue(Lang::trans("support.addEmailExists", array(":email" => $email)));
                        }
                        $clientEmail = WHMCS\Database\Capsule::table("tblclients")->where("id", $data["userid"])->value("email");
                        if ($email == $clientEmail) {
                            throw new WHMCS\Exception\Validation\InvalidValue(Lang::trans("support.clientEmail", array(":email" => $email)));
                        }
                        $existingContacts = WHMCS\Database\Capsule::table("tblcontacts")->where("email", $email)->where("userid", "!=", $data["userid"])->count("id");
                        $existingClients = WHMCS\Database\Capsule::table("tblclients")->where("email", $email)->where("id", "!=", $data["userid"])->count("id");
                        if (0 < $existingContacts + $existingClients) {
                            throw new WHMCS\Exception\Validation\InvalidValue(Lang::trans("support.emailNotPossible", array(":email" => $email)));
                        }
                        $cc[] = $email;
                        $cc = array_filter($cc);
                        $data = array("success" => true, "message" => Lang::trans("support.successAdd", array(":email" => $email)));
                        break;
                    default:
                        $data = array("error" => "An invalid request was made. Please try again.");
                }
                if (array_key_exists("success", $data) && $data["success"]) {
                    WHMCS\Database\Capsule::table("tbltickets")->where("id", $ticketId)->update(array("cc" => implode(",", $cc)));
                    addTicketLog($data["id"], $data["message"]);
                }
            } catch (Exception $e) {
                $data = array("error" => $e->getMessage());
            }
            $response = new WHMCS\Http\JsonResponse();
            $response->setData($data);
            $response->send();
            WHMCS\Terminus::getInstance()->doExit();
        }
        $errormessage = "";
        if ($postreply) {
            check_token();
            $smarty->assign("postingReply", true);
            $validate = new WHMCS\Validate();
            if (!$loggedInUserId) {
                $validate->validate("required", "replyname", "supportticketserrornoname");
                if ($validate->validate("required", "replyemail", "supportticketserrornoemail")) {
                    $validate->validate("email", "replyemail", "clientareaerroremailinvalid");
                }
            }
            $validate->validate("required", "replymessage", "supportticketserrornomessage");
            if ($validate->hasErrors()) {
                $errormessage .= $validate->getHTMLErrorOutput();
            }
            if ($_FILES["attachments"]) {
                foreach ($_FILES["attachments"]["name"] as $num => $filename) {
                    $filename = trim($filename);
                    if ($filename) {
                        $filenameparts = explode(".", $filename);
                        $extension = end($filenameparts);
                        $filename = implode(array_slice($filenameparts, 0, -1));
                        $filename = preg_replace("/[^a-zA-Z0-9-_ ]/", "", $filename);
                        $filename .= "." . $extension;
                        $validextension = checkTicketAttachmentExtension($filename);
                        if (!$validextension) {
                            $errormessage .= "<li>" . $_LANG["supportticketsfilenotallowed"];
                        }
                    }
                }
            }
            if (!$errormessage) {
                $attachments = uploadTicketAttachments();
                $from = array("name" => $replyname, "email" => $replyemail);
                AddReply($ticketId, $loggedInUserId, $loggedInContactId, $replymessage, "", $attachments, $from, "", false, false, true);
                redir("tid=" . $tid . "&c=" . $c);
            }
        } else {
            $smarty->assign("postingReply", false);
        }
        $ticketId = $data["id"];
        $userid = $data["userid"];
        $contactid = $data["contactid"];
        $deptid = $data["did"];
        $date = $data["date"];
        $subject = $data["title"];
        $message = $data["message"];
        $status = $data["status"];
        $attachment = $data["attachment"];
        $attachmentsRemoved = (bool) (int) $data["attachments_removed"];
        $urgency = $data["urgency"];
        $name = $data["name"];
        $email = $data["email"];
        $lastreply = $data["lastreply"];
        $admin = $data["admin"];
        $date = fromMySQLDate($date, 1, 1);
        $lastreply = fromMySQLDate($lastreply, 1, 1);
        $markup = new WHMCS\View\Markup\Markup();
        $markupFormat = $markup->determineMarkupEditor("ticket_msg", $data["editor"]);
        $message = $markup->transform($message, $markupFormat);
        $closedTicketStatuses = WHMCS\Database\Capsule::table("tblticketstatuses")->where("showactive", "=", "0")->where("showawaiting", "=", "0")->pluck("title");
        $showclosebutton = !in_array($status, $closedTicketStatuses);
        $status = getStatusColour($status);
        $urgency = $_LANG["supportticketsticketurgency" . strtolower($urgency)];
        $customfields = getCustomFields("support", $deptid, $ticketId, "", "", "", true);
        ClientRead($ticketId);
        if ($admin) {
            $user = "<strong>" . $admin . "</strong><br />" . $_LANG["supportticketsstaff"];
        } else {
            if (0 < $userid) {
                $clientsdata = get_query_vals("tblclients", "firstname,lastname,email", array("id" => $userid));
                $clientname = $clientsdata["firstname"] . " " . $clientsdata["lastname"];
                $clientemail = $clientsdata["email"];
                $user = "<strong>" . $clientname . "</strong><br />" . $_LANG["supportticketsclient"];
                if (0 < $contactid) {
                    $contactdata = get_query_vals("tblcontacts", "firstname,lastname,email", array("id" => $contactid, "userid" => $userid));
                    $clientname = $contactdata["firstname"] . " " . $contactdata["lastname"];
                    $clientemail = $contactdata["email"];
                    $user = "<strong>" . $clientname . "</strong><br />" . $_LANG["supportticketscontact"];
                }
            } else {
                $clientname = $name;
                $clientemail = $email;
                $user = "<strong>" . $clientname . "</strong><br />" . $clientemail;
            }
        }
        $department = getDepartmentName($deptid);
        $attachments = array();
        if ($attachment) {
            $attachment = explode("|", $attachment);
            foreach ($attachment as $filename) {
                $filename = substr($filename, 7);
                $attachments[] = $filename;
            }
        }
        $smarty->assign("id", $ticketId);
        $smarty->assign("c", $c);
        $smarty->assign("tid", $tid);
        $smarty->assign("date", $date);
        $smarty->assign("departmentid", $deptid);
        $smarty->assign("department", $department);
        $smarty->assign("subject", $subject);
        $smarty->assign("message", $message);
        $smarty->assign("status", $status);
        $smarty->assign("urgency", $urgency);
        $smarty->assign("attachments", $attachments);
        $smarty->assign("attachments_removed", $attachmentsRemoved);
        $smarty->assign("user", $user);
        $smarty->assign("lastreply", $lastreply);
        $smarty->assign("showclosebutton", $showclosebutton);
        $smarty->assign("closedticket", !$showclosebutton);
        $smarty->assign("customfields", $customfields);
        $smarty->assign("ratingenabled", $CONFIG["TicketRatingEnabled"]);
        $locale = preg_replace("/[^a-zA-Z0-9_\\-]*/", "", Lang::getLanguageLocale());
        $locale = $locale == "locale" ? "en" : substr($locale, 0, 2);
        $smarty->assign("mdeLocale", $locale);
        $smarty->assign("loadMarkdownEditor", true);
        $replies = $ascreplies = array();
        $ascreplies[] = array("id" => "", "userid" => $userid, "contactid" => $contactid, "name" => $admin ? $admin : $clientname, "email" => $admin ? "" : $clientemail, "admin" => $admin ? true : false, "user" => $user, "admin" => $admin, "date" => $date, "message" => $message, "attachments" => $attachments, "attachments_removed" => $attachmentsRemoved, "rating" => $rating);
        $allattachments = array();
        $result = select_query("tblticketreplies", "", array("tid" => $ticketId), "date", "ASC");
        while ($data = mysql_fetch_array($result)) {
            $replyId = $data["id"];
            $userid = $data["userid"];
            $contactid = $data["contactid"];
            $admin = $data["admin"];
            $name = $data["name"];
            $email = $data["email"];
            $date = $data["date"];
            $message = $data["message"];
            $attachment = $data["attachment"];
            $attachmentsRemoved = (bool) (int) $data["attachments_removed"];
            $rating = $data["rating"];
            $date = fromMySQLDate($date, 1, 1);
            $markupFormat = $markup->determineMarkupEditor("ticket_reply", $data["editor"]);
            $message = $markup->transform($message, $markupFormat);
            if ($admin) {
                $user = "<strong>" . $admin . "</strong><br />" . $_LANG["supportticketsstaff"];
            } else {
                if (0 < $userid) {
                    $clientsdata = get_query_vals("tblclients", "firstname,lastname,email", array("id" => $userid));
                    $clientname = $clientsdata["firstname"] . " " . $clientsdata["lastname"];
                    $clientemail = $clientsdata["email"];
                    $user = "<strong>" . $clientname . "</strong><br />" . $_LANG["supportticketsclient"];
                    if (0 < $contactid) {
                        $contactdata = get_query_vals("tblcontacts", "firstname,lastname,email", array("id" => $contactid, "userid" => $userid));
                        $clientname = $contactdata["firstname"] . " " . $contactdata["lastname"];
                        $clientemail = $contactdata["email"];
                        $user = "<strong>" . $clientname . "</strong><br />" . $_LANG["supportticketscontact"];
                    }
                } else {
                    $clientname = $name;
                    $clientemail = $email;
                    $user = "<strong>" . $clientname . "</strong><br />" . $clientemail;
                }
            }
            $attachments = array();
            if ($attachment) {
                $attachment = explode("|", $attachment);
                $attachmentCount = 0;
                foreach ($attachment as $filename) {
                    $filename = substr($filename, 7);
                    $attachments[] = $filename;
                    $allattachments[] = array("replyid" => $replyId, "i" => $attachmentCount, "filename" => $filename, "removed" => $attachmentsRemoved);
                    $attachmentCount++;
                }
            }
            $ascreplies[] = array("id" => $replyId, "userid" => $userid, "contactid" => $contactid, "name" => $admin ? $admin : $clientname, "email" => $admin ? "" : $clientemail, "admin" => $admin ? true : false, "user" => $user, "date" => $date, "message" => $message, "attachments" => $attachments, "attachments_removed" => $attachmentsRemoved, "rating" => $rating);
            $replies[] = $ascreplies;
        }
        $smarty->assign("replies", $replies);
        $smarty->assign("ascreplies", $ascreplies);
        krsort($ascreplies);
        $smarty->assign("descreplies", $ascreplies);
        $ratings = array();
        for ($counter = 1; $counter <= 5; $counter++) {
            $ratings[] = $counter;
        }
        $smarty->assign("ratings", $ratings);
        if ($loggedInUserId) {
            $clientname = $clientsdetails["firstname"] . " " . $clientsdetails["lastname"];
            $clientemail = $clientsdetails["email"];
            if ($loggedInContactId) {
                $contactdata = get_query_vals("tblcontacts", "firstname,lastname,email", array("id" => $loggedInContactId, "userid" => $loggedInUserId));
                $clientname = $contactdata["firstname"] . " " . $contactdata["lastname"];
                $clientemail = $contactdata["email"];
            }
        }
        if (!$replyname) {
            $replyname = $clientname;
        }
        if (!$replyemail) {
            $replyemail = $clientemail;
        }
        $smarty->assign("errormessage", $errormessage);
        $smarty->assign("clientname", $clientname);
        $smarty->assign("email", $clientemail);
        $smarty->assign("replyname", $replyname);
        $smarty->assign("replyemail", $replyemail);
        $smarty->assign("replymessage", $replymessage);
        $smarty->assign("allowedfiletypes", implode(", ", $tickets->getAllowedAttachments()));
    }
}
Menu::addContext("ticketId", $ticketId);
Menu::addContext("ticket", $ticketData);
Menu::primarySidebar("ticketView");
Menu::secondarySidebar("ticketView");
outputClientArea($templatefile, false, array("ClientAreaPageViewTicket"));

?>