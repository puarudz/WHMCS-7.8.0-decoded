<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Cron\Task;

class CloseInactiveTickets extends \WHMCS\Scheduling\Task\AbstractTask
{
    protected $defaultPriority = 1610;
    protected $defaultFrequency = 1440;
    protected $defaultDescription = "Auto Close Inactive Tickets";
    protected $defaultName = "Inactive Tickets";
    protected $systemName = "CloseInactiveTickets";
    protected $outputs = array("closed" => array("defaultValue" => 0, "identifier" => "closed", "name" => "Closed"));
    protected $icon = "fas fa-ticket-alt";
    protected $successCountIdentifier = "closed";
    protected $successKeyword = "Closed";
    public function __invoke()
    {
        $whmcs = \DI::make("app");
        if (!$whmcs->get_config("CloseInactiveTickets")) {
            return $this;
        }
        $departmentresponders = array();
        $result = select_query("tblticketdepartments", "id,noautoresponder", "");
        while ($data = mysql_fetch_array($result)) {
            $id = $data["id"];
            $noautoresponder = $data["noautoresponder"];
            $departmentresponders[$id] = $noautoresponder;
        }
        $inactiveTicketsClosed = 0;
        $closetitles = array();
        $result = select_query("tblticketstatuses", "title", array("autoclose" => "1"));
        while ($data = mysql_fetch_array($result)) {
            $closetitles[] = $data[0];
        }
        if ($closetitles) {
            $ticketclosedate = date("Y-m-d H:i:s", mktime(date("H") - $whmcs->get_config("CloseInactiveTickets"), date("i"), date("s"), date("m"), date("d"), date("Y")));
            $query = sprintf("SELECT id,did,title FROM tbltickets" . " WHERE status IN (%s)" . " AND lastreply<='%s'", db_build_in_array($closetitles), $ticketclosedate);
            for ($result = full_query($query); $data = mysql_fetch_array($result); $inactiveTicketsClosed++) {
                $id = $data["id"];
                $did = $data["did"];
                $subject = $data["title"];
                closeTicket($id);
                if (!$departmentresponders[$did] && !$whmcs->get_config("TicketFeedback")) {
                    sendMessage("Support Ticket Auto Close Notification", $id);
                }
            }
        }
        $this->output("closed")->write($inactiveTicketsClosed);
        return $this;
    }
}

?>