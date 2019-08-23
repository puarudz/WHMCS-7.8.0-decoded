<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Cron\Task;

class TicketEscalations extends \WHMCS\Scheduling\Task\AbstractTask
{
    protected $defaultPriority = 1700;
    protected $defaultFrequency = 3;
    protected $defaultDescription = "Process and escalate tickets per any Escalation Rules";
    protected $defaultName = "Ticket Escalation Rules";
    protected $systemName = "TicketEscalations";
    protected $outputs = array("processed" => array("defaultValue" => 0, "identifier" => "run", "name" => "Ticket Escalation Rules"));
    protected $icon = "fas fa-level-up-alt";
    protected $isBooleanStatus = true;
    protected $successCountIdentifier = "processed";
    protected $successKeyword = "Processed";
    public function __invoke()
    {
        include_once ROOTDIR . "/includes/adminfunctions.php";
        $markup = new \WHMCS\View\Markup\Markup();
        $ticketEscalationLastRun = \WHMCS\Config\Setting::getValue("TicketEscalationLastRun");
        $lastRunTime = $ticketEscalationLastRun ? new \WHMCS\Carbon($ticketEscalationLastRun) : null;
        $thisRunTime = \WHMCS\Carbon::now();
        $result = select_query("tblticketescalations", "", "");
        while ($data = mysql_fetch_array($result)) {
            $name = $data["name"];
            $departments = $data["departments"];
            $statuses = $data["statuses"];
            $priorities = $data["priorities"];
            $timeelapsed = $data["timeelapsed"];
            $newdepartment = $data["newdepartment"];
            $newpriority = $data["newpriority"];
            $newstatus = $data["newstatus"];
            $flagto = $data["flagto"];
            $notify = $data["notify"];
            $addreply = $data["addreply"];
            $editor = $data["editor"];
            $whereAndClauses = array();
            if ($departments) {
                $departments = explode(",", $departments);
                $whereAndClauses["departmentIdIn"] = "did IN (" . db_build_in_array($departments) . ")";
            }
            if ($statuses) {
                $statuses = explode(",", $statuses);
                $whereAndClauses["statusIn"] = "status IN (" . db_build_in_array($statuses) . ")";
            }
            if ($priorities) {
                $priorities = explode(",", $priorities);
                $whereAndClauses["urgencyIn"] = "urgency IN (" . db_build_in_array($priorities) . ")";
            }
            if ($timeelapsed) {
                $minTime = $lastRunTime ? $lastRunTime->copy()->subMinutes($timeelapsed)->format("Y-m-d H:i:s") : null;
                $maxTime = $thisRunTime->copy()->subMinutes($timeelapsed)->format("Y-m-d H:i:s");
                if ($minTime) {
                    $whereAndClauses["replyAfter"] = "lastreply > '" . $minTime . "'";
                }
                $whereAndClauses["replyBefore"] = "lastreply <= '" . $maxTime . "'";
            }
            $ticketQuery = "SELECT * FROM tbltickets WHERE merged_ticket_id = 0";
            if (count($whereAndClauses)) {
                $ticketQuery .= " AND " . implode(" AND ", $whereAndClauses);
            }
            $result2 = full_query($ticketQuery);
            while ($data = mysql_fetch_array($result2)) {
                $ticketid = $data["id"];
                $tickettid = $data["tid"];
                $ticketsubject = $data["title"];
                $ticketuserid = $data["userid"];
                $ticketdeptid = $data["did"];
                $ticketpriority = $data["urgency"];
                $ticketstatus = $data["status"];
                $ticketmsg = $data["message"];
                $ticketFlag = $data["flag"];
                $markupFormat = $markup->determineMarkupEditor("ticket_msg", $data["editor"]);
                $ticketmsg = $markup->transform($ticketmsg, $markupFormat);
                $updateqry = array();
                $changes = array();
                if ($newdepartment && $newdepartment != $ticketdeptid) {
                    $updateqry["did"] = $newdepartment;
                    $changes["Department"] = array("old" => getDepartmentName($ticketdeptid), "new" => getDepartmentName($newdepartment));
                }
                if ($newpriority && $newpriority != $ticketpriority) {
                    $updateqry["urgency"] = $newpriority;
                    $changes["Priority"] = array("old" => $ticketpriority, "new" => $newpriority);
                }
                if ($newstatus && $newstatus != $ticketstatus) {
                    $updateqry["status"] = $newstatus;
                    $changes["Status"] = array("old" => $ticketstatus, "new" => $newstatus);
                }
                if ($flagto && $flagto != $ticketFlag) {
                    $updateqry["flag"] = $flagto;
                    $changes["Assigned To"] = array("old" => $ticketFlag ? getAdminName($ticketFlag) : "Unassigned", "oldId" => $ticketFlag ?: 0, "new" => $flagto ? getAdminName($flagto) : "Unassigned", "newId" => $flagto ?: 0);
                }
                if (count($updateqry)) {
                    update_query("tbltickets", $updateqry, array("id" => $ticketid));
                }
                if ($changes) {
                    $changes["Who"] = "System";
                    \WHMCS\Tickets::notifyTicketChanges($ticketid, $changes);
                }
                if ($notify) {
                    $departmentId = $newdepartment ? $newdepartment : $ticketdeptid;
                    $departmentName = getDepartmentName($departmentId);
                    $clientName = get_query_val("tblclients", "CONCAT(firstname,' ',lastname)", array("id" => $ticketuserid));
                    $notifyTicketPriority = $newpriority ? $newpriority : $ticketpriority;
                    $notify = explode(",", $notify);
                    if (in_array("all", $notify)) {
                        sendAdminMessage("Escalation Rule Notification", array("rule_name" => $name, "ticket_id" => $ticketid, "ticket_tid" => $tickettid, "client_id" => $ticketuserid, "client_name" => $clientName, "ticket_department" => $departmentName, "ticket_subject" => $ticketsubject, "ticket_priority" => $notifyTicketPriority, "ticket_message" => $ticketmsg), "support", $departmentId);
                    }
                    foreach ($notify as $notifyid) {
                        if (is_numeric($notifyid)) {
                            sendAdminMessage("Escalation Rule Notification", array("rule_name" => $name, "ticket_id" => $ticketid, "ticket_tid" => $tickettid, "client_id" => $ticketuserid, "client_name" => $clientName, "ticket_department" => $departmentName, "ticket_subject" => $ticketsubject, "ticket_priority" => $notifyTicketPriority, "ticket_message" => $ticketmsg, "ticket_status" => $ticketstatus), "support", "", $notifyid);
                        }
                    }
                }
                if ($addreply) {
                    if (!$newstatus) {
                        $newstatus = $ticketstatus;
                    }
                    AddReply($ticketid, "", "", $addreply, "System", "", "", $newstatus, false, true, $editor == "markdown");
                }
                addTicketLog($ticketid, "Escalation Rule \"" . $name . "\" applied");
            }
        }
        update_query("tblconfiguration", array("value" => $thisRunTime->format("Y-m-d H:i:s")), array("setting" => "TicketEscalationLastRun"));
        return $this;
    }
}

?>