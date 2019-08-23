<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$action = $whmcs->get_req_var("action");
$sub = $whmcs->get_req_var("sub");
if (in_array($action, array("viewticket", "view", "gettags", "addTag", "removeTag", "split", "getmsg", "getticketlog", "getclientlog", "gettickets", "getallservices", "updatereply", "makingreply", "endreply", "checkstatus", "changestatus", "changeflag", "loadpredefinedreplies", "getpredefinedreply", "getquotedtext", "getcontacts", "markdown", "parseMarkdown"))) {
    $reqperm = "View Support Ticket";
} else {
    if ($action == "openticket" || $action == "open") {
        $reqperm = "Open New Ticket";
    } else {
        $reqperm = "List Support Tickets";
    }
}
if (!$action) {
    $aInt = new WHMCS\Admin($reqperm, false);
} else {
    $aInt = new WHMCS\Admin($reqperm);
}
if ($action == "markdown") {
    $body = "<div class=\"row\">\n    <div class=\"col-md-6\">\n        <h4>Emphasis</h4>\n        <pre>\n**<strong>bold</strong>**\n*<em>italics</em>*\n~~<strike>strikethrough</strike>~~</pre>\n\n        <h4>Headers</h4>\n        <pre class=\"markdown-content\">\n<h1 style=\"margin:0;\"># Big header</h1>\n<h2 style=\"margin:0;\">## Medium header</h2>\n<h3 style=\"margin:0;\">### Small header</h3>\n<h4 style=\"margin:0;\">#### Tiny header</h4>\n</pre>\n\n        <h4>Lists</h4>\n        <pre>\n* Generic list item\n* Generic list item\n* Generic list item\n\n1. Numbered list item\n2. Numbered list item\n3. Numbered list item</pre>\n    </div>\n    <div class=\"col-md-6\">\n        <h4>Links</h4>\n        <pre>[Text to display](http://www.example.com)</pre>\n\n        <h4>Quotes</h4>\n        <pre>\n> This is a quote.\n> It can span multiple lines!</pre>\n\n        <h4>Tables</h4>\n        <pre>\n| Column 1 | Column 2 | Column 3 |\n| -------- | -------- | -------- |\n| John     | Doe      | Male     |\n| Mary     | Smith    | Female   |\n</pre>\n<em>Or without aligning the columns...</em><br /><br />\n<pre>\n| Column 1 | Column 2 | Column 3 |\n| -------- | -------- | -------- |\n| John | Doe | Male |\n| Mary | Smith | Female |</pre>\n    </div>\n</div>\n\n        <h4>Displaying code</h4>\n        <pre>\n`var example = \"hello!\";`\n</pre>\n<em>Or spanning multiple lines...</em><br /><br />\n<pre>\n```\nvar example = \"hello!\";\nalert(example);\n```</pre>";
    $aInt->setBodyContent(array("body" => $body));
    $aInt->output();
    WHMCS\Terminus::getInstance()->doExit();
}
if ($action == "parseMarkdown") {
    $markup = new WHMCS\View\Markup\Markup();
    $content = App::get_req_var("content");
    $aInt->setBodyContent(array("body" => "<div class=\"markdown-content\">" . $markup->transform($content, "markdown") . "</div>"));
    $aInt->output();
    WHMCS\Terminus::getInstance()->doExit();
}
if ($action == "open" || $action == "openticket") {
    $icon = "ticketsopen";
    $title = $aInt->lang("support", "opennewticket");
} else {
    $icon = "tickets";
    $title = $aInt->lang("support", "supporttickets");
}
$aInt->title = $title;
$aInt->sidebar = "support";
$aInt->icon = $icon;
$aInt->helplink = "Support Tickets";
$aInt->requiredFiles(array("ticketfunctions", "modulefunctions", "customfieldfunctions"));
$filters = new WHMCS\Filter("tickets");
$smartyvalues = array();
$jscode = "";
if ($whmcs->get_req_var("ticketid")) {
    $action = "search";
}
$id = (int) App::getFromRequest("id");
if ($action == "gettags") {
    check_token("WHMCS.admin.default");
    $array = array();
    $q = App::getFromRequest("q");
    $result = WHMCS\Database\Capsule::table("tbltickettags")->where("ticketid", "!=", $id)->where("tag", "like", (string) $q . "%")->distinct()->orderBy("tag")->get();
    foreach ($result as $tagData) {
        $array[] = array("text" => $tagData->tag);
    }
    $aInt->jsonResponse($array);
}
if (in_array($action, array("addTag", "removeTag"))) {
    check_token("WHMCS.admin.default");
    $access = validateAdminTicketAccess($id);
    if (!$access) {
        $existingTags = $updatedTags = WHMCS\Database\Capsule::table("tbltickettags")->where("ticketid", "=", $id)->pluck("tag");
        $newTag = WHMCS\Input\Sanitize::decode(App::getFromRequest("newTag"));
        $removeTag = WHMCS\Input\Sanitize::decode(App::getFromRequest("removeTag"));
        if ($newTag && !in_array($newTag, $existingTags)) {
            WHMCS\Database\Capsule::table("tbltickettags")->insert(array("ticketid" => $id, "tag" => $newTag));
            addTicketLog($id, "Added Tag " . $newTag);
            $updatedTags[] = $newTag;
        }
        if ($removeTag && in_array($removeTag, $existingTags)) {
            WHMCS\Database\Capsule::table("tbltickettags")->where("ticketid", "=", $id)->where("tag", "=", $removeTag)->delete();
            addTicketLog($id, "Deleted Tag " . $removeTag);
            $updatedTags = array_flip($updatedTags);
            unset($updatedTags[$removeTag]);
            $updatedTags = array_flip($updatedTags);
        }
        WHMCS\Tickets::notifyTicketChanges($id, array("Ticket Tags" => array("old" => implode(", ", $existingTags), "new" => implode(", ", $updatedTags)), "Who" => getAdminName(WHMCS\Session::get("adminid"))));
    }
    WHMCS\Terminus::getInstance()->doExit();
}
if ($action == "checkstatus") {
    check_token("WHMCS.admin.default");
    $access = validateAdminTicketAccess($id);
    if ($access) {
        exit;
    }
    $result = select_query("tbltickets", "status", array("id" => $id));
    $data = mysql_fetch_assoc($result);
    $status = $data["status"];
    if ($status == $ticketstatus) {
        echo "true";
    } else {
        echo "false";
    }
    exit;
}
if ($action == "validatereply") {
    check_token("WHMCS.admin.default");
    $ticketId = $whmcs->getFromRequest("id");
    $response = array("valid" => false, "changes" => false, "currentStatus" => "");
    $access = validateAdminTicketAccess($ticketId);
    if ($ticketId == "opening") {
        $response = array("valid" => true, "changes" => false, "changeList" => "");
    } else {
        if (!$access) {
            $changeList = checkTicketChanges($ticketId);
            $changes = 0 < count($changeList);
            $response = array("valid" => true, "changes" => $changes, "changeList" => implode("\r\n", $changeList));
        }
    }
    $aInt->setBodyContent($response);
    $aInt->display();
    WHMCS\Terminus::getInstance()->doExit();
}
if ($action == "split") {
    if (empty($rids)) {
        redir("action=viewticket&id=" . $id);
    }
    check_token("WHMCS.admin.default");
    $access = validateAdminTicketAccess($id);
    if ($access) {
        exit;
    }
    $rids = db_escape_numarray($rids);
    $splitCount = count($rids);
    $noemail = !$splitnotifyclient ? true : false;
    $result = select_query("tbltickets", "", array("id" => $id));
    $data = mysql_fetch_array($result);
    $oldTicketID = $data["tid"];
    $newTicketUserid = $data["userid"];
    $newTicketContactid = $data["contactid"];
    $newTicketdepartmentid = $data["did"];
    $newTicketName = $data["name"];
    $newTicketEmail = $data["email"];
    $newTicketAttachment = $data["attachment"];
    $newTicketUrgency = $data["urgency"];
    $newTicketCC = $data["cc"];
    $newTicketService = $data["service"];
    $newTicketTitle = $data["title"];
    $newTicketEditor = $data["editor"];
    $data = WHMCS\Database\Capsule::table("tblticketreplies")->whereIn("id", $rids)->orderBy("date")->first();
    $messageEarliestID = $data->id;
    $messageEarliest = $data->message;
    $messageAdmin = $data->admin;
    $messageAttachments = $data->attachment;
    $messageEarliestDate = $data->date;
    if ($messageAttachments) {
        $newTicketAttachment .= trim($newTicketAttachment) ? "|" . $messageAttachments : $messageAttachments;
    }
    $subject = trim($splitsubject) ? $splitsubject : $newTicketTitle;
    $deptid = trim($splitdeptid) ? $splitdeptid : $newTicketdepartmentid;
    $priority = trim($splitpriority) ? $splitpriority : $newTicketUrgency;
    $newOpenedTicketResults = openNewTicket($newTicketUserid, $newTicketContactid, $deptid, $subject, $messageEarliest, $priority, $newTicketAttachment, array("name" => $newTicketName, "email" => $newTicketEmail), $newTicketService, $newTicketCC, $noemail, $messageAdmin, $newTicketEditor == "markdown");
    $newTicketID = $newOpenedTicketResults["ID"];
    copyCustomFieldValues("support", $id, $newTicketID);
    WHMCS\Database\Capsule::table("tbltickets")->where("id", "=", $newTicketID)->update(array("date" => $messageEarliestDate));
    $repliesPlural = 1 < $splitCount ? "Replies" : "Reply";
    addTicketLog($id, "Ticket " . $repliesPlural . " Split to New Ticket #" . $newOpenedTicketResults["TID"]);
    addTicketLog($newTicketID, "Ticket " . $repliesPlural . " Split from Ticket #" . $oldTicketID);
    WHMCS\Database\Capsule::table("tblticketreplies")->where("id", "=", $messageEarliestID)->delete();
    WHMCS\Database\Capsule::table("tblticketreplies")->whereIn("id", $rids)->update(array("tid" => $newTicketID));
    redir("action=viewticket&id=" . $newTicketID);
}
if ($action == "getmsg") {
    check_token("WHMCS.admin.default");
    $msg = "";
    $id = substr($ref, 1);
    if (substr($ref, 0, 1) == "t") {
        $access = validateAdminTicketAccess($id);
        if ($access) {
            exit;
        }
        $msg = get_query_val("tbltickets", "message", array("id" => $id));
    } else {
        if (substr($ref, 0, 1) == "r") {
            $data = get_query_vals("tblticketreplies", "tid,message", array("id" => $id));
            $id = $data["tid"];
            $msg = $data["message"];
            $access = validateAdminTicketAccess($id);
            if ($access) {
                exit;
            }
        }
    }
    echo WHMCS\Input\Sanitize::decode($msg);
    exit;
}
if ($action == "getticketlog") {
    check_token("WHMCS.admin.default");
    $access = validateAdminTicketAccess($id);
    if ($access) {
        exit;
    }
    $totaltickets = get_query_val("tblticketlog", "COUNT(id)", array("tid" => $id));
    $qlimit = 10;
    $offset = (int) $offset;
    if ($offset < 0) {
        $offset = 0;
    }
    $endnum = $offset + $qlimit;
    echo "<div style=\"padding:0 0 5px 0;text-align:left;\">Showing <strong>" . ($offset + 1) . "</strong> to <strong>" . ($totaltickets < $endnum ? $totaltickets : $endnum) . "</strong> of <strong>" . $totaltickets . " total</strong></div>";
    $aInt->sortableTableInit("nopagination");
    $result = select_query("tblticketlog", "", array("tid" => $id), "date", "DESC", (string) $offset . "," . $qlimit);
    while ($data = mysql_fetch_array($result)) {
        $tabledata[] = array(fromMySQLDate($data["date"], 1), "<div style=\"text-align:left;\">" . $data["action"] . "</div>");
    }
    echo $aInt->sortableTable(array($aInt->lang("fields", "date"), $aInt->lang("permissions", "action")), $tabledata);
    echo "<table width=\"80%\" align=\"center\"><tr><td style=\"text-align:left;\">";
    if (0 < $offset) {
        echo "<a href=\"#\" onclick=\"loadTab(" . $target . ",'ticketlog'," . ($offset - $qlimit) . ");return false\">";
    }
    echo "&laquo; Previous</a></td><td style=\"text-align:right;\">";
    if ($endnum < $totaltickets) {
        echo "<a href=\"#\" onclick=\"loadTab(" . $target . ",'ticketlog'," . $endnum . ");return false\">";
    }
    echo "Next &raquo;</a></td></tr></table>";
    exit;
}
if ($action == "getclientlog") {
    check_token("WHMCS.admin.default");
    checkPermission("View Activity Log");
    if ($userid == 0) {
        echo AdminLang::trans("general.noActivityLogForClient");
        exit;
    }
    $log = new WHMCS\Log\Activity();
    $log->setCriteria(array("userid" => $userid));
    $totaltickets = $log->getTotalCount();
    $qlimit = 10;
    $page = (int) $whmcs->get_req_var("offset");
    if ($page < 0) {
        $page = 0;
    }
    $start = $page * $qlimit;
    $endnum = $start + $qlimit;
    echo "<div style=\"padding:0 0 5px 0;text-align:left;\">Showing <strong>" . ($start + 1) . "</strong> to <strong>" . ($totaltickets < $endnum ? $totaltickets : $endnum) . "</strong> of <strong>" . $totaltickets . " total</strong></div>";
    $aInt->sortableTableInit("nopagination");
    $tabledata = array();
    $logs = $log->getLogEntries($page, $qlimit);
    foreach ($logs as $entry) {
        $tabledata[] = array($entry["date"], "<div align=\"left\">" . $entry["description"] . "</div>", $entry["username"], $entry["ipaddress"]);
    }
    echo $aInt->sortableTable(array($aInt->lang("fields", "date"), $aInt->lang("fields", "description"), $aInt->lang("fields", "username"), $aInt->lang("fields", "ipaddress")), $tabledata);
    echo "<table width=\"80%\" align=\"center\"><tr><td style=\"text-align:left;\">";
    if (0 < $offset) {
        echo "<a href=\"#\" onclick=\"loadTab(" . $target . ",'clientlog'," . ($page - 1) . ");return false\">";
    }
    echo "&laquo; Previous</a></td><td style=\"text-align:right;\">";
    if ($endnum < $totaltickets) {
        echo "<a href=\"#\" onclick=\"loadTab(" . $target . ",'clientlog'," . ($page + 1) . ");return false\">";
    }
    echo "Next &raquo;</a></td></tr></table>";
    exit;
} else {
    if ($action == "gettickets") {
        check_token("WHMCS.admin.default");
        $departmentsarray = getDepartments();
        if ($userid) {
            $where = array("userid" => $userid);
        } else {
            $where = array("email" => get_query_val("tbltickets", "email", array("id" => $id)));
        }
        $totaltickets = get_query_val("tbltickets", "COUNT(id)", $where);
        $qlimit = 5;
        $offset = (int) $offset;
        if ($offset < 0) {
            $offset = 0;
        }
        $endnum = $offset + $qlimit;
        echo "<div style=\"padding:0 0 5px 0;text-align:left;\">Showing <strong>" . ($offset + 1) . "</strong> to <strong>" . ($totaltickets < $endnum ? $totaltickets : $endnum) . "</strong> of <strong>" . $totaltickets . " total</strong></div>";
        $aInt->sortableTableInit("nopagination");
        $result = select_query("tbltickets", "", $where, "lastreply", "DESC", (string) $offset . "," . $qlimit);
        while ($data = mysql_fetch_array($result)) {
            $id = $data["id"];
            $ticketnumber = $data["tid"];
            $did = $data["did"];
            $puserid = $data["userid"];
            $name = $data["name"];
            $email = $data["email"];
            $date = $data["date"];
            $title = $data["title"];
            $message = $data["message"];
            $tstatus = $data["status"];
            $priority = $data["urgency"];
            $rawlastactivity = $data["lastreply"];
            $flag = $data["flag"];
            $adminread = $data["adminunread"];
            $adminread = explode(",", $adminread);
            if (!in_array($_SESSION["adminid"], $adminread)) {
                $unread = 1;
            } else {
                $unread = 0;
            }
            if (!trim($title)) {
                $title = "(" . $aInt->lang("emails", "nosubject") . ")";
            }
            $flaggedto = "";
            if ($flag == $_SESSION["adminid"]) {
                $showflag = "user";
            } else {
                if ($flag == 0) {
                    $showflag = "none";
                } else {
                    $showflag = "other";
                    $flaggedto = getAdminName($flag);
                }
            }
            $department = $departmentsarray[$did];
            if ($flaggedto) {
                $department .= " (" . $flaggedto . ")";
            }
            $date = fromMySQLDate($date, "time");
            $lastactivity = fromMySQLDate($rawlastactivity, "time");
            $tstatus = getStatusColour($tstatus);
            $lastreply = getShortLastReplyTime($rawlastactivity);
            $flagstyle = $showflag == "user" ? "<span class=\"ticketflag\">" : "";
            $title = "#" . $ticketnumber . " - " . $title;
            if ($unread || $showflag == "user") {
                $title = "<strong>" . $title . "</strong>";
            }
            $ticketlink = "<a href=\"?action=viewticket&id=" . $id . "\"" . $ainject . ">";
            $tabledata[] = array("<img src=\"images/" . strtolower($priority) . "priority.gif\" width=\"16\" height=\"16\" alt=\"" . $priority . "\" class=\"absmiddle\" />", $flagstyle . $date, $flagstyle . $department, "<div style=\"text-align:left;\">" . $flagstyle . $ticketlink . $title . "</a></div>", $flagstyle . $tstatus, $flagstyle . $lastreply);
        }
        echo $aInt->sortableTable(array("", $aInt->lang("support", "datesubmitted"), $aInt->lang("support", "department"), $aInt->lang("fields", "subject"), $aInt->lang("fields", "status"), $aInt->lang("support", "lastreply")), $tabledata);
        echo "<table width=\"80%\" align=\"center\"><tr><td style=\"text-align:left;\">";
        if (0 < $offset) {
            echo "<a href=\"#\" onclick=\"loadTab(" . $target . ",'tickets'," . ($offset - $qlimit) . ");return false\">";
        }
        echo "&laquo; Previous</a></td><td style=\"text-align:right;\">";
        if ($endnum < $totaltickets) {
            echo "<a href=\"#\" onclick=\"loadTab(" . $target . ",'tickets'," . $endnum . ");return false\">";
        }
        echo "Next &raquo;</a></td></tr></table>";
        exit;
    }
    if ($action == "getallservices") {
        check_token("WHMCS.admin.default");
        $pauserid = (int) $userid;
        $currency = getCurrency($pauserid);
        $service = get_query_val("tbltickets", "service", array("id" => $id));
        $selectedRelatedId = 0;
        $selectedRelatedType = "";
        if ($service) {
            $selectedRelatedType = substr($service, 0, 1);
            $selectedRelatedId = substr($service, 1);
        }
        $output = array();
        $result = select_query("tblhosting", "tblhosting.*,tblproducts.name", array("userid" => $pauserid), "domainstatus` ASC,`id", "DESC", "", "tblproducts ON tblproducts.id=tblhosting.packageid");
        while ($data = mysql_fetch_array($result)) {
            $service_id = $data["id"];
            if ($selectedRelatedType == "S" && $selectedRelatedId == $service_id) {
                continue;
            }
            $service_name = $data["name"];
            $service_domain = $data["domain"];
            $service_firstpaymentamount = $data["firstpaymentamount"];
            $service_recurringamount = $data["amount"];
            $service_billingcycle = $data["billingcycle"];
            $service_regdate = $data["regdate"];
            $service_regdate = fromMySQLDate($service_regdate);
            if (in_array($data["billingcycle"], array("One Time", "Free Account"))) {
                $service_nextduedate = "-";
            } else {
                $service_nextduedate = fromMySQLDate($data["nextduedate"]);
            }
            if ($service_recurringamount <= 0) {
                $service_amount = $service_firstpaymentamount;
            } else {
                $service_amount = $service_recurringamount;
            }
            $service_amount = formatCurrency($service_amount);
            $selected = false;
            $service_name = "<a href=\"clientshosting.php?userid=" . $pauserid . "&id=" . $service_id . "\" target=\"_blank\">" . $service_name . "</a> - <a href=\"http://" . $service_domain . "/\" target=\"_blank\">" . $service_domain . "</a>";
            $output[] = "<tr" . ($selected ? " class=\"rowhighlight\"" : "") . "><td>" . $service_name . "</td><td>" . $service_amount . "</td><td>" . $service_billingcycle . "</td><td>" . $service_regdate . "</td><td>" . $service_nextduedate . "</td><td>" . $data["domainstatus"] . "</td></tr>";
        }
        $predefinedaddons = WHMCS\Database\Capsule::table("tbladdons")->pluck("name", "id");
        $result = select_query("tblhostingaddons", "tblhostingaddons.*,tblhostingaddons.id AS addonid,tblhostingaddons.addonid AS addonid2,tblhostingaddons.name AS addonname,tblhosting.id AS hostingid,tblhosting.domain,tblproducts.name", array("tblhosting.userid" => $pauserid), "status` ASC,`tblhosting`.`id", "DESC", "", "tblhosting ON tblhosting.id=tblhostingaddons.hostingid INNER JOIN tblproducts ON tblproducts.id=tblhosting.packageid");
        while ($data = mysql_fetch_array($result)) {
            $service_id = $data["id"];
            if ($selectedRelatedType == "A" && $selectedRelatedId == $service_id) {
                continue;
            }
            $hostingid = $data["hostingid"];
            $service_addonid = $data["addonid2"];
            $service_name = $data["name"];
            $service_addon = $data["addonname"];
            $service_domain = $data["domain"];
            $service_recurringamount = $data["recurring"];
            $service_billingcycle = $data["billingcycle"];
            $service_regdate = $data["regdate"];
            $service_regdate = fromMySQLDate($service_regdate);
            if (in_array($data["billingcycle"], array("One Time", "Free Account"))) {
                $service_nextduedate = "-";
            } else {
                $service_nextduedate = fromMySQLDate($data["nextduedate"]);
            }
            if ($service_recurringamount <= 0) {
                $service_amount = $service_firstpaymentamount;
            } else {
                $service_amount = $service_recurringamount;
            }
            if (!$service_addon) {
                $service_addon = $predefinedaddons[$service_addonid];
            }
            $service_amount = formatCurrency($service_recurringamount);
            $selected = false;
            $service_name = $aInt->lang("orders", "addon") . " - " . $service_addon . "<br /><a href=\"clientshosting.php?userid=" . $pauserid . "&id=" . $hostingid . "\" target=\"_blank\">" . $service_name . "</a> - <a href=\"http://" . $service_domain . "/\" target=\"_blank\">" . $service_domain . "</a>";
            $output[] = "<tr" . ($selected ? " class=\"rowhighlight\"" : "") . "><td>" . $service_name . "</td><td>" . $service_amount . "</td><td>" . $service_billingcycle . "</td><td>" . $service_regdate . "</td><td>" . $service_nextduedate . "</td><td>" . $data["status"] . "</td></tr>";
        }
        $result = select_query("tbldomains", "", array("userid" => $pauserid), "status` ASC,`id", "DESC");
        while ($data = mysql_fetch_array($result)) {
            $service_id = $data["id"];
            if ($selectedRelatedType == "D" && $selectedRelatedId == $service_id) {
                continue;
            }
            $service_domain = $data["domain"];
            $service_firstpaymentamount = $data["firstpaymentamount"];
            $service_recurringamount = $data["recurringamount"];
            $service_registrationperiod = $data["registrationperiod"] . " Year(s)";
            $service_regdate = $data["registrationdate"];
            $service_regdate = fromMySQLDate($service_regdate);
            $service_nextduedate = $data["nextduedate"];
            $service_nextduedate = $service_nextduedate == "0000-00-00" ? "-" : fromMySQLDate($service_nextduedate);
            if ($service_recurringamount <= 0) {
                $service_amount = $service_firstpaymentamount;
            } else {
                $service_amount = $service_recurringamount;
            }
            $service_amount = formatCurrency($service_amount);
            $selected = false;
            $service_name = "<a href=\"clientsdomains.php?userid=" . $pauserid . "&id=" . $service_id . "\" target=\"_blank\">" . $aInt->lang("fields", "domain") . "</a> - <a href=\"http://" . $service_domain . "/\" target=\"_blank\">" . $service_domain . "</a>";
            $output[] = "<tr" . ($selected ? " class=\"rowhighlight\"" : "") . "><td>" . $service_name . "</td><td>" . $service_amount . "</td><td>" . $service_registrationperiod . "</td><td>" . $service_regdate . "</td><td>" . $service_nextduedate . "</td><td>" . $data["status"] . "</td></tr>";
        }
        for ($i = 0; $i <= 9; $i++) {
            unset($output[$i]);
        }
        echo implode($output);
        exit;
    }
    if ($action == "updatereply") {
        check_token("WHMCS.admin.default");
        $ref = App::get_req_var("ref");
        $id = App::get_req_var("id");
        if (substr($ref, 0, 1) == "t") {
            update_query("tbltickets", array("message" => $text), array("id" => substr($ref, 1)));
            $editor = get_query_val("tbltickets", "editor", array("id" => substr($ref, 1)));
            $contentType = "ticket_msg";
        } else {
            if (substr($ref, 0, 1) == "r") {
                update_query("tblticketreplies", array("message" => $text), array("id" => substr($ref, 1)));
                $editor = get_query_val("tblticketreplies", "editor", array("id" => substr($ref, 1)));
                $contentType = "ticket_reply";
            } else {
                if ($id && is_numeric($id)) {
                    update_query("tblticketreplies", array("message" => $text), array("id" => $id));
                    $editor = get_query_val("tbltickets", "editor", array("id" => $id));
                    $contentType = "ticket_reply";
                }
            }
        }
        $markup = new WHMCS\View\Markup\Markup();
        $markupFormat = $markup->determineMarkupEditor($contentType, $editor);
        echo $markup->transform($text, $markupFormat);
        exit;
    }
    if ($action == "makingreply") {
        check_token("WHMCS.admin.default");
        $access = validateAdminTicketAccess($id);
        if ($access) {
            exit;
        }
        $result = select_query("tbltickets", "replyingadmin,replyingtime", array("id" => $id, "replyingadmin" => array("sqltype" => ">", "value" => "0")));
        if (mysql_num_rows($result)) {
            $data = mysql_fetch_assoc($result);
            $replyingadmin = $data["replyingadmin"];
            $replyingtime = $data["replyingtime"];
            $replyingtime = fromMySQLDate($replyingtime, "time");
            if ($replyingadmin != WHMCS\Session::get("adminid")) {
                $result = select_query("tbladmins", "", array("id" => $replyingadmin));
                $data = mysql_fetch_array($result);
                $replyingadmin = ucfirst($data["username"]);
                $aInt->setBodyContent(array("isReplying" => 1, "replyingMsg" => $replyingadmin . " " . $aInt->lang("support", "viewedandstarted") . " @ " . $replyingtime));
            }
        } else {
            update_query("tbltickets", array("replyingadmin" => $_SESSION["adminid"], "replyingtime" => "now()"), array("id" => $id));
        }
        $aInt->setBodyContent(array("isReplying" => 0));
        $aInt->display();
        exit;
    }
    if ($action == "endreply") {
        check_token("WHMCS.admin.default");
        $access = validateAdminTicketAccess($id);
        if ($access) {
            exit;
        }
        update_query("tbltickets", array("replyingadmin" => ""), array("id" => $id));
        exit;
    }
    if ($action == "changestatus") {
        check_token("WHMCS.admin.default");
        $access = validateAdminTicketAccess($id);
        if ($access) {
            exit;
        }
        $status = App::getFromRequest("status");
        $ticketId = (int) App::getFromRequest("id");
        $skip = (bool) (int) App::getFromRequest("skip");
        $lastReplyId = (int) App::getFromRequest("lastReplyId");
        $changes = false;
        $changeList = array();
        $ticketInfo = WHMCS\Database\Capsule::table("tbltickets")->where("tbltickets.id", $ticketId)->leftJoin("tblticketreplies", function (Illuminate\Database\Query\JoinClause $query) use($lastReplyId) {
            $query->on("tbltickets.id", "=", "tblticketreplies.tid")->on("tblticketreplies.id", ">", WHMCS\Database\Capsule::raw($lastReplyId));
        })->groupBy("tblticketreplies.tid")->orderBy("tblticketreplies.id", "DESC")->first(array("tbltickets.status", "tbltickets.cc", "tbltickets.userid", "tbltickets.did", "tbltickets.flag", "tbltickets.urgency", "tbltickets.title", "tblticketreplies.id as lastReplyId", "tblticketreplies.admin as replyAdminName", "tblticketreplies.userid as replyUserId"));
        if (!$skip) {
            $changeList = checkTicketChanges($ticketId, $ticketInfo);
            $changes = 0 < count($changeList);
        }
        if (!$changes && $status != $ticketInfo->status) {
            if ($status == "Closed") {
                closeTicket($id);
            } else {
                addTicketLog($id, "Status changed to " . $status);
                update_query("tbltickets", array("status" => $status), array("id" => $id));
                WHMCS\Tickets::notifyTicketChanges($id, array("Status" => array("old" => $ticketInfo->status, "new" => $status), "Who" => getAdminName(WHMCS\Session::get("adminid"))));
                run_hook("TicketStatusChange", array("adminid" => $_SESSION["adminid"], "status" => $status, "ticketid" => $id));
            }
        }
        $response = array("valid" => true, "changes" => $changes, "changeList" => implode("\r\n", $changeList));
        $aInt->setBodyContent($response);
        $aInt->display();
        WHMCS\Terminus::getInstance()->doExit();
    }
    if ($action == "changeflag") {
        check_token("WHMCS.admin.default");
        $access = validateAdminTicketAccess($id);
        if ($access) {
            exit;
        }
        $oldFlag = get_query_val("tbltickets", "flag", array("id" => $id));
        $oldFlag = $oldFlag ? getAdminName($oldFlag) : "Nobody";
        addTicketLog($id, "Flagged to " . getAdminName($flag));
        update_query("tbltickets", array("flag" => $flag), array("id" => $id));
        WHMCS\Tickets::notifyTicketChanges($id, array("Assigned To" => array("old" => $oldFlag, "oldId" => $oldFlag ?: 0, "new" => $flag ? getAdminName($flag) : "Nobody", "newId" => $flag ?: 0), "Who" => getAdminName(WHMCS\Session::get("adminid"))));
        if ($flag != 0 && $flag != $_SESSION["adminid"]) {
            echo "1";
        }
        exit;
    }
    if ($action == "loadpredefinedreplies") {
        check_token("WHMCS.admin.default");
        echo genPredefinedRepliesList($cat, $predefq);
        exit;
    }
    if ($action == "getpredefinedreply") {
        check_token("WHMCS.admin.default");
        $result = select_query("tblticketpredefinedreplies", "", array("id" => $id));
        $data = mysql_fetch_array($result);
        $reply = WHMCS\Input\Sanitize::decode($data["reply"]);
        echo $reply;
        exit;
    }
    if ($action == "getquotedtext") {
        check_token("WHMCS.admin.default");
        $replytext = "";
        if ($id) {
            $access = validateAdminTicketAccess($id);
            if ($access) {
                exit;
            }
            $result = select_query("tbltickets", "message", array("id" => $id));
            $data = mysql_fetch_array($result);
            $replytext = $data["message"];
        } else {
            if ($ids) {
                $result = select_query("tblticketreplies", "tid,message", array("id" => $ids));
                $data = mysql_fetch_array($result);
                $id = $data["tid"];
                $access = validateAdminTicketAccess($id);
                if ($access) {
                    exit;
                }
                $replytext = $data["message"];
            }
        }
        $replytext = wordwrap(WHMCS\Input\Sanitize::decode(strip_tags($replytext)), 80);
        $replytext = explode("\n", $replytext);
        foreach ($replytext as $line) {
            echo "> " . $line . "\n";
        }
        exit;
    } else {
        if ($action == "getcontacts") {
            check_token("WHMCS.admin.default");
            echo getTicketContacts($userid);
            exit;
        }
        if ($action == "getcustomfields") {
            check_token("WHMCS.admin.default");
            $id = $whmcs->get_req_var("id");
            $deptID = get_query_val("tbltickets", "did", array("id" => $id));
            $customFields = getCustomFields("support", $deptID, $id, true);
            $aInt->assign("csrfToken", generate_token("plain"));
            $aInt->assign("csrfTokenHiddenInput", generate_token());
            $aInt->assign("ticketid", $id);
            $aInt->assign("customfields", $customFields);
            $aInt->assign("numcustomfields", count($customFields));
            echo $aInt->getTemplate("viewticketcustomfields", false);
            exit;
        }
        if ($action == "mergetickets") {
            check_token("WHMCS.admin.default");
            sort($selectedTickets);
            if (1 < count($selectedTickets)) {
                $masterTID = $selectedTickets[0];
                addTicketLog($masterTID, "Merged Tickets " . implode(",", $selectedTickets));
                $masterTicketData = WHMCS\Database\Capsule::table("tbltickets")->find($masterTID, array("title", "userid", "lastreply", "status"));
                $userID = $masterTicketData->userid;
                getUsersLang($userID);
                $merge = $_LANG["ticketmerge"];
                if (!$merge || $merge == "") {
                    $merge = "MERGED";
                }
                $subject = strpos($masterTicketData->title, " [" . $merge . "]") === false ? $masterTicketData->title . " [" . $merge . "]" : $masterTicketData->title;
                $masterTicketLastReply = WHMCS\Carbon::createFromTimestamp(strtotime($masterTicketData->lastreply));
                $ticketLastReply = WHMCS\Carbon::createFromTimestamp(strtotime($masterTicketData->lastreply));
                $ticketStatus = $masterTicketData->status;
                WHMCS\Database\Capsule::table("tbltickets")->where("id", "=", $masterTID)->update(array("title" => $subject));
                foreach ($selectedTickets as $id) {
                    WHMCS\Database\Capsule::table("tblticketlog")->where("tid", "=", $id)->update(array("tid" => $masterTID));
                    WHMCS\Database\Capsule::table("tblticketnotes")->where("ticketid", "=", $id)->update(array("ticketid" => $masterTID));
                    WHMCS\Database\Capsule::table("tblticketreplies")->where("tid", "=", $id)->update(array("tid" => $masterTID));
                    if ($id != $masterTID) {
                        $ticketData = WHMCS\Database\Capsule::table("tbltickets")->find($id);
                        WHMCS\Database\Capsule::table("tblticketreplies")->insert(array("tid" => $masterTID, "userid" => $userID, "name" => $ticketData->name, "email" => $ticketData->email, "date" => $ticketData->date, "message" => $ticketData->message, "admin" => $ticketData->admin, "attachment" => $ticketData->attachment));
                        $thisTicketLastReply = WHMCS\Carbon::createFromTimestamp(strtotime($ticketData->lastreply));
                        if ($ticketLastReply < $thisTicketLastReply) {
                            $ticketLastReply = $thisTicketLastReply;
                            $ticketStatus = $ticketData->status;
                        }
                        WHMCS\Database\Capsule::table("tbltickets")->where("id", "=", $id)->update(array("merged_ticket_id" => $masterTID, "status" => "Closed", "message" => "", "admin" => "", "attachment" => "", "email" => "", "flag" => 0));
                        WHMCS\Database\Capsule::table("tbltickets")->where("merged_ticket_id", "=", $id)->update(array("merged_ticket_id" => $masterTID));
                        addTicketLog($id, "Ticket ID: " . $id . " Merged with Ticket ID: " . $masterTID);
                    }
                }
                if ($masterTicketLastReply < $ticketLastReply) {
                    WHMCS\Database\Capsule::table("tbltickets")->where("id", "=", $masterTID)->update(array("lastreply" => $ticketLastReply->format("Y-m-d H:i:s"), "status" => $ticketStatus));
                }
                echo 1;
            } else {
                echo 0;
            }
            exit;
        } else {
            if ($action == "deletetickets") {
                check_token("WHMCS.admin.default");
                if (0 < count($selectedTickets)) {
                    if (!checkPermission("Delete Ticket", true)) {
                        echo "denied";
                        exit;
                    }
                    foreach ($selectedTickets as $id) {
                        deleteTicket($id);
                    }
                    echo 1;
                } else {
                    echo 0;
                }
                exit;
            } else {
                if ($action == "blockdeletetickets") {
                    check_token("WHMCS.admin.default");
                    if (0 < count($selectedTickets)) {
                        if (!checkPermission("Delete Ticket", true)) {
                            echo "denied";
                            exit;
                        }
                        foreach ($selectedTickets as $id) {
                            $result = select_query("tbltickets", "userid, email", array("id" => $id));
                            $data = mysql_fetch_array($result);
                            $userID = $data["userid"];
                            $email = $data["email"];
                            if ($userID) {
                                $result = select_query("tblclients", "email", array("id" => $userID));
                                $data = mysql_fetch_array($result);
                                $email = $data["email"];
                            }
                            $result = select_query("tblticketspamfilters", "COUNT(*)", array("type" => "Sender", "content" => $email));
                            $data = mysql_fetch_array($result);
                            $blockedAlready = $data[0];
                            if (!$blockedAlready) {
                                insert_query("tblticketspamfilters", array("type" => "Sender", "content" => $email));
                            }
                            deleteTicket($id);
                        }
                        echo 1;
                    } else {
                        echo 0;
                    }
                    exit;
                } else {
                    if ($action == "closetickets") {
                        check_token("WHMCS.admin.default");
                        if (0 < count($selectedTickets)) {
                            foreach ($selectedTickets as $id) {
                                closeTicket($id);
                            }
                            echo 1;
                        } else {
                            echo 0;
                        }
                        exit;
                    } else {
                        if ($action == "watcher_update") {
                            check_token("WHMCS.admin.default");
                            $type = $whmcs->get_req_var("type");
                            $ticketId = (int) $whmcs->get_req_var("ticket_id");
                            $adminId = (int) $whmcs->get_req_var("admin_id");
                            if ($type == "watch") {
                                $existingWatchedTickets = WHMCS\Ticket\Watchers::byAdmin($adminId)->pluck("ticket_id")->all();
                                if (!in_array($ticketId, $existingWatchedTickets)) {
                                    $watcher = new WHMCS\Ticket\Watchers();
                                    $watcher->adminId = $adminId;
                                    $watcher->ticketId = $ticketId;
                                    $watcher->save();
                                }
                                echo 1;
                            } else {
                                if ($type == "unwatch") {
                                    $existingWatchedTickets = WHMCS\Ticket\Watchers::byAdmin($adminId)->pluck("ticket_id")->all();
                                    if (in_array($ticketId, $existingWatchedTickets)) {
                                        WHMCS\Ticket\Watchers::whereTicketId($ticketId)->whereAdminId($adminId)->delete();
                                    }
                                    echo 1;
                                } else {
                                    echo 0;
                                }
                            }
                            WHMCS\Terminus::getInstance()->doExit();
                        }
                        if (!$action) {
                            if ($sub == "deleteticket") {
                                check_token("WHMCS.admin.default");
                                checkPermission("Delete Ticket");
                                deleteTicket($id);
                                $filters->redir();
                            }
                        } else {
                            if ($action == "mergeticket") {
                                check_token("WHMCS.admin.default");
                                $result = select_query("tbltickets", "id", array("tid" => $mergetid));
                                $data = mysql_fetch_array($result);
                                $mergeid = $data["id"];
                                if (!$mergeid) {
                                    exit($aInt->lang("support", "mergeidnotfound"));
                                }
                                if ($mergeid == $id) {
                                    exit($aInt->lang("support", "mergeticketequal"));
                                }
                                $mastertid = $id;
                                if ($mergeid < $mastertid) {
                                    $mastertid = $mergeid;
                                    $mergeid = $id;
                                }
                                $adminname = getAdminName();
                                addTicketLog($mastertid, "Merged Ticket " . $mergeid);
                                $adminname = "";
                                $masterTicketData = WHMCS\Database\Capsule::table("tbltickets")->find($mastertid, array("title", "userid", "lastreply"));
                                $userID = $masterTicketData->userid;
                                getUsersLang($userID);
                                $merge = $_LANG["ticketmerge"];
                                if (!$merge) {
                                    $merge = "MERGED";
                                }
                                $subject = strpos($masterTicketData->title, " [" . $merge . "]") === false ? $masterTicketData->title . " [" . $merge . "]" : $masterTicketData->title;
                                WHMCS\Database\Capsule::table("tbltickets")->where("id", "=", $mastertid)->update(array("title" => $subject));
                                WHMCS\Database\Capsule::table("tblticketlog")->where("tid", "=", $mergeid)->update(array("tid" => $mastertid));
                                WHMCS\Database\Capsule::table("tblticketnotes")->where("ticketid", "=", $mergeid)->update(array("ticketid" => $mastertid));
                                WHMCS\Database\Capsule::table("tblticketreplies")->where("tid", "=", $mergeid)->update(array("tid" => $mastertid));
                                $ticketData = WHMCS\Database\Capsule::table("tbltickets")->find($mergeid);
                                WHMCS\Database\Capsule::table("tblticketreplies")->insert(array("tid" => $mastertid, "userid" => $userID, "name" => $ticketData->name, "email" => $ticketData->email, "date" => $ticketData->date, "message" => $ticketData->message, "admin" => $ticketData->admin, "attachment" => $ticketData->attachment));
                                $masterTicketLastReply = WHMCS\Carbon::createFromTimestamp(strtotime($masterTicketData->lastreply));
                                $ticketLastReply = WHMCS\Carbon::createFromTimestamp(strtotime($ticketData->lastreply));
                                if ($masterTicketLastReply < $ticketLastReply) {
                                    WHMCS\Database\Capsule::table("tbltickets")->where("id", "=", $mastertid)->update(array("lastreply" => $ticketData->lastreply, "status" => $ticketData->status));
                                }
                                WHMCS\Database\Capsule::table("tbltickets")->where("id", "=", $mergeid)->update(array("merged_ticket_id" => $mastertid, "status" => "Closed", "message" => "", "admin" => "", "attachment" => "", "email" => "", "flag" => 0));
                                WHMCS\Database\Capsule::table("tbltickets")->where("merged_ticket_id", "=", $mergeid)->update(array("merged_ticket_id" => $mastertid));
                                addTicketLog($id, "Ticket ID: " . $mergeid . " Merged with Ticket ID: " . $mastertid);
                                redir("action=viewticket&id=" . $mastertid);
                            } else {
                                if ($action == "openticket") {
                                    check_token("WHMCS.admin.default");
                                    $validate = new WHMCS\Validate();
                                    $validate->validate("required", "message", array("support", "ticketmessageerror"));
                                    $validate->validate("required", "subject", array("support", "ticketsubjecterror"));
                                    if (!$client) {
                                        if ($validate->validate("required", "email", array("support", "ticketemailerror"))) {
                                            $validate->validate("email", "email", array("support", "ticketemailvalidationerror"));
                                        }
                                        $validate->validate("required", "name", array("support", "ticketnameerror"));
                                    }
                                    if (!$validate->hasErrors()) {
                                        $validationData = array("clientId" => $client, "contactId" => $contactid, "name" => $name, "email" => $email, "isAdmin" => true, "departmentId" => $deptid, "subject" => $subject, "message" => $message, "priority" => $priority, "relatedService" => $relatedservice, "customfields" => array());
                                        $ticketOpenValidateResults = run_hook("TicketOpenValidation", $validationData);
                                        if (is_array($ticketOpenValidateResults)) {
                                            foreach ($ticketOpenValidateResults as $hookReturn) {
                                                if (is_string($hookReturn) && ($hookReturn = trim($hookReturn))) {
                                                    $validate->addError($hookReturn);
                                                }
                                            }
                                        }
                                    }
                                    if (!$validate->hasErrors()) {
                                        $attachments = uploadTicketAttachments(true);
                                        $client = (int) str_replace("UserID:", "", $client);
                                        $ticketdata = openNewTicket($client, $contactid, $deptid, $subject, $message, $priority, $attachments, array("name" => $name, "email" => $email), $relatedservice, $ccemail, $sendemail ? false : true, true, true);
                                        $id = $ticketdata["ID"];
                                        redir("action=viewticket&id=" . $id);
                                    } else {
                                        $action = "open";
                                    }
                                } else {
                                    if ($action == "viewticket" || $action == "view") {
                                        $access = validateAdminTicketAccess($id);
                                        if ($access == "invalidid") {
                                            $aInt->gracefulExit($aInt->lang("support", "ticketnotfound"));
                                        }
                                        if ($access == "deptblocked") {
                                            $aInt->gracefulExit($aInt->lang("support", "deptnoaccess"));
                                        }
                                        if ($access == "flagged") {
                                            $aInt->gracefulExit($aInt->lang("support", "flagnoaccess") . ": " . getAdminName(get_query_val("tbltickets", "flag", array("id" => $id))));
                                        }
                                        if (substr($access, 0, 6) == "merged" && substr($access, 6) != (int) $id) {
                                            redir("action=viewticket&id=" . (int) substr($access, 6));
                                        }
                                        if ($access) {
                                            $aInt->gracefulExit("Access Denied");
                                        }
                                        $ticket = new WHMCS\Tickets();
                                        $ticket->setID($id);
                                        $postreply = $whmcs->get_req_var("postreply");
                                        $postaction = $whmcs->get_req_var("postaction");
                                        $ticketWatcherIds = WHMCS\Ticket\Watchers::ofTicket($id)->pluck("admin_id")->all();
                                        $ticketWatchers = array();
                                        if ($ticketWatcherIds) {
                                            $adminList = WHMCS\User\Admin::whereIn("id", $ticketWatcherIds)->orderBy("firstname")->orderBy("lastname")->get();
                                            foreach ($adminList as $adminUser) {
                                                $ticketWatchers[$adminUser->id] = $adminUser->fullName;
                                            }
                                        }
                                        $smartyvalues["ticketWatchers"] = $ticketWatchers;
                                        $smartyvalues["ticketCc"] = array_filter(explode(",", $ticket->getData("cc")));
                                        if ($postreply || $postaction) {
                                            check_token("WHMCS.admin.default");
                                            $data = WHMCS\Database\Capsule::table("tbltickets")->find($id);
                                            if (is_null($data)) {
                                                throw new WHMCS\Exception\ProgramExit("Ticket ID not found");
                                            }
                                            $originalPriority = $data->urgency;
                                            $originalDepartmentId = $data->did;
                                            $originalFlag = $data->flag;
                                            $originalStatus = $data->status;
                                            $priority = $whmcs->get_req_var("priority");
                                            $deptid = $whmcs->get_req_var("deptid");
                                            $flagto = $whmcs->get_req_var("flagto");
                                            $status = $whmcs->get_req_var("status");
                                            $update = array();
                                            $changes = array();
                                            if ($status != "nochange") {
                                                $update["status"] = $status;
                                            }
                                            if ($priority != "nochange" && $originalPriority != $priority) {
                                                addTicketLog($id, "Priority changed to " . $priority);
                                                $update["urgency"] = $priority;
                                                $changes["Priority"] = array("old" => $originalPriority, "new" => $priority);
                                            }
                                            if ($deptid != "nochange" && $originalDepartmentId != $deptid) {
                                                migrateCustomFields("support", $id, $deptid);
                                                $ticket->changeDept($deptid);
                                                $changes["Department"] = array("old" => $ticket->getDeptName($originalDepartmentId), "new" => $ticket->getDeptName(), "newId" => $deptid);
                                            }
                                            if ($flagto != "nochange" && $originalFlag != $flagto) {
                                                $ticket->setFlagTo($flagto);
                                                $changes["Assigned To"] = array("old" => $originalFlag ? getAdminName($originalFlag) : "Unassigned", "oldId" => $originalFlag ?: 0, "new" => $flagto ? getAdminName($flagto) : "Unassigned", "newId" => $flagto ?: 0);
                                            }
                                            if (0 < count($update)) {
                                                WHMCS\Database\Capsule::table("tbltickets")->where("id", "=", $id)->update($update);
                                            }
                                            if ($postaction == "note") {
                                                $message = App::getFromRequest("message");
                                                $mentionedAdminIds = WHMCS\Mentions\Mentions::getIdsForMentions($message);
                                                AddNote($id, $message, true);
                                                $changes["Note"] = array("new" => $message, "editor" => "markdown");
                                                $newstatus = $status == "nochange" ? $originalStatus : $status;
                                                if ($newstatus != $originalStatus) {
                                                    WHMCS\Database\Capsule::table("tbltickets")->where("id", "=", $id)->update(array("status" => $newstatus));
                                                }
                                                $changes["Who"] = getAdminName(WHMCS\Session::get("adminid"));
                                                WHMCS\Tickets::notifyTicketChanges($id, $changes, array(), $mentionedAdminIds);
                                                if ($mentionedAdminIds) {
                                                    $ticketTid = $data->tid;
                                                    $ticketTitle = $data->title;
                                                    WHMCS\Mentions\Mentions::sendNotification("ticket", $id, $message, $mentionedAdminIds, AdminLang::trans("mention.ticket") . " #" . $ticketTid . " - " . $ticketTitle);
                                                }
                                            } else {
                                                $attachments = uploadTicketAttachments(true);
                                                $newstatus = $status == "nochange" ? "Answered" : $status;
                                                AddReply($id, "", "", $message, WHMCS\Session::get("adminid"), $attachments, "", $newstatus, false, false, true, $changes);
                                                if ($billingdescription && $billingdescription != $aInt->lang("support", "toinvoicedes")) {
                                                    checkPermission("Create Invoice");
                                                    $result = select_query("tbltickets", "", array("id" => $id));
                                                    $data = mysql_fetch_array($result);
                                                    $userid = $data["userid"];
                                                    $contactid = $data["contactid"];
                                                    $invoicenow = false;
                                                    if ($billingaction == "3") {
                                                        $invoicenow = true;
                                                        $billingaction = "1";
                                                    }
                                                    $billingamount = preg_replace("/[^0-9.]/", "", $billingamount);
                                                    insert_query("tblbillableitems", array("userid" => $userid, "description" => $billingdescription, "amount" => $billingamount, "recur" => 0, "recurcycle" => 0, "recurfor" => 0, "invoiceaction" => $billingaction, "duedate" => "now()"));
                                                    if ($invoicenow) {
                                                        require ROOTDIR . "/includes/clientfunctions.php";
                                                        require ROOTDIR . "/includes/processinvoices.php";
                                                        require ROOTDIR . "/includes/invoicefunctions.php";
                                                        createInvoices($userid);
                                                    }
                                                }
                                            }
                                            if ($newstatus != $originalStatus) {
                                                run_hook("TicketStatusChange", array("adminid" => $_SESSION["adminid"], "status" => $newstatus, "ticketid" => $id));
                                                if ($newstatus == "Closed") {
                                                    WHMCS\Database\Capsule::table("tbltickets")->where("id", "=", $id)->update(array("status" => "Answered"));
                                                    closeTicket($id);
                                                }
                                            }
                                            update_query("tbltickets", array("replyingadmin" => "", "replyingtime" => ""), array("id" => $id));
                                            WHMCS\Session::start();
                                            WHMCS\Session::set("ReturnToList", $whmcs->get_req_var("returntolist") ? true : false);
                                            WHMCS\Session::release();
                                            if ($whmcs->get_req_var("returntolist")) {
                                                $filters->redir();
                                            } else {
                                                redir("action=viewticket&id=" . $id);
                                            }
                                        }
                                        if ($deptid) {
                                            check_token("WHMCS.admin.default");
                                            $adminname = getAdminName();
                                            $result = select_query("tbltickets", "", array("id" => $id));
                                            $changes = array();
                                            $data = mysql_fetch_array($result);
                                            $orig_userid = $data["userid"];
                                            $orig_contactid = $data["contactid"];
                                            $orig_deptid = $data["did"];
                                            $orig_title = $data["title"];
                                            $orig_status = $data["status"];
                                            $orig_priority = $data["urgency"];
                                            $orig_flag = $data["flag"];
                                            $orig_cc = $data["cc"];
                                            if ($orig_title != $subject) {
                                                $changes["Subject"] = array("old" => $orig_title, "new" => $subject);
                                                addTicketLog($id, "Ticket subject changed from \"" . $orig_title . "\" to \"" . $subject . "\"");
                                                run_hook("TicketSubjectChange", array("ticketid" => $id, "subject" => $subject));
                                            }
                                            if ($orig_userid != $userid) {
                                                $changes["User ID"] = array("old" => $orig_userid ?: "No User", "new" => $userid ?: "No User");
                                                addTicketLog($id, "Ticket Assigned to User ID " . $userid);
                                            }
                                            if ($orig_deptid != $deptid) {
                                                $ticket = new WHMCS\Tickets();
                                                $ticket->setID($id);
                                                if ($ticket->changeDept($deptid)) {
                                                    $changes["Department"] = array("old" => $ticket->getDeptName($orig_deptid), "new" => $ticket->getDeptName(), "newId" => $deptid);
                                                }
                                            }
                                            if ($orig_status != $status) {
                                                if ($status == "Closed") {
                                                    closeTicket($id);
                                                } else {
                                                    addTicketLog($id, "Status changed to " . $status);
                                                }
                                                $changes["Status"] = array("old" => $orig_status, "new" => $status);
                                                run_hook("TicketStatusChange", array("adminid" => $_SESSION["adminid"], "status" => $status, "ticketid" => $id));
                                            }
                                            if ($orig_priority != $priority) {
                                                addTicketLog($id, "Priority changed to " . $priority);
                                                $changes["Priority"] = array("old" => $orig_priority, "new" => $priority);
                                                run_hook("TicketPriorityChange", array("ticketid" => $id, "priority" => $priority));
                                            }
                                            if ($orig_cc != $cc) {
                                                addTicketLog($id, "Modified CC Recipients");
                                                $changes["CC Recipients"] = array("old" => $orig_cc, "new" => $cc);
                                            }
                                            if ($orig_flag != $flagto) {
                                                $ticket = new WHMCS\Tickets();
                                                $ticket->setID($id);
                                                $ticket->setFlagTo($flagto);
                                                $changes["Assigned To"] = array("old" => $orig_flag ? getAdminName($orig_flag) : "Unassigned", "oldId" => $orig_flag ?: 0, "new" => $flagto ? getAdminName($flagto) : "Unassigned", "newId" => $flagto ?: 0);
                                            }
                                            $table = "tbltickets";
                                            $array = array("status" => $status, "urgency" => $priority, "title" => $subject, "userid" => $userid, "cc" => $cc);
                                            $where = array("id" => $id);
                                            update_query($table, $array, $where);
                                            if ($changes) {
                                                $changes["Who"] = getAdminName(WHMCS\Session::get("adminid"));
                                                WHMCS\Tickets::notifyTicketChanges($id, $changes);
                                            }
                                            if ($orig_status != "Closed" && $status == "Closed") {
                                                run_hook("TicketClose", array("ticketid" => $id));
                                            }
                                            if ($mergetid) {
                                                redir("action=mergeticket&id=" . $id . "&mergetid=" . $mergetid . generate_token("link"));
                                            }
                                            redir("action=viewticket&id=" . $id);
                                        }
                                        if ($removeattachment) {
                                            check_token("WHMCS.admin.default");
                                            $i = (int) $whmcs->get_req_var("filecount");
                                            $idsd = (int) $whmcs->get_req_var("idsd");
                                            $type = $whmcs->get_req_var("type");
                                            $field = "attachment";
                                            switch ($type) {
                                                case "n":
                                                    $table = "tblticketnotes";
                                                    $field = "attachments";
                                                    break;
                                                case "r":
                                                    $table = "tblticketreplies";
                                                    break;
                                                default:
                                                    $table = "tbltickets";
                                            }
                                            $attachments = WHMCS\Database\Capsule::table($table)->find($idsd, (array) $field);
                                            if ($attachments) {
                                                $attachments = $attachments->{$field};
                                            }
                                            $attachments = explode("|", $attachments);
                                            $filename = isset($attachments[$i]) ? $attachments[$i] : NULL;
                                            if (is_null($filename)) {
                                                $aInt->gracefulExit("Invalid attachment index requested for deletion");
                                            }
                                            try {
                                                Storage::ticketAttachments()->deleteAllowNotPresent($filename);
                                            } catch (Exception $e) {
                                                $aInt->gracefulExit("Could not delete file: " . htmlentities($e->getMessage()));
                                            }
                                            unset($attachments[$i]);
                                            WHMCS\Database\Capsule::table($table)->where("id", "=", $idsd)->update(array($field => implode("|", $attachments)));
                                            redir("action=viewticket&id=" . $id);
                                        }
                                        if ($sub == "del") {
                                            check_token("WHMCS.admin.default");
                                            checkPermission("Delete Ticket");
                                            deleteTicket($id, $idsd);
                                            redir("action=viewticket&id=" . $id);
                                        }
                                        if ($sub == "delnote") {
                                            check_token("WHMCS.admin.default");
                                            checkPermission("Delete Ticket");
                                            $noteAttachments = WHMCS\Database\Capsule::table("tblticketnotes")->find($idsd, array("attachments"));
                                            if (!is_null($noteAttachments) && $noteAttachments->attachments) {
                                                $noteAttachments = explode("|", $noteAttachments->attachments);
                                                foreach ($noteAttachments as $attachment) {
                                                    try {
                                                        Storage::ticketAttachments()->deleteAllowNotPresent($attachment);
                                                    } catch (Exception $e) {
                                                        $aInt->gracefulExit("Could not delete note attachment: " . htmlentities($e->getMessage()));
                                                    }
                                                }
                                            }
                                            delete_query("tblticketnotes", array("id" => $idsd));
                                            addTicketLog($id, "Deleted Ticket Note ID " . $idsd);
                                            redir("action=viewticket&id=" . $id);
                                        }
                                        if ($blocksender) {
                                            check_token("WHMCS.admin.default");
                                            $result = select_query("tbltickets", "userid,email", array("id" => $id));
                                            $data = get_query_vals("tbltickets", "userid,email", array("id" => $id));
                                            $userid = $data["userid"];
                                            $email = $data["email"];
                                            if ($userid) {
                                                $email = get_query_val("tblclients", "email", array("id" => $userid));
                                            }
                                            $blockedalready = get_query_val("tblticketspamfilters", "COUNT(*)", array("type" => "Sender", "content" => $email));
                                            if ($blockedalready) {
                                                redir("action=viewticket&id=" . $id . "&blockresult=2");
                                            } else {
                                                insert_query("tblticketspamfilters", array("type" => "Sender", "content" => $email));
                                                redir("action=viewticket&id=" . $id . "&blockresult=1&email=" . $email);
                                            }
                                        }
                                        if ($blockresult == "1") {
                                            infoBox($aInt->lang("support", "spamupdatesuccess"), sprintf($aInt->lang("support", "spamupdatesuccessinfo"), $email));
                                        }
                                        if ($blockresult == "2") {
                                            infoBox($aInt->lang("support", "spamupdatefailed"), $aInt->lang("support", "spamupdatefailedinfo"));
                                        }
                                    }
                                }
                            }
                        }
                        if ($autorefresh = $whmcs->get_req_var("autorefresh")) {
                            check_token("WHMCS.admin.default");
                            setcookie("WHMCSAutoRefresh", NULL, -86400);
                            WHMCS\Cookie::delete("AutoRefresh");
                            if (is_numeric($autorefresh)) {
                                WHMCS\Cookie::set("AutoRefresh", $autorefresh, time() + 90 * 24 * 60 * 60);
                            }
                            redir();
                        }
                        if ($action == "viewticket" || $action == "view") {
                            $result = select_query("tbltickets", "", array("id" => $id));
                            $data = mysql_fetch_array($result);
                            $replyingadmin = $data["replyingadmin"];
                            if (!$replyingadmin) {
                                $adminheaderbodyjs = "onunload=\"endMakingReply();\"";
                            }
                        }
                        $supportdepts = getAdminDepartmentAssignments();
                        ob_start();
                        $view = $filters->get("view");
                        $multiView = $filters->get("multi_view");
                        if (is_array($multiView) && count($multiView) == 1 && !$view) {
                            $view = $multiView[0];
                        }
                        $multiViewPost = is_array($multiView) && 0 < count($multiView);
                        $jumpToPageRequest = $whmcs->get_req_var("page");
                        if (!$multiView) {
                            $multiView = $multiViewPost && App::isInRequest("view") && !$jumpToPageRequest ? array("any") : array();
                        }
                        $deptid = $filters->get("deptid");
                        $multiDeptIds = $filters->get("multi_dept_id");
                        if (!$multiDeptIds) {
                            $multiDeptIds = array();
                        }
                        $priority = $filters->get("priority");
                        if (!$priority) {
                            $priority = array();
                        }
                        $client = $filters->get("client");
                        $clientid = $filters->get("clientid");
                        $clientname = $filters->get("clientname");
                        $subject = $filters->get("subject");
                        $email = $filters->get("email");
                        $tag = $filters->get("tag");
                        $smartyvalues["ticketfilterdata"] = array("view" => $view, "deptid" => $deptid, "subject" => $subject, "email" => $email);
                        if (!$action) {
                            WHMCS\Session::release();
                            $smartyvalues["inticketlist"] = true;
                            if (!count($supportdepts)) {
                                $aInt->gracefulExit($aInt->lang("permissions", "accessdenied") . " - " . $aInt->lang("support", "noticketdepts"));
                            }
                            $tickets = new WHMCS\Tickets();
                            $autorefresh = isset($_COOKIE["WHMCSAutoRefresh"]) ? (int) $_COOKIE["WHMCSAutoRefresh"] : 0;
                            if ($autorefresh && !$action) {
                                $refreshtime = $autorefresh * 60;
                                if ($refreshtime && !$disable_auto_ticket_refresh) {
                                    echo "<meta http-equiv=\"refresh\" content=\"" . $refreshtime . "\">";
                                }
                            }
                            echo $aInt->beginAdminTabs(array($aInt->lang("global", "searchfilter"), $aInt->lang("support", "autorefresh")));
                            echo "\n<form action=\"";
                            echo $whmcs->getPhpSelf();
                            echo "\" method=\"post\">\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n    <tr>\n        <td width=\"200\" class=\"fieldlabel\">";
                            echo AdminLang::trans("fields.client");
                            echo "</td>\n        <td class=\"fieldarea\">\n            ";
                            echo $aInt->clientsDropDown($client, false, "client", true);
                            echo "        </td>\n    </tr>\n    <tr>\n        <td class=\"fieldlabel\">\n            ";
                            echo $aInt->lang("support", "department");
                            echo "        </td>\n        <td class=\"fieldarea\">\n            <select name=\"multi_dept_id[]\" class=\"form-control selectize-multi-select\" multiple data-value-field=\"id\" placeholder=\"";
                            echo AdminLang::trans("global.any");
                            echo "\">\n                <option value=\"\">";
                            echo $aInt->lang("global", "any");
                            echo "</option>";
                            $allDefinedDepartments = WHMCS\Support\Department::orderBy("order", "asc")->get(array("id", "name"));
                            foreach ($allDefinedDepartments as $dept) {
                                $id = $dept->id;
                                $name = $dept->name;
                                if (in_array($id, $supportdepts)) {
                                    echo "<option value=\"" . $id . "\"";
                                    if ($multiDeptIds && in_array($id, $multiDeptIds) || $id == $deptid) {
                                        echo " selected";
                                    }
                                    echo ">" . $name . "</option>";
                                }
                            }
                            echo "            </select>\n        </td>\n    </tr>\n    <tr>\n        <td class=\"fieldlabel\">\n            ";
                            echo $aInt->lang("fields", "status");
                            echo "        </td>\n        <td class=\"fieldarea\">\n            <select id=\"multi-view\" name=\"multi_view[]\" class=\"form-control selectize-multi-select\" multiple=\"multiple\" data-value-field=\"id\" placeholder=\"";
                            echo AdminLang::trans("support.anyStatus");
                            echo "\">\n                <option value=\"any\"";
                            echo $view == "any" || is_array($multiView) && in_array("any", $multiView) ? " selected" : "";
                            echo ">\n                    ";
                            echo AdminLang::trans("support.anyStatus");
                            echo "                </option>\n                <option value=\"flagged\"";
                            echo !$view && !$multiView || $view == "flagged" || is_array($multiView) && in_array("flagged", $multiView) ? " selected" : "";
                            echo ">\n                    ";
                            echo $aInt->lang("support", "flagged");
                            echo "                </option>\n                ";
                            $result = select_query("tblticketstatuses", "", "", "sortorder", "ASC");
                            while ($data = mysql_fetch_array($result)) {
                                echo "<option value=\"" . $data["title"] . "\"";
                                if ($view == $data["title"] || in_array("any", $multiView) || in_array($data["title"], $multiView) || !$multiView && ($view == "any" || $view == "" && $data["showawaiting"] || $view == "active" && $data["showactive"])) {
                                    echo " selected";
                                }
                                echo ">" . $data["title"] . "</option>";
                            }
                            echo "            </select>\n        </td>\n    </tr>\n    <tr>\n        <td class=\"fieldlabel\">\n            ";
                            echo AdminLang::trans("support.priority");
                            echo "        </td>\n        <td class=\"fieldarea\">\n            <select name=\"priority[]\" class=\"form-control selectize-multi-select\" multiple data-value-field=\"id\" placeholder=\"";
                            echo AdminLang::trans("global.any");
                            echo "\">\n                <option value=\"\">";
                            echo AdminLang::trans("global.any");
                            echo "</option>\n                <option value=\"Low\"";
                            echo in_array("Low", $priority) ? " selected" : "";
                            echo ">\n                    ";
                            echo AdminLang::trans("status.low");
                            echo "                </option>\n                <option value=\"Medium\"";
                            echo in_array("Medium", $priority) ? " selected" : "";
                            echo ">\n                    ";
                            echo AdminLang::trans("status.medium");
                            echo "                </option>\n                <option value=\"High\"";
                            echo in_array("High", $priority) ? " selected" : "";
                            echo ">\n                    ";
                            echo AdminLang::trans("status.high");
                            echo "                </option>\n            </select>\n        </td>\n    </tr>\n    <tr>\n        <td class=\"fieldlabel\">\n            ";
                            echo AdminLang::trans("support.subjectmessage");
                            echo "        </td>\n        <td class=\"fieldarea\">\n            <input type=\"text\" name=\"subject\" size=\"40\" value=\"";
                            echo $subject;
                            echo "\" class=\"form-control\" />\n        </td>\n    </tr>\n    <tr>\n        <td class=\"fieldlabel\">\n            ";
                            echo $aInt->lang("fields", "email");
                            echo "        </td>\n        <td class=\"fieldarea\">\n            <input type=\"text\" name=\"email\" size=\"40\" value=\"";
                            echo $email;
                            echo "\" class=\"form-control input-700\" />\n        </td>\n    </tr>\n    <tr>\n        <td class=\"fieldlabel\">\n            ";
                            echo $aInt->lang("support", "ticketid");
                            echo "        </td>\n        <td class=\"fieldarea\">\n            <input type=\"text\" name=\"ticketid\" size=\"15\" class=\"form-control input-250\" />\n        </td>\n    </tr>\n</table>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
                            echo $aInt->lang("global", "searchfilter");
                            echo "\" class=\"btn btn-primary\" />\n</div>\n\n</form>\n\n";
                            echo $aInt->nextAdminTab();
                            echo "\n<form action=\"";
                            echo $whmcs->getPhpSelf();
                            echo "\" method=\"post\">\n<div align=\"center\">";
                            echo $aInt->lang("support", "autorefreshevery");
                            echo " <select name=\"autorefresh\" class=\"form-control select-inline\"><option>Never</option>\n";
                            $times = array(1, 2, 5, 10, 15);
                            foreach ($times as $time) {
                                echo "<option value=\"" . $time . "\"";
                                if ($time == $autorefresh) {
                                    echo " selected";
                                }
                                echo ">" . $time . " " . $aInt->lang("support", "minute" . (1 < $time ? "s" : "")) . "</option>";
                            }
                            echo "</select> <input type=\"submit\" value=\"";
                            echo $aInt->lang("support", "setautorefresh");
                            echo "\" class=\"btn btn-primary btn-sm\" /></div>\n</form>\n\n";
                            echo $aInt->endAdminTabs();
                            echo "\n<br />\n\n";
                            if ($actionresult) {
                                switch ($actionresult) {
                                    case "blockdeleteticketsfailed":
                                    case "closeticketsfailed":
                                    case "deleteticketsfailed":
                                    case "mergeticketsfailed":
                                        infoBox($aInt->lang("global", "erroroccurred"), $aInt->lang("support", $actionresult), "error");
                                        break;
                                    case "blockdeleteticketssuccess":
                                    case "closeticketssuccess":
                                    case "deleteticketssuccess":
                                    case "mergeticketssuccess":
                                        infoBox($aInt->lang("global", "success"), $aInt->lang("support", $actionresult), "success");
                                        break;
                                    case "blockdeleteticketsdenied":
                                    case "closeticketsdenied":
                                    case "deleteticketsdenied":
                                    case "mergeticketsdenied":
                                        infoBox(AdminLang::trans("permissions.accessdenied"), AdminLang::trans("permissions.nopermission"), "error");
                                        break;
                                }
                                echo $infobox;
                            }
                            $htmlOutput = run_hook("AdminSupportTicketPagePreTickets", array());
                            foreach ($htmlOutput as $output) {
                                if (!empty($output)) {
                                    echo $output;
                                }
                            }
                            $tag = $whmcs->get_req_var("tag");
                            if ($tag) {
                                echo "<h2>Filtering Tickets for Tag <strong>\"" . $tag . "\"</strong></h2>";
                            }
                            $selectors = "input[name='merge'],input[name='close'],input[name='delete'],input[name='blockdelete']";
                            $jqueryCode = "\$(\"" . $selectors . "\").on('click', function( event ) {\n    event.preventDefault();\n    var selectedItems = \$(\"input[name='selectedtickets[]']\");\n    var name = \$(this).attr('name');\n    switch(name) {\n        case 'merge':\n            var langConfirm = '" . $aInt->lang("support", "massmergeconfirm", "1") . "';\n            break;\n        case 'close':\n            var langConfirm = '" . $aInt->lang("support", "masscloseconfirm", "1") . "';\n            break;\n        case 'delete':\n            var langConfirm = '" . $aInt->lang("support", "massdeleteconfirm", "1") . "';\n            break;\n        case 'blockdelete':\n            var langConfirm = '" . $aInt->lang("support", "massblockdeleteconfirm", "1") . "';\n            break;\n    }\n    if (selectedItems.filter(':checked').length == 0) {\n        alert('" . $aInt->lang("global", "pleaseSelectForMassAction", "1") . "');\n    } else {\n        if (confirm(langConfirm)) {\n            ticketMassAction(name + 'tickets');\n        }\n    }\n});";
                            $mButton = $aInt->lang("clientsummary", "merge");
                            $cButton = $aInt->lang("global", "close");
                            $massactionbtns = "<input type=\"submit\" value=\"" . $mButton . "\" name=\"merge\" class=\"btn btn-default btn-xs\" />\n <input type=\"submit\" value=\"" . $cButton . "\" name=\"close\" class=\"btn btn-default btn-xs\" />";
                            if ($aInt->hasPermission("Delete Ticket")) {
                                $dButton = $aInt->lang("global", "delete");
                                $bdButton = $aInt->lang("support", "blockanddelete");
                                $massactionbtns .= " <input type=\"submit\" value=\"" . $dButton . "\" name=\"delete\" class=\"btn btn-danger btn-xs\" />\n <input type=\"submit\" value=\"" . $bdButton . "\" name=\"blockdelete\" class=\"btn btn-danger btn-xs\" />";
                            }
                            $name = "tickets";
                            $orderby = "lastreply";
                            $sort = "DESC";
                            $pageObj = new WHMCS\Pagination($name, $orderby, $sort);
                            $pageObj->digestCookieData();
                            $pageObj->setPagination(false);
                            $filters->store();
                            $tbl = new WHMCS\ListTable($pageObj, 1, $aInt);
                            $tbl->setColumns(array("checkall", "", array("deptname", $aInt->lang("support", "department")), array("title", $aInt->lang("fields", "subject")), $aInt->lang("support", "submitter"), array("status", $aInt->lang("fields", "status")), array("lastreply", $aInt->lang("support", "lastreply"))));
                            $activeAwaitingTicketStatuses = WHMCS\Database\Capsule::table("tblticketstatuses")->where("showactive", "1")->orWhere("showawaiting", "1")->pluck("title");
                            $activeAwaitingTicketStatuses[] = "active";
                            $ticketlist = $taggedTickets = array();
                            $notFlaggedTo = 0;
                            if (!$tag && (!$view || in_array($view, $activeAwaitingTicketStatuses))) {
                                $ticketsModel = new WHMCS\Tickets($pageObj);
                                $criteria = array("status" => $view, "multiStatus" => $multiView, "deptid" => $deptid, "multiDeptIds" => $multiDeptIds, "priority" => $priority, "subject" => $subject, "tag" => $tag, "client" => $client, "clientid" => $clientid, "clientname" => $clientname, "email" => $email, "flag" => WHMCS\Auth::getID());
                                $ticketsModel->execute($criteria);
                                $ticketslist = $pageObj->getData();
                                $notFlaggedTo = WHMCS\Auth::getID();
                                $taggedTickets = $ticketsModel->getTagTicketIds();
                            }
                            if (count($ticketslist)) {
                                foreach ($ticketslist as $ticket) {
                                    $tbl->addRow(array("<input type=\"checkbox\" name=\"selectedtickets[]\" value=\"" . $ticket["id"] . "\" class=\"checkall\" />", "<img src=\"images/" . strtolower($ticket["priority"]) . "priority.gif\" width=\"16\" height=\"16\" alt=\"" . $ticket["priority"] . "\" class=\"absmiddle\" />", $ticket["department"], "<a href=\"supporttickets.php?action=view&id=" . $ticket["id"] . "\"" . ($ticket["unread"] ? " style=\"font-weight:bold;\"" : "") . " title=\"" . $ticket["textsummary"] . "\">#" . $ticket["ticketnum"] . " - " . $ticket["subject"] . "</a>", $ticket["clientname"], $ticket["status"], $ticket["lastreply"]));
                                }
                                $tbl->setPagination(false);
                                $tbl->setMassActionBtns($massactionbtns);
                                echo "<h2>" . $aInt->lang("support", "assignedtickets") . "</h2><p>" . sprintf($aInt->lang("support", "numticketsassigned"), $pageObj->getNumResults()) . "</p>" . $tbl->output() . "<br /><h2>" . $aInt->lang("support", "unassignedtickets") . "</h2>";
                            }
                            unset($ticketslist);
                            unset($ticketsModel);
                            $name = "tickets";
                            $orderby = "lastreply";
                            $sort = "DESC";
                            $pageObj = new WHMCS\Pagination($name, $orderby, $sort);
                            $pageObj->digestCookieData();
                            $tbl = new WHMCS\ListTable($pageObj, 2, $aInt);
                            $tbl->setColumns(array("checkall", "", array("deptname", $aInt->lang("support", "department")), array("title", $aInt->lang("fields", "subject")), $aInt->lang("support", "submitter"), array("status", $aInt->lang("fields", "status")), array("lastreply", $aInt->lang("support", "lastreply"))));
                            $ticketsModel = new WHMCS\Tickets($pageObj);
                            $criteria = array("status" => $view, "multiStatus" => $multiView, "deptid" => $deptid, "multiDeptIds" => $multiDeptIds, "priority" => $priority, "subject" => $subject, "tag" => $tag, "client" => $client, "clientid" => $clientid, "clientname" => $clientname, "email" => $email, "notflaggedto" => $notFlaggedTo, "tag_ticket_ids" => $taggedTickets);
                            $ticketsModel->execute($criteria);
                            $ticketslist = $pageObj->getData();
                            foreach ($ticketslist as $ticket) {
                                $tbl->addRow(array("<input type=\"checkbox\" name=\"selectedtickets[]\" value=\"" . $ticket["id"] . "\" class=\"checkall\" />", "<img src=\"images/" . strtolower($ticket["priority"]) . "priority.gif\" width=\"16\" height=\"16\" alt=\"" . $ticket["priority"] . "\" class=\"absmiddle\" />", $ticket["department"], "<a href=\"supporttickets.php?action=view&id=" . $ticket["id"] . "\"" . ($ticket["unread"] ? " style=\"font-weight:bold;\"" : "") . " title=\"" . $ticket["textsummary"] . "\">#" . $ticket["ticketnum"] . " - " . $ticket["subject"] . "</a>", $ticket["clientname"], $ticket["status"], $ticket["lastreply"]));
                            }
                            $tbl->setMassActionBtns($massactionbtns);
                            $tbl->setShowMassActionBtnsTop(true);
                            echo $tbl->output();
                            $smartyvalues["tagcloud"] = $ticketsModel->buildTagCloud();
                            unset($ticketslist);
                            unset($ticketsModel);
                            $jscode .= "\nfunction ticketMassAction(action) {\n    var selectedTickets = [];\n    \$(\"input:checkbox[name='selectedtickets[]']:checked\").each(function(){\n        selectedTickets.push(parseInt(\$(this).val()));\n    });\n    WHMCS.http.jqClient.post(\n        \"supporttickets.php\",\n        { action: action,\n          'selectedTickets[]': selectedTickets,\n          token: \"" . generate_token("plain") . "\"\n        },\n        function (data) {\n            if (data=='1') {\n                window.location='" . $whmcs->getPhpSelf() . "?actionresult='+action+'success&filter=1'\n            } else if (data=='denied') {\n                window.location='" . $whmcs->getPhpSelf() . "?actionresult='+action+'denied&filter=1'\n            } else {\n                window.location='" . $whmcs->getPhpSelf() . "?actionresult='+action+'failed&filter=1'\n            }\n        }\n    );\n}\n";
                            $aInt->jquerycode = $jqueryCode;
                        }
                        if ($action == "search") {
                            $where = "tid='" . db_escape_string($ticketid) . "' AND did IN (" . db_build_in_array(db_escape_numarray(getAdminDepartmentAssignments())) . ")";
                            $result = select_query("tbltickets", "", $where);
                            $data = mysql_fetch_array($result);
                            $id = $data["id"];
                            if (!$id) {
                                echo "<p>" . $aInt->lang("support", "ticketnotfound") . "  <a href=\"javascript:history.go(-1)\">" . $aInt->lang("support", "pleasetryagain") . "</a>.</p>";
                            } else {
                                $action = "viewticket";
                            }
                        }
                        if ($action == "viewticket" || $action == "view") {
                            WHMCS\Session::release();
                            $smartyvalues["ticketfilterdata"] = array("view" => $filters->getFromSession("view"), "deptid" => $filters->getFromSession("deptid"), "subject" => $filters->getFromSession("subject"), "email" => $filters->getFromSession("email"));
                            $ticket = new WHMCS\Tickets();
                            $ticket->setID($id);
                            $data = $ticket->getData();
                            $id = $data["id"];
                            $tid = $data["tid"];
                            $deptid = $data["did"];
                            $pauserid = $data["userid"];
                            $pacontactid = $data["contactid"];
                            $name = $data["name"];
                            $email = $data["email"];
                            $cc = $data["cc"];
                            $date = $data["date"];
                            $title = $data["title"];
                            $message = $data["message"];
                            $tstatus = $data["status"];
                            $admin = $data["admin"];
                            $attachment = $data["attachment"];
                            $urgency = $data["urgency"];
                            $lastreply = $data["lastreply"];
                            $flag = $data["flag"];
                            $replyingadmin = $data["replyingadmin"];
                            $replyingtime = $data["replyingtime"];
                            $service = $data["service"];
                            $replyingtime = fromMySQLDate($replyingtime, "time");
                            $watchers = $data["watchers"];
                            $access = validateAdminTicketAccess($id);
                            if ($access == "invalidid") {
                                $aInt->gracefulExit($aInt->lang("support", "ticketnotfound"));
                            }
                            if ($access == "deptblocked") {
                                $aInt->gracefulExit($aInt->lang("support", "deptnoaccess"));
                            }
                            if ($access == "flagged") {
                                $aInt->gracefulExit($aInt->lang("support", "flagnoaccess") . ": " . getAdminName($flag));
                            }
                            if ($access) {
                                exit;
                            }
                            if (0 < $pauserid) {
                                $aInt->assertClientBoundary($pauserid);
                            }
                            $aInt->template = "viewticket";
                            $smartyvalues["inticket"] = true;
                            $smartyvalues["returnToList"] = WHMCS\Session::exists("ReturnToList") ? WHMCS\Session::get("ReturnToList") : true;
                            $updateticket = App::getFromRequest("updateticket");
                            if ($updateticket) {
                                check_token("WHMCS.admin.default");
                                $ticketId = (int) App::getFromRequest("id");
                                $value = App::getFromRequest("value");
                                $skip = (bool) (int) App::getFromRequest("skip");
                                $changes = false;
                                $changeList = array();
                                if (!$skip) {
                                    $changeList = checkTicketChanges($ticketId);
                                    $changes = 0 < count($changeList);
                                }
                                if (!$changes && ($value || $updateticket == "flagto")) {
                                    $currentValue = App::getFromRequest("currentValue");
                                    switch ($updateticket) {
                                        case "deptid":
                                            if ($value == $deptid) {
                                                break;
                                            }
                                            $ticket->changeDept($value);
                                            WHMCS\Tickets::notifyTicketChanges($id, array("Department" => array("old" => $ticket->getDeptName($deptid), "new" => $ticket->getDeptName($value), "newId" => $value), "Who" => getAdminName(WHMCS\Session::get("adminid"))));
                                            break;
                                        case "flagto":
                                            if ($value == $flag) {
                                                break;
                                            }
                                            $ticket->setFlagTo($value);
                                            WHMCS\Tickets::notifyTicketChanges($id, array("Flag" => array("old" => $flag ? getAdminName($flag) : "Unassigned", "new" => $value ? getAdminName($value) : "Unassigned"), "Who" => getAdminName(WHMCS\Session::get("adminid"))));
                                            break;
                                        case "priority":
                                            if (!in_array($value, array("High", "Medium", "Low")) || $value == $urgency) {
                                                break;
                                            }
                                            $ticket->setPriority($value);
                                            WHMCS\Tickets::notifyTicketChanges($id, array("Priority" => array("old" => $urgency, "new" => $value), "Who" => getAdminName(WHMCS\Session::get("adminid"))));
                                            break;
                                    }
                                }
                                $response = array("changes" => $changes, "changeList" => implode("\r\n", $changeList));
                                $aInt->setBodyContent($response);
                                $aInt->display();
                                WHMCS\Terminus::getInstance()->doExit();
                            }
                            if ($sub == "savecustomfields") {
                                check_token("WHMCS.admin.default");
                                $customfields = getCustomFields("support", $deptid, $id, true);
                                foreach ($customfields as $v) {
                                    $k = $v["id"];
                                    $customfieldsarray[$k] = $customfield[$k];
                                }
                                saveCustomFields($id, $customfieldsarray, "support", true);
                                $adminname = getAdminName();
                                addTicketLog($id, "Custom Field Values Modified by " . $adminname);
                                redir("action=viewticket&id=" . $id);
                            }
                            AdminRead($id);
                            if ($replyingadmin && $replyingadmin != $_SESSION["adminid"]) {
                                $result = select_query("tbladmins", "", array("id" => $replyingadmin));
                                $data = mysql_fetch_array($result);
                                $replyingadmin = ucfirst($data["username"]);
                                $smartyvalues["replyingadmin"] = array("name" => $replyingadmin, "time" => $replyingtime);
                            }
                            $smartyvalues["watchingTicket"] = false;
                            if (in_array(WHMCS\Session::get("adminid"), $data["watchers"])) {
                                $smartyvalues["watchingTicket"] = true;
                            }
                            $smartyvalues["watchers"] = $data["watchers"];
                            $clientname = $contactname = $clientGroupColour = "";
                            if ($pauserid) {
                                $clientname = strip_tags($aInt->outputClientLink($pauserid));
                            }
                            if ($pacontactid) {
                                $contactname = strip_tags($aInt->outputClientLink(array($pauserid, $pacontactid)));
                            }
                            if ($groupid) {
                                $clientGroups = getClientGroups();
                                $clientGroupColour = $clientGroups[$groupid]["colour"];
                            }
                            $staffinvolved = array();
                            $result = select_query("tblticketreplies", "DISTINCT admin", array("tid" => $id));
                            while ($data = mysql_fetch_array($result)) {
                                if (trim($data[0])) {
                                    $staffinvolved[] = $data[0];
                                }
                            }
                            $addons_html = run_hook("AdminAreaViewTicketPage", array("ticketid" => $id));
                            $smartyvalues["addons_html"] = $addons_html;
                            $addons_html = run_hook("AdminAreaViewTicketPageSidebar", array("ticketid" => $id));
                            $smartyvalues["sidebaroutput"] = $addons_html;
                            $department = getDepartmentName($deptid);
                            if (!$lastreply) {
                                $lastreply = $date;
                            }
                            $date = fromMySQLDate($date, true);
                            $outstatus = getStatusColour($tstatus);
                            $aInt->addHeadOutput(WHMCS\View\Asset::jsInclude("bootstrap-tabdrop.js"));
                            $aInt->addHeadOutput(WHMCS\View\Asset::cssInclude("tabdrop.css"));
                            $aInt->addInternalJQueryCode("\$(\".admin-tabs\").tabdrop();");
                            $tags = array();
                            $result = select_query("tbltickettags", "tag", array("ticketid" => $id), "tag", "ASC");
                            while ($data = mysql_fetch_array($result)) {
                                $tags[] = $data["tag"];
                            }
                            $smartyvalues["tags"] = $tags;
                            $csrfToken = generate_token("plain");
                            $jsheadoutput = "<script type=\"text/javascript\">\nvar ticketid = '" . $id . "';\nvar userid = '" . $pauserid . "';\nvar langdelreplysure = \"" . $_ADMINLANG["support"]["delreplysure"] . "\";\nvar langdelticketsure = \"" . $_ADMINLANG["support"]["delticketsure"] . "\";\nvar langdelnotesure = \"" . $_ADMINLANG["support"]["delnotesure"] . "\";\nvar langloading = \"" . $_ADMINLANG["global"]["loading"] . "\";\nvar watch_ticket = \"" . $_ADMINLANG["support"]["watchTicket"] . "\";\nvar unwatch_ticket = \"" . $_ADMINLANG["support"]["unwatchTicket"] . "\",\n    changes = \"" . $_ADMINLANG["support"]["ticketChanges"] . "\",\n    changesTitle = \"" . $_ADMINLANG["support"]["ticketChangesTitle"] . "\",\n    continueText = \"" . $_ADMINLANG["global"]["continue"] . "\"\n</script>";
                            $jsheadoutput .= WHMCS\View\Asset::jsInclude("AdminTicketInterface.js?v=" . WHMCS\View\Helper::getAssetVersionHash());
                            $aInt->addHeadOutput($jsheadoutput);
                            $smartyvalues["infobox"] = $infobox;
                            $smartyvalues["ticketid"] = $id;
                            $smartyvalues["deptid"] = $deptid;
                            $smartyvalues["tid"] = $tid;
                            $smartyvalues["subject"] = $title;
                            $smartyvalues["status"] = $tstatus;
                            $smartyvalues["userid"] = $pauserid;
                            $smartyvalues["userSearchDropdown"] = $aInt->clientSearchDropdown("userid", $pauserid ?: "", $pauserid ? array($pauserid => $clientname) : array(), AdminLang::trans("global.typeToSearchClients"));
                            $smartyvalues["contactid"] = $pacontactid;
                            $smartyvalues["clientname"] = $clientname;
                            $smartyvalues["contactname"] = $contactname;
                            $smartyvalues["clientgroupcolour"] = $clientGroupColour;
                            $smartyvalues["lastreply"] = getLastReplyTime($lastreply);
                            $smartyvalues["priority"] = $urgency;
                            $smartyvalues["flag"] = $flag;
                            $smartyvalues["cc"] = $cc;
                            $smartyvalues["staffinvolved"] = $staffinvolved;
                            $smartyvalues["name"] = $name;
                            $smartyvalues["email"] = $email;
                            $smartyvalues["deleteperm"] = checkPermission("Delete Ticket", true);
                            $result = select_query("tbladmins", "firstname,lastname,signature", array("id" => $_SESSION["adminid"]));
                            $data = mysql_fetch_array($result);
                            $signature = $data["signature"];
                            $smartyvalues["signature"] = $signature;
                            $smartyvalues["predefinedreplies"] = genPredefinedRepliesList(0);
                            $markup = new WHMCS\View\Markup\Markup();
                            $smartyvalues["clientnotes"] = array();
                            $result = select_query("tblnotes", "tblnotes.*,(SELECT CONCAT(firstname,' ',lastname) FROM tbladmins WHERE tbladmins.id=tblnotes.adminid) AS adminuser", array("userid" => $pauserid, "sticky" => "1"), "modified", "DESC");
                            while ($data = mysql_fetch_assoc($result)) {
                                $markupFormat = $markup->determineMarkupEditor("client_note", "client_note", $data["modified"]);
                                $mentions = WHMCS\Mentions\Mentions::getMentionReplacements($data["note"]);
                                if (0 < count($mentions)) {
                                    $data["note"] = str_replace($mentions["find"], $mentions["replace"], $data["note"]);
                                }
                                $data["note"] = $markup->transform($data["note"], $markupFormat);
                                $data["created"] = fromMySQLDate($data["created"], 1);
                                $data["modified"] = fromMySQLDate($data["modified"], 1);
                                $smartyvalues["clientnotes"][] = $data;
                            }
                            $customfields = getCustomFields("support", $deptid, $id, true);
                            $smartyvalues["customfields"] = $customfields;
                            $smartyvalues["numcustomfields"] = count($customfields);
                            $departmentshtml = "";
                            $departments = array();
                            $result = select_query("tblticketdepartments", "", "", "order", "ASC");
                            while ($data = mysql_fetch_array($result)) {
                                $departments[] = array("id" => $data["id"], "name" => $data["name"]);
                                $departmentshtml .= "<option value=\"" . $data["id"] . "\"" . ($data["id"] == $deptid ? " selected" : "") . ">" . $data["name"] . "</option>";
                            }
                            $smartyvalues["departments"] = $departments;
                            $staff = array();
                            $result = select_query("tbladmins", "id,firstname,lastname,supportdepts", "disabled=0 OR id='" . (int) $flag . "'", "firstname` ASC,`lastname", "ASC");
                            while ($data = mysql_fetch_array($result)) {
                                $staff[] = array("id" => $data["id"], "name" => $data["firstname"] . " " . $data["lastname"]);
                            }
                            $smartyvalues["staff"] = $staff;
                            $statuses = array();
                            $result = select_query("tblticketstatuses", "", "", "sortorder", "ASC");
                            while ($data = mysql_fetch_array($result)) {
                                $statuses[] = array("title" => $data["title"], "color" => $data["color"], "id" => $data["id"]);
                            }
                            $smartyvalues["statuses"] = $statuses;
                            if ($service) {
                                switch (substr($service, 0, 1)) {
                                    case "S":
                                        $result = select_query("tblhosting", "tblhosting.id,tblhosting.userid,tblhosting.regdate,tblhosting.domain,tblhosting.domainstatus,tblhosting.nextduedate,tblhosting.billingcycle,tblproducts.name,tblhosting.username,tblhosting.password,tblproducts.servertype", array("tblhosting.id" => substr($service, 1)), "", "", "", "tblproducts ON tblproducts.id=tblhosting.packageid");
                                        $data = mysql_fetch_array($result);
                                        $service_id = $data["id"];
                                        $service_userid = $data["userid"];
                                        $service_name = $data["name"];
                                        $service_domain = $data["domain"];
                                        $service_status = $data["domainstatus"];
                                        $service_regdate = $data["regdate"];
                                        if (in_array($data["billingcycle"], array("One Time", "Free Account"))) {
                                            $service_nextduedate = "-";
                                        } else {
                                            $service_nextduedate = fromMySQLDate($data["nextduedate"]);
                                        }
                                        $service_username = $data["username"];
                                        $service_password = decrypt($data["password"]);
                                        $service_servertype = $data["servertype"];
                                        if ($service_servertype) {
                                            if (!isValidforPath($service_servertype)) {
                                                exit("Invalid Server Module Name");
                                            }
                                            include "../modules/servers/" . $service_servertype . "/" . $service_servertype . ".php";
                                            if (function_exists($service_servertype . "_LoginLink")) {
                                                ob_start();
                                                ServerLoginLink($service_id);
                                                $service_loginlink = ob_get_contents();
                                                ob_end_clean();
                                            }
                                        }
                                        $smartyvalues["relatedproduct"] = array("id" => $service_id, "name" => $service_name, "regdate" => fromMySQLDate($service_regdate), "domain" => $service_domain, "nextduedate" => $service_nextduedate, "username" => $service_username, "password" => $service_password, "loginlink" => $service_loginlink, "status" => $service_status);
                                        break;
                                    case "D":
                                        $result = select_query("tbldomains", "", array("id" => substr($service, 1)));
                                        $data = mysql_fetch_array($result);
                                        $service_id = $data["id"];
                                        $service_userid = $data["userid"];
                                        $service_type = $data["type"];
                                        $service_domain = $data["domain"];
                                        $service_status = $data["status"];
                                        $service_nextduedate = $data["nextduedate"];
                                        $service_regperiod = $data["registrationperiod"];
                                        $service_registrar = $data["registrar"];
                                        $smartyvalues["relateddomain"] = array("id" => $service_id, "domain" => $service_domain, "nextduedate" => fromMySQLDate($service_nextduedate), "registrar" => ucfirst($service_registrar), "regperiod" => $service_regperiod, "ordertype" => $service_type, "status" => $service_status);
                                        break;
                                }
                            }
                            if ($pauserid && checkPermission("List Services", true)) {
                                $selectedRelatedId = 0;
                                $selectedRelatedType = "";
                                if ($service) {
                                    $selectedRelatedType = substr($service, 0, 1);
                                    $selectedRelatedId = substr($service, 1);
                                }
                                $currency = getCurrency($pauserid);
                                $smartyvalues["relatedservices"] = array();
                                $totalitems = get_query_val("tblhosting", "COUNT(id)", array("userid" => $pauserid)) + get_query_val("tblhostingaddons", "COUNT(tblhostingaddons.id)", array("tblhosting.userid" => $pauserid), "", "", "", "tblhosting ON tblhosting.id=tblhostingaddons.hostingid") + get_query_val("tbldomains", "COUNT(id)", array("userid" => $pauserid));
                                $lefttoselect = 10;
                                $relatedIndex = 0;
                                $result = WHMCS\Database\Capsule::table("tblhosting")->join("tblproducts", "tblproducts.id", "=", "tblhosting.packageid")->where("userid", $pauserid)->limit($lefttoselect)->offset(0)->orderBy("tblhosting.domainstatus")->orderBy("tblhosting.id", "desc")->select(array("tblhosting.*", "tblproducts.name"));
                                if ($selectedRelatedType == "S") {
                                    $result2 = WHMCS\Database\Capsule::table("tblhosting")->join("tblproducts", "tblproducts.id", "=", "tblhosting.packageid")->where("userid", $pauserid)->where("tblhosting.id", $selectedRelatedId)->select(array("tblhosting.*", "tblproducts.name"));
                                    $result->union($result2);
                                }
                                foreach ($result->get() as $data) {
                                    $data = (array) $data;
                                    $service_id = $data["id"];
                                    $service_name = $data["name"];
                                    $service_domain = $data["domain"];
                                    $service_firstpaymentamount = $data["firstpaymentamount"];
                                    $service_recurringamount = $data["amount"];
                                    $service_billingcycle = $data["billingcycle"];
                                    $service_signupdate = $data["regdate"];
                                    $service_status = $data["domainstatus"];
                                    $service_signupdate = fromMySQLDate($service_signupdate);
                                    switch ($service_billingcycle) {
                                        case "Free":
                                        case "Free Account":
                                            $service_nextduedate = "-";
                                            $service_amount = formatCurrency("0.00");
                                            break;
                                        case "One Time":
                                            $service_nextduedate = "-";
                                            $service_amount = formatCurrency($service_firstpaymentamount);
                                            break;
                                        default:
                                            $service_nextduedate = fromMySQLDate($data["nextduedate"]);
                                            $service_amount = formatCurrency($service_recurringamount);
                                    }
                                    $selected = false;
                                    $index = $relatedIndex;
                                    if ($selectedRelatedType == "S" && $selectedRelatedId == $service_id) {
                                        $selected = true;
                                        $index = -1;
                                    }
                                    $href = "clientsservices.php?userid=" . $pauserid . "&id=" . $service_id;
                                    $name = "<a href=\"" . $href . "\" target=\"_blank\">" . $service_name . "</a>" . " - <a href=\"http://" . $service_domain . "\" target=\"_blank\">" . $service_domain . "</a>";
                                    $smartyvalues["relatedservices"][$index] = array("id" => $service_id, "type" => "product", "name" => $name, "product" => $service_name, "domain" => $service_domain, "amount" => $service_amount, "billingcycle" => $service_billingcycle, "regdate" => $service_signupdate, "nextduedate" => $service_nextduedate, "status" => $service_status, "selected" => $selected);
                                    $relatedIndex++;
                                }
                                $lefttoselect = 10 - count($smartyvalues["relatedservices"]);
                                if (0 < $lefttoselect || $selectedRelatedType == "A") {
                                    $predefinedaddons = WHMCS\Database\Capsule::table("tbladdons")->pluck("name", "id");
                                    if (!$lefttoselect) {
                                        $lefttoselect = 1;
                                    }
                                    $result = WHMCS\Database\Capsule::table("tblhostingaddons")->join("tblhosting", "tblhosting.id", "=", "tblhostingaddons.hostingid")->join("tblproducts", "tblproducts.id", "=", "tblhosting.packageid")->where("tblhostingaddons.userid", $pauserid)->limit($lefttoselect)->offset(0)->orderBy("tblhostingaddons.status")->orderBy("tblhosting.id", "desc")->select(array("tblhostingaddons.*", "tblhostingaddons.id as addonid", "tblhostingaddons.addonid as addonid2", "tblhostingaddons.name as addonname", "tblhosting.id as hostingid", "tblhosting.domain", "tblproducts.name"));
                                    if ($selectedRelatedType == "A") {
                                        $result2 = WHMCS\Database\Capsule::table("tblhostingaddons")->join("tblhosting", "tblhosting.id", "=", "tblhostingaddons.hostingid")->join("tblproducts", "tblproducts.id", "=", "tblhosting.packageid")->where("tblhostingaddons.userid", $pauserid)->where("tblhostingaddons.id", $selectedRelatedId)->select(array("tblhostingaddons.*", "tblhostingaddons.id as addonid", "tblhostingaddons.addonid as addonid2", "tblhostingaddons.name as addonname", "tblhosting.id as hostingid", "tblhosting.domain", "tblproducts.name"));
                                        $result->union($result2);
                                    }
                                    foreach ($result->get() as $data) {
                                        $data = (array) $data;
                                        $service_id = $data["id"];
                                        $hostingid = $data["hostingid"];
                                        $service_addonid = $data["addonid2"];
                                        $service_name = $data["name"];
                                        $service_addon = $data["addonname"];
                                        $service_domain = $data["domain"];
                                        $service_recurringamount = $data["recurring"];
                                        $service_billingcycle = $data["billingcycle"];
                                        $service_signupdate = fromMySQLDate($data["regdate"]);
                                        $service_status = $data["status"];
                                        if (!$service_addon) {
                                            $service_addon = $predefinedaddons[$service_addonid];
                                        }
                                        if (in_array($data["billingcycle"], array("One Time", "Free Account"))) {
                                            $service_nextduedate = "-";
                                        } else {
                                            $service_nextduedate = fromMySQLDate($data["nextduedate"]);
                                        }
                                        $service_amount = formatCurrency($service_recurringamount);
                                        $selected = false;
                                        if ($selectedRelatedType == "A" && $selectedRelatedId == $service_id) {
                                            $selected = true;
                                        }
                                        $index = $relatedIndex;
                                        if ($selected) {
                                            $index = -1;
                                        }
                                        $name = AdminLang::trans("orders.addon") . " - " . $service_addon . "<br>" . "<a href=\"clientsservices.php?userid=" . $pauserid . "&id=" . $hostingid . "&aid=" . $service_id . "\" target=\"_blank\">" . $service_name . "</a>" . " - <a href=\"http://" . $service_domain . "/\" target=\"_blank\">" . $service_domain . "</a>";
                                        $smartyvalues["relatedservices"][$index] = array("id" => $service_id, "type" => "addon", "serviceid" => $hostingid, "name" => $name, "product" => $service_addon, "domain" => $service_domain, "amount" => $service_amount, "billingcycle" => $service_billingcycle, "regdate" => $service_signupdate, "nextduedate" => $service_nextduedate, "status" => $service_status, "selected" => $selected);
                                        $relatedIndex++;
                                    }
                                }
                                $lefttoselect = 10 - count($smartyvalues["relatedservices"]);
                                if (0 < $lefttoselect || $selectedRelatedType == "D") {
                                    if (!$lefttoselect) {
                                        $lefttoselect = 1;
                                    }
                                    $result = WHMCS\Database\Capsule::table("tbldomains")->where("userid", $pauserid)->limit($lefttoselect)->offset(0)->orderBy("status")->orderBy("id", "desc");
                                    if ($selectedRelatedType == "D") {
                                        $result2 = WHMCS\Database\Capsule::table("tbldomains")->where("userid", $pauserid)->where("id", $selectedRelatedId);
                                        $result->union($result2);
                                    }
                                    foreach ($result->get() as $data) {
                                        $data = (array) $data;
                                        $service_id = $data["id"];
                                        $service_domain = $data["domain"];
                                        $service_firstpaymentamount = $data["firstpaymentamount"];
                                        $service_recurringamount = $data["recurringamount"];
                                        $service_registrationperiod = $data["registrationperiod"] . " Year(s)";
                                        $service_signupdate = $data["registrationdate"];
                                        $service_nextduedate = $data["nextduedate"];
                                        $service_status = $data["status"];
                                        $service_signupdate = fromMySQLDate($service_signupdate);
                                        if ($service_nextduedate == "0000-00-00") {
                                            $service_nextduedate = "-";
                                        } else {
                                            $service_nextduedate = fromMySQLDate($service_nextduedate);
                                        }
                                        if ($service_recurringamount <= 0) {
                                            $service_amount = $service_firstpaymentamount;
                                        } else {
                                            $service_amount = $service_recurringamount;
                                        }
                                        $service_amount = formatCurrency($service_amount);
                                        $selected = false;
                                        $index = $relatedIndex;
                                        if ($selectedRelatedType == "D" && $selectedRelatedId == $service_id) {
                                            $selected = true;
                                            $index = -1;
                                        }
                                        $href = "clientsdomains.php?userid=" . $pauserid . "&id=" . $service_id;
                                        $name = "<a href=\"" . $href . "\" target=\"_blank\">" . AdminLang::trans("fields.domain") . "</a>" . " - <a href=\"http://" . $service_domain . "/\" target=\"_blank\">" . $service_domain . "</a>";
                                        $smartyvalues["relatedservices"][$index] = array("id" => $service_id, "type" => "domain", "name" => $name, "product" => AdminLang::trans("fields.domain"), "domain" => $service_domain, "amount" => $service_amount, "billingcycle" => $service_registrationperiod, "regdate" => $service_signupdate, "nextduedate" => $service_nextduedate, "status" => $service_status, "selected" => $selected);
                                        $relatedIndex++;
                                    }
                                }
                                ksort($smartyvalues["relatedservices"]);
                                if (10 < count($smartyvalues["relatedservices"])) {
                                    $smartyvalues["relatedservices"] = array_chunk($smartyvalues["relatedservices"], 10, true);
                                    $smartyvalues["relatedservices"] = $smartyvalues["relatedservices"][0];
                                }
                                if (count($smartyvalues["relatedservices"]) < $totalitems) {
                                    $smartyvalues["relatedservicesexpand"] = true;
                                }
                            }
                            $jscode .= "function insertKBLink(url, title) {\n    \$(\"#replymessage\").addToReply(url, title);\n}";
                            $aInt->addMarkdownEditor("replyTicketMDE", "ticket_reply_" . md5($id . WHMCS\Session::get("adminid")), "replymessage");
                            $aInt->addMarkdownEditor("noteMDE", "note_" . md5($id . WHMCS\Session::get("adminid")), "replynote", false);
                            $jquerycode = "\n(function() {\n    var fieldSelection = {\n        addToReply: function() {\n            var url = arguments[0] || '',\n                title = arguments[1] || ''\n                e = this.jquery ? this[0] : this,\n                text = '';\n\n            if (title != '') {\n                text = '[' + title + '](' + url + ')';\n            } else {\n                text = url;\n            }\n\n            return (\n                ('selectionStart' in e && function() {\n                    if (e.value==\"\\n\\n" . str_replace("\r\n", "\\n", $signature) . "\") {\n                        e.selectionStart=0;\n                        e.selectionEnd=0;\n                    }\n                    e.value = e.value.substr(0, e.selectionStart) + text + e.value.substr(e.selectionEnd, e.value.length);\n                    e.focus();\n                    return this;\n                }) ||\n                (document.selection && function() {\n                    e.focus();\n                    document.selection.createRange().text = text;\n                    return this;\n                }) ||\n                function() {\n                    e.value += text;\n                    return this;\n                }\n            )();\n        }\n    };\n    jQuery.each(fieldSelection, function(i) { jQuery.fn[i] = this; });\n    })();";
                            $aInt->jquerycode = $jquerycode;
                            $replies = array();
                            $result = select_query("tbltickets", "userid,contactid,name,email,date,title,message,admin,attachment,attachments_removed,editor", array("id" => $id));
                            $data = mysql_fetch_array($result);
                            $userid = $data["userid"];
                            $contactid = $data["contactid"];
                            $name = $data["name"];
                            $email = $data["email"];
                            $date = $data["date"];
                            $title = $data["title"];
                            $message = $data["message"];
                            $admin = $data["admin"];
                            $attachment = $data["attachment"];
                            $attachmentsRemoved = (bool) (int) $data["attachments_removed"];
                            $friendlydate = substr($date, 0, 10) == date("Y-m-d") ? "" : (substr($date, 0, 4) == date("Y") ? WHMCS\Carbon::createFromFormat("Y-m-d H:i:s", $date)->format("l jS F") : WHMCS\Carbon::createFromFormat("Y-m-d H:i:s", $date)->format("l jS F Y"));
                            $friendlytime = date("H:i", strtotime($date));
                            $date = fromMySQLDate($date, true);
                            $markupFormat = $markup->determineMarkupEditor("ticket_msg", $data["editor"]);
                            $message = $markup->transform($message, $markupFormat);
                            if ($userid) {
                                $name = $aInt->outputClientLink(array($userid, $contactid), "", "", "", "", true);
                            }
                            $attachmentType = "ticket";
                            if ($attachmentsRemoved) {
                                $attachmentType = "removed";
                            }
                            $attachments = getTicketAttachmentsInfo($id, $attachment, $attachmentType);
                            $replies[$data["date"]][] = array("id" => 0, "admin" => $admin, "userid" => $userid, "contactid" => $contactid, "clientname" => $name, "clientemail" => $email, "date" => $date, "friendlydate" => $friendlydate, "friendlytime" => $friendlytime, "message" => $message, "attachments" => $attachments, "attachments_removed" => $attachmentsRemoved, "numattachments" => count($attachments));
                            $result = select_query("tblticketreplies", "", array("tid" => $id), "date", "ASC");
                            $lastReplyId = 0;
                            while ($data = mysql_fetch_array($result)) {
                                $replyid = $data["id"];
                                $userid = $data["userid"];
                                $contactid = $data["contactid"];
                                $name = $data["name"];
                                $email = $data["email"];
                                $date = $data["date"];
                                $message = $data["message"];
                                $attachment = $data["attachment"];
                                $attachmentsRemoved = (bool) (int) $data["attachments_removed"];
                                $admin = $data["admin"];
                                $rating = $data["rating"];
                                $friendlydate = substr($date, 0, 10) == date("Y-m-d") ? "" : (substr($date, 0, 4) == date("Y") ? WHMCS\Carbon::createFromFormat("Y-m-d H:i:s", $date)->format("l jS F") : WHMCS\Carbon::createFromFormat("Y-m-d H:i:s", $date)->format("l jS F Y"));
                                $friendlytime = date("H:i", strtotime($date));
                                $date = fromMySQLDate($date, true);
                                $markupFormat = $markup->determineMarkupEditor("ticket_reply", $data["editor"]);
                                $message = $markup->transform($message, $markupFormat);
                                if ($userid) {
                                    $name = $aInt->outputClientLink(array($userid, $contactid), "", "", "", "", true);
                                }
                                $attachmentType = "reply";
                                if ($attachmentsRemoved) {
                                    $attachmentType = "removed";
                                }
                                $attachments = getTicketAttachmentsInfo($id, $attachment, $attachmentType, $replyid);
                                $ratingstars = "";
                                if ($admin && $rating) {
                                    for ($i = 1; $i <= 5; $i++) {
                                        $ratingstars .= $i <= $rating ? DI::make("asset")->imgTag("rating_pos.png", "+", array("align" => "absmiddle")) : DI::make("asset")->imgTag("rating_neg.png", "-", array("align" => "absmiddle"));
                                    }
                                }
                                $replies[$data["date"]][] = array("id" => $replyid, "admin" => $admin, "userid" => $userid, "contactid" => $contactid, "clientname" => $name, "clientemail" => $email, "date" => $date, "friendlydate" => $friendlydate, "friendlytime" => $friendlytime, "message" => $message, "attachments" => $attachments, "attachments_removed" => $attachmentsRemoved, "numattachments" => count($attachments), "rating" => $ratingstars);
                                if ($lastReplyId < $replyid) {
                                    $lastReplyId = $replyid;
                                }
                            }
                            $noteCollection = WHMCS\Database\Capsule::table("tblticketnotes")->where("ticketid", "=", $id)->orderBy("date")->get();
                            $notes = array();
                            foreach ($noteCollection as $note) {
                                $date = $note->date;
                                $friendlyDate = substr($date, 0, 10) == date("Y-m-d") ? "" : (substr($date, 0, 4) == date("Y") ? WHMCS\Carbon::createFromFormat("Y-m-d H:i:s", $date)->format("l jS F") : WHMCS\Carbon::createFromFormat("Y-m-d H:i:s", $date)->format("l jS F Y"));
                                $friendlyTime = date("H:i", strtotime($date));
                                $date = fromMySQLDate($date, true);
                                $attachmentType = "note";
                                $attachmentsRemoved = false;
                                if ($note->attachments_removed) {
                                    $attachmentType = "removed";
                                    $attachmentsRemoved = true;
                                }
                                $attachments = getTicketAttachmentsInfo($id, $note->attachments, $attachmentType, $note->id);
                                $markupFormat = $markup->determineMarkupEditor("ticket_note", $note->editor);
                                $message = $markup->transform($note->message, $markupFormat);
                                $mentions = WHMCS\Mentions\Mentions::getMentionReplacements($message);
                                if (0 < count($mentions)) {
                                    $message = str_replace($mentions["find"], $mentions["replace"], $message);
                                }
                                $replies[$note->date][] = array("id" => $note->id, "admin" => $note->admin, "userid" => 0, "contactid" => 0, "clientname" => "", "clientemail" => "", "date" => $date, "friendlydate" => $friendlyDate, "friendlytime" => $friendlyTime, "message" => $message, "attachments" => $attachments, "attachments_removed" => $attachmentsRemoved, "numattachments" => count($attachments), "rating" => 0, "note" => true);
                                $notes[] = array("id" => $note->id, "admin" => $note->admin, "date" => $date, "message" => $message);
                            }
                            $smartyvalues["lastReplyId"] = $lastReplyId;
                            $smartyvalues["notes"] = $notes;
                            $smartyvalues["numnotes"] = count($notes);
                            if (WHMCS\Config\Setting::getValue("SupportTicketOrder") == "DESC") {
                                krsort($replies);
                            } else {
                                ksort($replies);
                            }
                            $repliesForTemplate = array();
                            foreach ($replies as $replyGroup) {
                                foreach ($replyGroup as $reply) {
                                    $repliesForTemplate[] = $reply;
                                }
                            }
                            $smartyvalues["replies"] = $repliesForTemplate;
                            $smartyvalues["repliescount"] = count($repliesForTemplate);
                            $smartyvalues["thumbnails"] = $CONFIG["AttachmentThumbnails"] ? true : false;
                            $splitTicketPriorityDropDown = "<select id=\"splitpriorityx\" class=\"form-control\">\n    <option value=\"High\"" . ($urgency == "High" ? " selected" : "") . ">High</option>\n    <option value=\"Medium\"" . ($urgency == "Medium" ? " selected" : "") . ">Medium</option>\n    <option value=\"Low\"" . ($urgency == "Low" ? " selected" : "") . ">Low</option>\n</select>";
                            $splitTicketDialogHtml = "<p>\n    " . $aInt->lang("support", "splitticketdialoginfo") . "\n</p>\n<table class=\"padded-fields\">\n    <tr>\n        <td align=\"right\">" . $aInt->lang("support", "department") . ":</td>\n        <td><select id=\"splitdeptidx\" class=\"form-control\">" . $departmentshtml . "</select></td>\n    </tr>\n    <tr>\n        <td align=\"right\">" . $aInt->lang("support", "splitticketdialognewticketname") . ":</td>\n        <td><input type=\"text\" id=\"splitsubjectx\" size=\"35\" value=\"" . $title . "\" class=\"form-control\" /></td>\n    </tr>\n    <tr>\n        <td align=\"right\">" . $aInt->lang("support", "priority") . ":</td>\n        <td>\n            " . $splitTicketPriorityDropDown . "\n        </td>\n    </tr>\n    <tr>\n        <td align=\"right\">" . $aInt->lang("support", "splitticketdialognotifyclient") . ":</td>\n        <td>\n            <label class=\"checkbox-inline\">\n                <input type=\"checkbox\" id=\"splitnotifyclientx\" />\n                " . $aInt->lang("support", "splitticketdialognotifyclientinfo") . "\n            </label>\n        </td>\n    </tr>\n</table>";
                            $splitTicketModal = $aInt->modal("splitTicket", AdminLang::trans("support.splitticketdialogtitle"), $splitTicketDialogHtml, array(array("title" => $aInt->lang("global", "submit"), "class" => "btn btn-primary", "onclick" => "\$(\"#splitdeptid\").val(\$(\"#splitdeptidx\").val());\$(\"#splitsubject\").val(\$(\"#splitsubjectx\").val());\$(\"#splitpriority\").val(\$(\"#splitpriorityx\").val());if (\$(\"#splitnotifyclientx\").prop(\"checked\")) { \$(\"#splitnotifyclient\").val(\"true\");};\$(\"#ticketreplies\").submit();"), array("class" => "btn btn-default", "title" => $aInt->lang("supportreq", "cancel"))));
                            $smartyvalues["splitticketdialog"] = $splitTicketModal;
                        } else {
                            if ($action == "open") {
                                $result = select_query("tbladmins", "signature", array("id" => $_SESSION["adminid"]));
                                $data = mysql_fetch_array($result);
                                $signature = $data["signature"];
                                if (isset($validate) && $validate instanceof WHMCS\Validate && $validate->hasErrors()) {
                                    infoBox($aInt->lang("global", "validationerror"), $validate->getHTMLErrorOutput(), "error");
                                    echo $infobox;
                                }
                                $allDepts = WHMCS\Support\Department::orderBy("order", "ASC")->get(array("id", "name"));
                                if ($allDepts->count() == 0) {
                                    $aInt->gracefulExit(AdminLang::trans("support.nodepartments"));
                                }
                                $assignedDeptIds = explode(",", WHMCS\Database\Capsule::table("tbladmins")->where("id", "=", $_SESSION["adminid"])->value("supportdepts"));
                                $assignedDepartments = array();
                                foreach ($allDepts as $dept) {
                                    if (in_array($dept->id, $assignedDeptIds)) {
                                        $assignedDepartments[$dept->id] = $dept->name;
                                    }
                                }
                                if (empty($assignedDepartments)) {
                                    $aInt->gracefulExit(AdminLang::trans("support.nodepartmentsassigned"));
                                }
                                $aInt->addMarkdownEditor("openTicketMDE", "ticket_open_" . md5(WHMCS\Session::get("adminid")), "replymessage");
                                $jquerycode = "\n(function() {\n    var fieldSelection = {\n        addToReply: function() {\n            var url = arguments[0] || '',\n                title = arguments[1] || ''\n                e = this.jquery ? this[0] : this,\n                text = '';\n\n            if (title != '') {\n                text = '[' + title + '](' + url + ')';\n            } else {\n                text = url;\n            }\n\n            return (\n                ('selectionStart' in e && function() {\n                    if (e.value==\"\\n\\n" . str_replace("\r\n", "\\n", $signature) . "\") {\n                        e.selectionStart=0;\n                        e.selectionEnd=0;\n                    }\n                    e.value = e.value.substr(0, e.selectionStart) + text + e.value.substr(e.selectionEnd, e.value.length);\n                    e.focus();\n                    return this;\n                }) ||\n                (document.selection && function() {\n                    e.focus();\n                    document.selection.createRange().text = text;\n                    return this;\n                }) ||\n                function() {\n                    e.value += text;\n                    return this;\n                }\n            )();\n        }\n    };\n    jQuery.each(fieldSelection, function(i) { jQuery.fn[i] = this; });\n    })();\n\$(\"#addfileupload\").click(function () {\n    \$(\"#fileuploads\").append(\"<input type=\\\"file\\\" name=\\\"attachments[]\\\" class=\\\"form-control top-margin-5\\\">\");\n    return false;\n});\n\$(\"#predefq\").keyup(function () {\n    var intellisearchlength = \$(\"#predefq\").val().length;\n    if (intellisearchlength>2) {\n    WHMCS.http.jqClient.post(\"supporttickets.php\", { action: \"loadpredefinedreplies\", predefq: \$(\"#predefq\").val(), token: \"" . generate_token("plain") . "\" },\n        function(data){\n            \$(\"#prerepliescontent\").html(data);\n        });\n    }\n});\n\$(\"#frmOpenTicket\").submit(function (e, options) {\n    options = options || {};\n\n    \$(\"#btnOpenTicket\").attr(\"disabled\", \"disabled\");\n    \$(\"#btnOpenTicket i\").removeClass(\"fa-plus\").addClass(\"fa-spinner fa-spin\");\n\n    if (options.skipValidation) {\n        return true;\n    }\n\n    e.preventDefault();\n\n    var gotValidResponse = false,\n        postReply = false,\n        responseMsg = '',\n        thisElement = jQuery(this);\n\n    WHMCS.http.jqClient.post(\n        \"supporttickets.php\",\n        {\n            action: \"validatereply\",\n            id: 'opening',\n            status: 'new',\n            token: '" . generate_token("plain") . "'\n        },\n        function(data){\n            gotValidResponse = true;\n            if (data.valid) {\n                postReply = true;\n            } else {\n                // access denied\n                responseMsg = 'Access Denied. Please try again.';\n            }\n        }, \"json\")\n        .always(function() {\n            if (!gotValidResponse) {\n                responseMsg = 'Session Expired. Please <a href=\"javascript:location.reload()\" class=\"alert-link\">reload the page</a> before continuing.';\n            }\n\n            if (responseMsg) {\n                postReply = false;\n                \$(\"#replyingAdminMsg\").html(responseMsg);\n                \$(\"#replyingAdminMsg\").removeClass('alert-info').addClass('alert-warning');\n                if (!\$(\"#replyingAdminMsg\").is(\":visible\")) {\n                    \$(\"#replyingAdminMsg\").hide().removeClass('hidden').slideDown();\n                }\n                \$('html, body').animate({\n                    scrollTop: \$(\"#replyingAdminMsg\").offset().top - 15\n                }, 400);\n            }\n\n            if (postReply) {\n                \$(\"#replyingAdminMsg\").slideUp();\n                thisElement.attr('data-no-clear', 'false');\n                \$(\"#frmOpenTicket\").trigger('submit', { 'skipValidation': true });\n            } else {\n                \$(\"#btnOpenTicket\").removeAttr(\"disabled\");\n                \$(\"#btnOpenTicket i\").removeClass(\"fa-spinner fa-spin\").addClass(\"fa-plus\");\n            }\n        }\n    );\n});\n";
                                $aInt->jquerycode = $jquerycode;
                                $jscode .= "function insertKBLink(url, title) {\n    \$(\"#replymessage\").addToReply(url, title);\n}\nfunction selectpredefcat(catid) {\n    WHMCS.http.jqClient.post(\"supporttickets.php\", { action: \"loadpredefinedreplies\", cat: catid, token: \"" . generate_token("plain") . "\" },\n    function(data){\n        \$(\"#prerepliescontent\").html(data);\n    });\n}\nfunction loadpredef(catid) {\n    \$(\"#prerepliescontainer\").slideToggle();\n    \$(\"#prerepliescontent\").html('<img src=\"images/loading.gif\" align=\"top\" /> " . $aInt->lang("global", "loading") . "');\n    WHMCS.http.jqClient.post(\"supporttickets.php\", { action: \"loadpredefinedreplies\", cat: catid, token: \"" . generate_token("plain") . "\" },\n    function(data){\n        \$(\"#prerepliescontent\").html(data);\n    });\n}\nfunction selectpredefreply(artid) {\n    WHMCS.http.jqClient.post(\"supporttickets.php\", { action: \"getpredefinedreply\", id: artid, token: \"" . generate_token("plain") . "\" },\n    function(data){\n        \$(\"#replymessage\").addToReply(data);\n    });\n    \$(\"#prerepliescontainer\").slideToggle();\n}\nfunction dropdownSelectClient(userId, name, email) {\n    jQuery(\"#clientinput\").val(userId);\n    jQuery(\"#name\").val(name).prop(\"disabled\", true);\n    jQuery(\"#email\").val(email).prop(\"disabled\", true);\n    WHMCS.http.jqClient.post(\n        \"supporttickets.php\",\n        {\n            action: \"getcontacts\",\n            userid: userId,\n            token: \"" . generate_token("plain") . "\"\n        },\n        function(data)\n        {\n            if (data) {\n                jQuery(\"#contacthtml\").html(data);\n                jQuery(\"#contactrow\").show();\n            } else {\n                jQuery(\"#contactrow\").hide();\n            }\n        }\n    );\n}\n";
                                if ($userid) {
                                    $result = select_query("tblclients", "id,firstname,lastname,companyname,email", array("id" => $userid));
                                    $data = mysql_fetch_array($result);
                                    $client = $data["id"];
                                    if ($client) {
                                        $name = $data["firstname"] . " " . $data["lastname"];
                                        if ($data["companyname"]) {
                                            $name .= " (" . $data["companyname"] . ")";
                                        }
                                        $email = $data["email"];
                                    }
                                }
                                $contactsdata = "";
                                if ($client) {
                                    $contactsdata = getTicketContacts($client);
                                }
                                echo "\n<div class=\"alert alert-info text-center hidden\" role=\"alert\" id=\"replyingAdminMsg\">\n</div>\n\n<form method=\"post\" action=\"";
                                echo $whmcs->getPhpSelf();
                                echo "?action=openticket\" enctype=\"multipart/form-data\" id=\"frmOpenTicket\" data-no-clear=\"true\">\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr>\n    <td class=\"fieldlabel\">";
                                echo $aInt->lang("fields", "client");
                                echo "</td>\n    <td class=\"fieldarea\" colspan=\"3\">\n        ";
                                echo $aInt->clientSearchDropdown("clientSearch", "", array(), AdminLang::trans("global.typeToSearchClients"), "id", 1);
                                echo "    </td>\n</tr>\n<tr>\n    <td width=\"15%\" class=\"fieldlabel\">";
                                echo AdminLang::trans("fields.name");
                                echo "</td>\n    <td class=\"fieldarea\" colspan=\"3\">\n        <input type=\"hidden\" name=\"client\" id=\"clientinput\" value=\"";
                                echo $client;
                                echo "\" />\n        <input type=\"text\" name=\"name\" id=\"name\" class=\"form-control input-300\" tabindex=\"2\" value=\"";
                                echo $name;
                                echo "\" />\n    </td>\n</tr>\n<tr><td class=\"fieldlabel\">";
                                echo $aInt->lang("fields", "email");
                                echo "</td><td class=\"fieldarea\" colspan=\"3\"><input type=\"text\" name=\"email\" id=\"email\" class=\"form-control input-400 input-inline\" tabindex=\"3\" value=\"";
                                echo $email;
                                echo "\"> <label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"sendemail\" tabindex=\"4\" checked /> ";
                                echo $aInt->lang("global", "sendemail");
                                echo "</label></td></tr>\n<tr id=\"contactrow\"";
                                if (!$contactsdata) {
                                    echo " style=\"display:none;\"";
                                }
                                echo "><td class=\"fieldlabel\">";
                                echo $aInt->lang("clientsummary", "contacts");
                                echo "</td><td class=\"fieldarea\" id=\"contacthtml\" colspan=\"3\">";
                                echo $contactsdata;
                                echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
                                echo $aInt->lang("support", "ccrecipients");
                                echo "</td><td class=\"fieldarea\" colspan=\"3\"><input type=\"text\" name=\"ccemail\" tabindex=\"5\" value=\"";
                                echo $cc;
                                echo "\" class=\"form-control input-500 input-inline\"> (";
                                echo $aInt->lang("transactions", "commaseparated");
                                echo ")</td></tr>\n<tr><td class=\"fieldlabel\">";
                                echo $aInt->lang("fields", "subject");
                                echo "</td><td class=\"fieldarea\" colspan=\"3\"><input type=\"text\" name=\"subject\" class=\"form-control\" tabindex=\"6\" value=\"";
                                echo $subject;
                                echo "\"></td></tr>\n<tr><td class=\"fieldlabel\">";
                                echo $aInt->lang("support", "department");
                                echo "</td><td class=\"fieldarea\"><select name=\"deptid\" class=\"form-control select-inline\" tabindex=\"7\">";
                                foreach ($assignedDepartments as $id => $name) {
                                    printf("<option value=\"%s\"%s>%s</option>", $id, $id == $department ? " selected" : "", $name);
                                }
                                echo "</select></td>\n    <td class=\"fieldlabel\">";
                                echo $aInt->lang("support", "priority");
                                echo "</td><td class=\"fieldarea\">\n        <select name=\"priority\" class=\"form-control select-inline\" tabindex=\"8\">\n            <option value=\"High\">";
                                echo $aInt->lang("status", "high");
                                echo "</option>\n            <option value=\"Medium\" selected>";
                                echo $aInt->lang("status", "medium");
                                echo "</option>\n            <option value=\"Low\">";
                                echo $aInt->lang("status", "low");
                                echo "</option>\n        </select>\n    </td>\n</tr>\n</table>\n\n<div class=\"margin-top-bottom-20\">\n    <textarea name=\"message\" id=\"replymessage\" rows=\"20\" tabindex=\"9\" class=\"form-control\">";
                                if ($message) {
                                    echo $message;
                                } else {
                                    if ($signature) {
                                        echo "\n\n\n" . $signature;
                                    }
                                }
                                echo "</textarea>\n</div>\n\n<div id=\"insertlinks\" class=\"margin-top-bottom-20 text-center\">\n    <a href=\"#\" onClick=\"window.open('supportticketskbarticle.php','kbartwnd','width=500,height=400,scrollbars=yes');return false\">";
                                echo $aInt->lang("support", "insertkblink");
                                echo "</a>\n    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n    <a href=\"#\" onclick=\"loadpredef('0');return false\">";
                                echo $aInt->lang("support", "insertpredef");
                                echo "</a>\n</div>\n\n<div id=\"prerepliescontainer\">\n    <div style=\"border:3px solid #E2E7E9;border-radius:4px;background-color:#f8f8f8;padding:15px;text-align:left;\" class=\"margin-top-bottom-20\">\n        <div class=\"predefined-replies-search\">\n            <input type=\"text\" id=\"predefq\" size=\"25\" placeholder=\"";
                                echo $aInt->lang("global", "search");
                                echo "\" />\n        </div>\n        <div id=\"prerepliescontent\"></div>\n    </div>\n</div>\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"15%\" class=\"fieldlabel\">";
                                echo $aInt->lang("support", "attachments");
                                echo "</td><td class=\"fieldarea\">\n    <div class=\"row\">\n        <div class=\"col-sm-10\">\n            <input type=\"file\" name=\"attachments[]\" size=\"85\" class=\"form-control\">\n            <div id=\"fileuploads\"></div>\n        </div>\n        <div class=\"col-sm-2\">\n            <a href=\"#\" id=\"addfileupload\" class=\"btn btn-default btn-block\">\n                <img src=\"images/icons/add.png\" align=\"absmiddle\" border=\"0\" />\n                ";
                                echo $aInt->lang("support", "addmore");
                                echo "            </a>\n        </div>\n    </div>\n</td></tr>\n</table>\n\n<div class=\"btn-container\">\n    <button type=\"submit\" class=\"btn btn-primary\" id=\"btnOpenTicket\">\n        <i class=\"fas fa-plus\"></i>\n        ";
                                echo AdminLang::trans("clientsummary.openticket");
                                echo "    </button>\n</div>\n\n</form>\n\n";
                            }
                        }
                        $content = ob_get_contents();
                        ob_end_clean();
                        $aInt->jscode = $jscode;
                        $aInt->content = $content;
                        $aInt->templatevars = $smartyvalues;
                        $aInt->display();
                    }
                }
            }
        }
    }
}

?>