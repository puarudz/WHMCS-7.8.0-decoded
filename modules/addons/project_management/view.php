<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if (!defined("WHMCS")) {
    exit("This file cannot be accessed directly");
}
require ROOTDIR . "/includes/gatewayfunctions.php";
require ROOTDIR . "/includes/ticketfunctions.php";
$projectid = $projectId = (int) $_REQUEST["projectid"];
$modulelink .= "&projectid=" . (int) $projectid;
if (!project_management_check_viewproject($projectid)) {
    redir("module=project_management");
}
if ($a == "addinvoice") {
    check_token("WHMCS.admin.default");
    $invoicenum = $_REQUEST["invoicenum"];
    if (!trim($invoicenum)) {
        exit($vars["_lang"]["youmustenterinvoicenumber"]);
    }
    $data = get_query_vals("tblinvoices", "id,date,datepaid,total,paymentmethod,status", array("id" => $invoicenum));
    $invoicenum = $data["id"];
    if (!$invoicenum) {
        exit($vars["_lang"]["invoicenumberenterednotfound"]);
    }
    $oldInvoiceIds = get_query_val("mod_project", "invoiceids", array("id" => $projectid));
    $invoiceids = explode(",", $oldInvoiceIds);
    if (in_array($invoicenum, $invoiceids)) {
        exit($vars["_lang"]["invoicenumberalreadyassociated"]);
    }
    $invoiceids[] = $invoicenum;
    update_query("mod_project", array("invoiceids" => implode(",", $invoiceids), "lastmodified" => "now()"), array("id" => $projectid));
    project_management_log($projectid, $vars["_lang"]["addedinvoiceassociation"] . $invoicenum);
    $invoiceid = $data["id"];
    $invoicedate = $data["date"];
    $invoicedatepaid = $data["datepaid"] != "0000-00-00 00:00:00" ? fromMySQLDate($data["datepaid"]) : "-";
    $invoicetotal = $data["total"];
    $paymentmethod = get_query_val("tblpaymentgateways", "value", array("gateway" => $data["paymentmethod"], "setting" => "name"));
    $invoicestatus = $data["status"];
    echo "<tr id=\"invoiceholder" . $i . "\"><td><a href=\"invoices.php?action=edit&id=" . $invoiceid . "\" target=\"_blank\">" . $invoiceid . "</a></td><td>" . fromMySQLDate($invoicedate) . "</td><td>" . $invoicedatepaid . "</td><td>" . $invoicetotal . "</td><td>" . $paymentmethod . "</td><td>" . getInvoiceStatusColour($invoicestatus) . "</td></tr>";
    exit;
}
if ($a == "updatestaffmsg") {
    check_token("WHMCS.admin.default");
    $msgid = $_POST["msgid"];
    $msgtxt = WHMCS\Input\Sanitize::decode($_POST["msgtxt"]);
    $oldMessage = WHMCS\Database\Capsule::table("mod_projectmessages")->where("id", "=", $msgid)->pluck("message");
    update_query("mod_projectmessages", array("message" => $msgtxt), array("id" => $msgid));
    $projectChanges = array(array("field" => "Staff Message Updated", "oldValue" => $oldMessage, "newValue" => $msgtxt));
    $project->notify()->staff($projectChanges);
    project_management_log($projectid, "Edited Staff Message");
    echo nl2br(ticketAutoHyperlinks($msgtxt));
    exit;
}
if ($a == "deletestaffmsg") {
    check_token("WHMCS.admin.default");
    if (project_management_checkperm("Delete Messages")) {
        $projectChanges = array();
        $msgid = (int) $_REQUEST["id"];
        $attachments = explode(",", get_query_val("mod_projectmessages", "attachments", array("id" => $msgid)));
        $storage = Storage::projectManagementFiles($projectid);
        foreach ($attachments as $i => $attachment) {
            if ($attachment) {
                try {
                    $storage->deleteAllowNotPresent($attachment);
                    project_management_log($projectid, $vars["_lang"]["deletedattachment"] . " " . substr($attachment, 7));
                    unset($attachments[$i]);
                } catch (Exception $e) {
                    $aInt->gracefulExit("Could not delete file: " . htmlentities($e->getMessage()));
                }
            }
        }
        delete_query("mod_projectmessages", array("id" => $msgid));
        project_management_log($projectid, "Deleted Staff Message");
        echo $msgid;
    } else {
        echo "0";
    }
    exit;
} else {
    if ($a == "hookstarttimer") {
        check_token("WHMCS.admin.default");
        $projectid = $_REQUEST["projectid"];
        $ticketnum = $_REQUEST["ticketnum"];
        $taskid = $_REQUEST["taskid"];
        $title = $_REQUEST["title"];
        if (!$taskid && $title) {
            $taskid = insert_query("mod_projecttasks", array("projectid" => $projectid, "task" => $title, "created" => "now()"));
            project_management_log($projectid, $vars["_lang"]["addedtask"] . $title);
        }
        $timerid = insert_query("mod_projecttimes", array("projectid" => $projectid, "taskid" => $taskid, "start" => time(), "adminid" => $_SESSION["adminid"]));
        project_management_log($projectid, $vars["_lang"]["startedtimerfortask"] . get_query_val("mod_projecttasks", "task", array("id" => $taskid)));
        if ($timerid) {
            $result = select_query("mod_projecttimes", "mod_projecttimes.id, mod_projecttimes.projectid, mod_project.title, mod_projecttimes.taskid, mod_projecttasks.task, mod_projecttimes.start", array("mod_projecttimes.adminid" => $_SESSION["adminid"], "mod_projecttimes.end" => "", "mod_project.ticketids" => array("sqltype" => "LIKE", "value" => (int) $ticketnum)), "", "", "", "mod_projecttasks ON mod_projecttimes.taskid=mod_projecttasks.id INNER JOIN mod_project ON mod_projecttimes.projectid=mod_project.id");
            while ($data = mysql_fetch_array($result)) {
                echo "<div class=\"stoptimer" . $data["id"] . "\" style=\"padding-bottom:10px;\"><em>" . $data["title"] . " - Project ID " . $data["projectid"] . "</em><br />&nbsp;&raquo; " . $data["task"] . "<br />Started at " . fromMySQLDate(date("Y-m-d H:i", $data["start"]), 1) . ":" . date("s", $data["start"]) . " - <a href=\"#\" onclick=\"projectendtimersubmit('" . $data["projectid"] . "','" . $data["id"] . "');return false\"><strong>Stop Timer</strong></a></div>";
            }
        } else {
            echo "0";
        }
        exit;
    }
    if ($a == "hookendtimer") {
        check_token("WHMCS.admin.default");
        $timerid = $_POST["timerid"];
        $ticketnum = $_POST["ticketnum"];
        $taskid = get_query_val("mod_projecttimes", "taskid", array("id" => $timerid, "adminid" => $_SESSION["adminid"]));
        $projectid = get_query_val("mod_projecttimes", "projectid", array("id" => $timerid, "adminid" => $_SESSION["adminid"]));
        update_query("mod_projecttimes", array("end" => time()), array("id" => $timerid, "taskid" => $taskid, "adminid" => $_SESSION["adminid"]));
        project_management_log($projectid, $vars["_lang"]["stoppedtimerfortask"] . get_query_val("mod_projecttasks", "task", array("id" => $taskid)));
        if (!$taskid) {
            echo "0";
        } else {
            $result = select_query("mod_projecttimes", "mod_projecttimes.id, mod_projecttimes.projectid, mod_project.title, mod_projecttimes.taskid, mod_projecttasks.task, mod_projecttimes.start", array("mod_projecttimes.adminid" => $_SESSION["adminid"], "mod_projecttimes.end" => "", "mod_project.ticketids" => array("sqltype" => "LIKE", "value" => (int) $ticketnum)), "", "", "", "mod_projecttasks ON mod_projecttimes.taskid=mod_projecttasks.id INNER JOIN mod_project ON mod_projecttimes.projectid=mod_project.id");
            while ($data = mysql_fetch_array($result)) {
                echo "<div class=\"stoptimer" . $data["id"] . "\" style=\"padding-bottom:10px;\"><em>" . $data["title"] . " - Project ID " . $data["projectid"] . "</em><br />&nbsp;&raquo; " . $data["task"] . "<br />Started at " . fromMySQLDate(date("Y-m-d H:i", $data["start"]), 1) . ":" . date("s", $data["start"]) . " - <a href=\"#\" onclick=\"projectendtimersubmit('" . $data["projectid"] . "','" . $data["id"] . "');return false\"><strong>Stop Timer</strong></a></div>";
            }
        }
        exit;
    }
    if ($a == "deleteticket") {
        check_token("WHMCS.admin.default");
        if (project_management_checkperm("Associate Tickets")) {
            $result = select_query("mod_project", "ticketids", array("id" => $projectid));
            $data = mysql_fetch_array($result);
            $ticketids = explode(",", $data["ticketids"]);
            project_management_log($projectid, $vars["_lang"]["deletedticketrelationship"] . $ticketids[$_REQUEST["id"]]);
            unset($ticketids[$_REQUEST["id"]]);
            update_query("mod_project", array("ticketids" => implode(",", $ticketids), "lastmodified" => "now()"), array("id" => $projectid));
            echo $_REQUEST["id"];
            exit;
        }
    } else {
        if ($a == "projectsave") {
            check_token("WHMCS.admin.default");
            $logmsg = "";
            $projectChanges = array();
            $result = select_query("mod_project", "", array("id" => $projectid));
            $data = mysql_fetch_array($result);
            $updateqry["userid"] = $_POST["userid"];
            $updateqry["title"] = $_POST["title"];
            $updateqry["adminid"] = $_POST["adminid"];
            $updateqry["created"] = toMySQLDate($_POST["created"]);
            $updateqry["duedate"] = toMySQLDate($_POST["duedate"]);
            $updateqry["lastmodified"] = "now()";
            if ($_POST["completed"]) {
                update_query("mod_projecttasks", array("completed" => "1"), array("projectid" => $projectid));
            }
            if (!$logmsg) {
                if ($updateqry["title"] && $updateqry["title"] != $data["title"]) {
                    $changes[] = $vars["_lang"]["titlechangedfrom"] . $data["title"] . " to " . $updateqry["title"];
                    $projectChanges[] = array("field" => "Title", "oldValue" => $data["title"], "newValue" => $updateqry["title"]);
                }
                if (isset($updateqry["userid"]) && $updateqry["userid"] != $data["userid"]) {
                    $changes[] = $vars["_lang"]["assignedclientchangedfrom"] . $data["userid"] . " " . $vars["_lang"]["to"] . " " . $updateqry["userid"];
                    $projectChanges[] = array("field" => "User Id", "oldValue" => $data["userid"], "newValue" => $updateqry["userid"]);
                }
                if ($updateqry["adminid"] != $data["adminid"]) {
                    $adminId = $data["adminid"] ? getAdminName($data["adminid"]) : "Nobody";
                    $newAdminId = $updateqry["adminid"] ? getAdminName($updateqry["adminid"]) : "Nobody";
                    $changes[] = $vars["_lang"]["assignedadminchangedfrom"] . (string) $adminId . " " . $vars["_lang"]["to"] . " " . $newAdminId;
                    $projectChanges[] = array("field" => "Admin Id", "oldValue" => $adminId, "newValue" => $newAdminId);
                }
                if ($_POST["created"] && $_POST["created"] != fromMySQLDate($data["created"])) {
                    $oldCreated = fromMySQLDate($data["created"]);
                    $newCreated = $whmcs->get_req_var("created");
                    $changes[] = $vars["_lang"]["creationdatechangedfrom"] . " " . $oldCreated . " to " . $newCreated;
                    $projectChanges[] = array("field" => "Created", "oldValue" => $oldCreated, "newValue" => $newCreated);
                }
                if ($_POST["duedate"] && $_POST["duedate"] != fromMySQLDate($data["duedate"])) {
                    $oldDueDate = fromMySQLDate($data["duedate"]);
                    $newDueDate = $whmcs->get_req_var("duedate");
                    $changes[] = $vars["_lang"]["duedatechangedfrom"] . (string) $oldDueDate . " to " . $newDueDate;
                    $projectChanges[] = array("field" => "Due Date", "oldValue" => $oldDueDate, "newValue" => $newDueDate);
                }
                if ($_POST["newticketid"]) {
                    $newTicketId = $whmcs->get_req_var("newticketid");
                    $changes[] = $vars["_lang"]["addednewrelatedticket"] . $newticketid;
                    $projectChanges[] = array("field" => "New Ticket", "oldValue" => "", "newValue" => $newticketid);
                }
                if ($updateqry["notes"] && $updateqry["notes"] != $data["notes"]) {
                    $changes[] = $vars["_lang"]["notesupdated"];
                    $projectChanges[] = array("field" => "Notes", "oldValue" => $data["notes"], "newValue" => $updateqry["notes"]);
                }
                if ($updateqry["completed"] && $updateqry["completed"] != $data["completed"]) {
                    $changes[] = $vars["_lang"]["projectmarkedcompleted"];
                    $projectChanges[] = array("field" => "Completed", "oldValue" => $data["completed"], "newValue" => $updateqry["completed"]);
                }
                $logmsg = $vars["_lang"]["updatedproject"] . implode(", ", $changes);
            }
            if (count($changes)) {
                project_management_log($projectid, $logmsg);
            }
            update_query("mod_project", $updateqry, array("id" => $projectid));
            echo project_management_daysleft(toMySQLDate($_POST["duedate"]), $vars);
            exit;
        }
        if ($a == "statussave") {
            check_token("WHMCS.admin.default");
            if (project_management_checkperm("Update Status")) {
                $status = db_escape_string($_POST["status"]);
                $statuses = explode(",", $vars["statusvalues"]);
                $statusarray = array();
                foreach ($statuses as $tmpstatus) {
                    $tmpstatus = explode("|", $tmpstatus, 2);
                    $statusarray[] = $tmpstatus[0];
                }
                if (in_array($status, $statusarray)) {
                    $oldstatus = get_query_val("mod_project", "status", array("id" => $projectid));
                    $updateqry = array("status" => $status);
                    if (in_array($status, explode(",", $vars["completedstatuses"]))) {
                        $updateqry["completed"] = "1";
                    } else {
                        $updateqry["completed"] = "0";
                    }
                    update_query("mod_project", $updateqry, array("id" => $projectid));
                    project_management_log($projectid, $vars["_lang"]["statuschangedfrom"] . $oldstatus . " " . $vars["_lang"]["to"] . " " . $status);
                }
            }
            exit;
        } else {
            if ($a == "addquickinvoice") {
                check_token("WHMCS.admin.default");
                $newinvoice = trim($_REQUEST["newinvoice"]);
                $newinvoiceamt = trim($_REQUEST["newinvoiceamt"]);
                if ($newinvoice && $newinvoiceamt) {
                    $projectChanges = array();
                    $userid = get_query_val("mod_project", "userid", array("id" => $projectid));
                    $gateway = function_exists("getClientsPaymentMethod") ? getClientsPaymentMethod($userid) : "paypal";
                    if ($CONFIG["TaxEnabled"] == "on") {
                        $clientsdetails = getClientsDetails($userid);
                        if (!$clientsdetails["taxexempt"]) {
                            $state = $clientsdetails["state"];
                            $country = $clientsdetails["country"];
                            $taxdata = getTaxRate(1, $state, $country);
                            $taxdata2 = getTaxRate(2, $state, $country);
                            $taxrate = $taxdata["rate"];
                            $taxrate2 = $taxdata2["rate"];
                        }
                    }
                    $invoice = new WHMCS\Billing\Invoice();
                    $invoice->dateCreated = WHMCS\Carbon::now();
                    $invoice->dateDue = WHMCS\Carbon::now();
                    $invoice->clientId = $userid;
                    $invoice->status = "Unpaid";
                    $invoice->paymentGateway = $gateway;
                    $invoice->taxRate1 = $taxrate;
                    $invoice->taxRate2 = $taxrate2;
                    $invoice->save();
                    $invoiceid = $invoice->id;
                    insert_query("tblinvoiceitems", array("invoiceid" => $invoiceid, "userid" => $userid, "type" => "Project", "relid" => $projectid, "description" => $newinvoice, "paymentmethod" => $gateway, "amount" => $newinvoiceamt, "taxed" => "1"));
                    updateInvoiceTotal($invoiceid);
                    $invoiceids = get_query_val("mod_project", "invoiceids", array("id" => $projectid));
                    $invoiceids = explode(",", $invoiceids);
                    $invoiceids[] = $invoiceid;
                    $invoiceids = implode(",", $invoiceids);
                    update_query("mod_project", array("invoiceids" => $invoiceids), array("id" => $projectid));
                    project_management_log($projectid, $vars["_lang"]["addedquickinvoice"] . " " . $invoiceid, $userid);
                    $invoiceArr = array("source" => "adminarea", "user" => WHMCS\Session::get("adminid"), "invoiceid" => $invoiceid, "status" => "Unpaid");
                    run_hook("InvoiceCreation", $invoiceArr);
                    run_hook("InvoiceCreationAdminArea", $invoiceArr);
                }
                redir("module=project_management&m=view&projectid=" . $projectid);
            } else {
                if ($a == "gettimesheethead") {
                    check_token("WHMCS.admin.default");
                    echo WHMCS\View\Asset::cssInclude("jquery-ui.min.css") . WHMCS\View\Asset::jsInclude("jquery.min.js") . WHMCS\View\Asset::jsInclude("jquery-ui.min.js");
                    exit;
                }
                if ($a == "gettimesheet") {
                    check_token("WHMCS.admin.default");
                    if (project_management_checkperm("Bill Tasks")) {
                        echo "<form method=\"post\" action=\"" . $modulelink . "&a=dynamicinvoicegenerate\">\n        " . generate_token() . "\n<div class=\"box\">\n<table width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\" class=\"tasks\" id=\"tasks\"><tr bgcolor=\"#efefef\">\n    <th width=\"60%\">" . $vars["_lang"]["description"] . "</th><th width=\"10%\">" . $vars["_lang"]["hours"] . "</th><th width=\"14%\">" . $vars["_lang"]["rate"] . "</th><th width=\"15%\">" . $vars["_lang"]["amount"] . "</th><th width=\"20\"></th></tr>";
                        $dti = 0;
                        for ($tasksresult = select_query("mod_projecttasks", "id,task", array("projectid" => $projectid, "billed" => "0")); $tasksdata = mysql_fetch_assoc($tasksresult); $dti++) {
                            $dynamictimes[$dti]["seconds"] = get_query_val("mod_projecttimes", "SUM(end-start)", array("taskid" => $tasksdata["id"], "donotbill" => 0));
                            $dynamictimes[$dti]["description"] = $tasksdata["task"];
                            $dynamictimes[$dti]["rate"] = $vars["hourlyrate"];
                            $dynamictimes[$dti]["amount"] = $dynamictimes[$dti]["rate"] * $dynamictimes[$dti]["seconds"] / 3600;
                            if (0 < $dynamictimes[$dti]["seconds"]) {
                                echo "<tr id=\"dynamictaskinvoiceitemholder" . $dti . "\">\n            <td><input type=\"hidden\" name=\"taskid[" . $dti . "]\" value=\"" . $tasksdata["id"] . "\" /><input style=\"width:99%\" type=\"text\" name=\"description[" . $dti . "]\" value=\"" . $dynamictimes[$dti]["description"] . "\" /></td>\n            <td><input type=\"hidden\" id=\"dynamicbillhours" . $dti . "\" name=\"hours[" . $dti . "]\" value=\"" . round($dynamictimes[$dti]["seconds"] / 3600, 2) . "\" /><input type=\"text\" name=\"displayhours[" . $dti . "]\" class=\"dynamicbilldisplayhours\" id=\"dynamicbilldisplayhours" . $dti . "\" name=\"hours[" . $dti . "]\" value=\"" . project_management_sec2hms($dynamictimes[$dti]["seconds"]) . "\" /></td>\n            <td><input type=\"text\" class=\"dynamicbillrate\" id=\"dynamicbillrate" . $dti . "\" name=\"rate[" . $dti . "]\" value=\"" . format_as_currency($dynamictimes[$dti]["rate"]) . "\" /></td>\n            <td><input type=\"text\" id=\"dynamicbillamount" . $dti . "\" name=\"amount[" . $dti . "]\" value=\"" . format_as_currency($dynamictimes[$dti]["amount"], 2) . "\" /></td>\n            <td><a class=\"deldynamictaskinvoice\" id=\"deldynamictaskinvoice" . $dti . "\"><img src=\"images/delete.gif\"></a></td></tr>";
                            }
                        }
                        echo "</table></div>\n        <p align=\"center\">\n            <input type=\"submit\" value=\"" . $vars["_lang"]["generatenow"] . "\" />&nbsp;\n            <input type=\"submit\" onClick=\"form.action='" . $modulelink . "&a=dynamicinvoicegenerate&sendinvoicegenemail=true&token=" . generate_token("plain") . "'\" value=\"" . $vars["_lang"]["generatenowandemail"] . "\" />&nbsp;\n            <input type=\"button\" id=\"dynamictasksinvoicecancel\" value=\"" . $vars["_lang"]["cancel"] . "\" />\n        </p>\n        </form>";
                    }
                    exit;
                }
                if ($a == "dynamicinvoicegenerate") {
                    check_token("WHMCS.admin.default");
                    if (!project_management_checkperm("Bill Tasks")) {
                        redir("module=project_management");
                    }
                    $userid = get_query_val("mod_project", "userid", array("id" => $projectid));
                    $invoice = WHMCS\Billing\Invoice::newInvoice($userid);
                    $invoice->status = "Unpaid";
                    $invoice->save();
                    $invoiceid = $invoice->id;
                    foreach ($_REQUEST["taskid"] as $taski => $taskid) {
                        update_query("mod_projecttasks", array("billed" => 1), array("id" => $taskid));
                    }
                    foreach ($_REQUEST["description"] as $desci => $description) {
                        if ($description && $_REQUEST["displayhours"][$desci] && $_REQUEST["rate"][$desci] && $_REQUEST["amount"][$desci]) {
                            $description .= " - " . $_REQUEST["displayhours"][$desci] . " " . $vars["_lang"]["hours"];
                            if ($_REQUEST["rate"][$desci] != $vars["hourlyrate"]) {
                                $amount = $_REQUEST["hours"][$desci] * $_REQUEST["rate"][$desci];
                            } else {
                                $amount = $_REQUEST["amount"][$desci];
                            }
                            insert_query("tblinvoiceitems", array("invoiceid" => $invoiceid, "userid" => $userid, "type" => "Project", "relid" => $projectid, "description" => $description, "paymentmethod" => $gateway, "amount" => round($amount, 2), "taxed" => "1"));
                        }
                    }
                    updateInvoiceTotal($invoiceid);
                    $oldInvoiceIds = get_query_val("mod_project", "invoiceids", array("id" => $projectid));
                    $invoiceids = explode(",", $oldInvoiceIds);
                    $invoiceids[] = $invoiceid;
                    $invoiceids = implode(",", $invoiceids);
                    update_query("mod_project", array("invoiceids" => $invoiceids), array("id" => $projectid));
                    if ($invoiceid && $_REQUEST["sendinvoicegenemail"] == "true") {
                        sendMessage("Invoice Created", $invoiceid);
                    }
                    project_management_log($projectid, $vars["_lang"]["createdtimebasedinvoice"] . " " . $invoiceid, $userid);
                    $invoiceArr = array("source" => "adminarea", "user" => WHMCS\Session::get("adminid"), "invoiceid" => $invoiceid, "status" => "Unpaid");
                    run_hook("InvoiceCreation", $invoiceArr);
                    run_hook("InvoiceCreationAdminArea", $invoiceArr);
                    redir("module=project_management&m=view&projectid=" . $projectid);
                } else {
                    if ($a == "savetasklist") {
                        check_token("WHMCS.admin.default");
                        $tasksarray = array();
                        $result = select_query("mod_projecttasks", "", array("projectid" => $_REQUEST["projectid"]), "order", "ASC");
                        while ($data = mysql_fetch_array($result)) {
                            $tasksarray[] = array("task" => $data["task"], "notes" => $data["notes"], "adminid" => $data["adminid"], "duedate" => $data["duedate"]);
                        }
                        insert_query("mod_projecttasktpls", array("name" => $_REQUEST["taskname"], "tasks" => safe_serialize($tasksarray)));
                    } else {
                        if ($a == "loadtasklist") {
                            check_token("WHMCS.admin.default");
                            $maxorder = get_query_val("mod_projecttasks", "MAX(`order`)", array("projectid" => $_REQUEST["projectid"]));
                            $result = select_query("mod_projecttasktpls", "tasks", array("id" => $_REQUEST["tasktplid"]));
                            $data = mysql_fetch_array($result);
                            $tasks = safe_unserialize($data["tasks"]);
                            foreach ($tasks as $task) {
                                $maxorder++;
                                insert_query("mod_projecttasks", array("projectid" => $_REQUEST["projectid"], "task" => $task["task"], "notes" => $task["notes"], "adminid" => $task["adminid"], "created" => "now()", "order" => $maxorder));
                            }
                            redir("module=project_management&m=view&projectid=" . $projectid);
                        }
                    }
                }
            }
        }
    }
    if ($projectid) {
        $result = select_query("mod_project", "", array("id" => $projectid));
        $data = mysql_fetch_array($result);
        $projectid = $data["id"];
        if (!$projectid) {
            echo "<p><b>" . $vars["_lang"]["viewingproject"] . "</b></p><p>" . $vars["_lang"]["projectidnotfound"] . "</p>";
            return NULL;
        }
        $title = $data["title"];
        $attachments = $data["attachments"];
        $ticketids = $data["ticketids"];
        $notes = $data["notes"];
        $userid = $data["userid"];
        $adminid = $data["adminid"];
        $created = $data["created"];
        $duedate = $data["duedate"];
        $completed = $data["completed"];
        $projectstatus = $data["status"];
        $lastmodified = $data["lastmodified"];
        $daysleft = project_management_daysleft($duedate, $vars);
        $attachments = explode(",", $attachments);
        $ticketids = explode(",", $ticketids);
        $created = fromMySQLDate($created);
        $duedate = fromMySQLDate($duedate);
        $lastmodified = fromMySQLDate($lastmodified, true);
        $client = "";
        if (!$userid) {
            foreach ($ticketids as $i => $ticketnum) {
                if ($ticketnum) {
                    $result = select_query("tbltickets", "userid", array("tid" => $ticketnum));
                    $data = mysql_fetch_array($result);
                    $userid = $data["userid"];
                    update_query("mod_project", array("userid" => $userid), array("id" => $projectid));
                }
            }
        }
        if ($userid) {
            $result = select_query("tblclients", "id,firstname,lastname,companyname", array("id" => $userid));
            $data = mysql_fetch_array($result);
            $clientname = $data[1] . " " . $data[2];
            if ($data[3]) {
                $clientname .= " (" . $data[3] . ")";
            }
            $client = "<a href=\"clientssummary.php?userid=" . $userid . "\">" . $clientname . "</a>";
        }
        $headtitle = $title;
    } else {
        $headtitle = $vars["_lang"]["newproject"];
        $daysleft = $client = "";
        $created = getTodaysDate();
        $duedate = getTodaysDate();
    }
    $admin = trim(get_query_val("tbladmins", "CONCAT(firstname,' ',lastname)", array("id" => $adminid)));
    if (!$admin) {
        $admin = $vars["_lang"]["none"];
    }
    if (!$client) {
        $client = $vars["_lang"]["none"];
    }
    $output = array();
    $output["tasks"] = $project->tasks()->listall();
    $output["tasksSummary"] = $project->tasks()->getTaskSummary();
    $output["messages"] = $project->messages()->get();
    $output["tickets"] = $project->tickets()->get();
    $output["invoices"] = $project->invoices()->get();
    $output["departments"] = $project->tickets()->getDepartments();
    if ($project->userid) {
        $client = WHMCS\User\Client::find($project->userid);
    }
    $output["client"] = $client ?: NULL;
    $output["contacts"] = $client ? $client->contacts ? $client->contacts->pluck("fullName", "id") : array() : array();
    $output["files"] = $project->files()->get();
    $timers = $project->timers();
    $output["timers"] = $timers->get();
    $output["openTimerId"] = $timers->getOpenTimerId();
    $output["timerStats"] = $timers->getStats();
    $output["taskTemplates"] = WHMCS\Database\Capsule::table("mod_projecttasktpls")->lists("name", "id");
    $output["log"] = $project->log()->get();
    $output["admins"] = WHMCSProjectManagement\Helper::getAdmins();
    $output["adminName"] = array_key_exists($project->adminid, $output["admins"]) ? $output["admins"][$project->adminid] : "Unassigned";
    $output["maxFileSize"] = WHMCSProjectManagement\Helper::getFriendlyMbValue(ini_get("upload_max_filesize"));
    $output["hourlyRate"] = $vars["hourlyrate"];
    $output["statuses"] = explode(",", $vars["statusvalues"]);
    $language = $vars["_lang"];
    $output["emailTemplates"] = WHMCS\Mail\Template::master()->where("type", "general")->pluck("name", "id")->toArray();
    $output["now"] = WHMCS\Carbon::now()->toAdminDateTimeFormat();
    $output["oneWeek"] = WHMCS\Carbon::now()->addWeek()->toAdminDateTimeFormat();
    $output["dateTimeFormat"] = WHMCS\Config\Setting::getValue("DateFormat") . " H:i";
    echo $headeroutput;
    include "views/view.php";
}

?>