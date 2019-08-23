<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCSProjectManagement;

class Tickets extends BaseProjectEntity
{
    protected $statusColours = array();
    protected function getTicketByMask($ticketMask)
    {
        $data = \WHMCS\Database\Capsule::table("tbltickets")->leftJoin("tblclients", "tblclients.id", "=", "tbltickets.userid")->leftJoin("tblcontacts", "tblcontacts.id", "=", "tbltickets.contactid")->leftJoin("tblticketdepartments", "tblticketdepartments.id", "=", "tbltickets.did")->where("tid", "=", $ticketMask)->first(array("tbltickets.id", "tbltickets.tid", "tblticketdepartments.name as departmentName", "tbltickets.date", "tbltickets.title", "tbltickets.status", "tbltickets.lastreply", "tbltickets.admin", "tbltickets.name", "tbltickets.userid", \WHMCS\Database\Capsule::raw("CONCAT_WS(' ', tblclients.firstname, tblclients.lastname) as client"), \WHMCS\Database\Capsule::raw("CONCAT_WS(' ', tblcontacts.firstname, tblcontacts.lastname) as contact")));
        if (!$data) {
            throw new Exception("Ticket ID Not Found");
        }
        $lastReplyData = \WHMCS\Database\Capsule::table("tblticketreplies")->leftJoin("tblclients", "tblclients.id", "=", "tblticketreplies.userid")->leftJoin("tblcontacts", "tblcontacts.id", "=", "tblticketreplies.contactid")->where("tid", "=", $data->id)->orderBy("tblticketreplies.id", "desc")->limit(1)->first(array("tblticketreplies.admin", \WHMCS\Database\Capsule::raw("CONCAT_WS(' ', tblclients.firstname, tblclients.lastname) as client"), \WHMCS\Database\Capsule::raw("CONCAT_WS(' ', tblcontacts.firstname, tblcontacts.lastname) as contact"), "tblticketreplies.name"));
        $data->isAdminReply = false;
        if ($lastReplyData) {
            $data->lastReplyUser = $lastReplyData->admin ?: $lastReplyData->contact ?: $lastReplyData->client ?: $lastReplyData->name;
            if ($lastReplyData->admin) {
                $data->isAdminReply = true;
            }
        } else {
            $data->lastReplyUser = $data->admin ?: $data->contact ?: $data->client ?: $data->name;
            if ($data->admin) {
                $data->isAdminReply = true;
            }
        }
        $data->lastreply = fromMySQLDate($data->lastreply, true);
        $data->statusColour = $this->getStatusColour($data->status);
        $data->statusTextColour = $this->getContrastYIQ(substr($data->statusColour, 1));
        $data->userDetails = $data->contact ? $data->contact . " (" . Helper::getClientLink($data->userid) . ")" : $data->client ? Helper::getClientLink($data->userid) : $data->name;
        return $data;
    }
    public function get()
    {
        $tickets = array();
        foreach ($this->project->ticketids as $key => $ticketMask) {
            try {
                $tickets[] = $this->getTicketByMask($ticketMask);
            } catch (Exception $e) {
                unset($this->project->ticketids[$key]);
                $this->project->save();
            }
        }
        return $tickets;
    }
    public function associate()
    {
        if (!$this->project->permissions()->check("Associate Tickets")) {
            throw new Exception("You don't have permission to associate tickets.");
        }
        $ticketMask = trim(\App::getFromRequest("ticketmask"));
        if (!$ticketMask) {
            throw new Exception("Ticket Mask is required");
        }
        $ticketData = $this->getTicketByMask($ticketMask);
        if (in_array($ticketMask, $this->project->ticketids)) {
            throw new Exception("This ticket is already associated with this project");
        }
        $currentTicketList = $this->ticketLinks($this->project->ticketids);
        $this->project->ticketids[] = $ticketMask;
        $this->project->save();
        $newTicketList = $this->ticketLinks($this->project->ticketids);
        $projectChanges = array(array("field" => "Ticket Associated", "oldValue" => implode(", ", $currentTicketList), "newValue" => implode(", ", $newTicketList)));
        $this->project->notify()->staff($projectChanges);
        $this->project->log()->add("Support Ticket Associated: #" . $ticketMask);
        return array("ticket" => $ticketData);
    }
    public function getDepartments()
    {
        return \WHMCS\Database\Capsule::table("tblticketdepartments")->lists("name", "id");
    }
    public function parseMarkdown()
    {
        $markup = new \WHMCS\View\Markup\Markup();
        $content = \App::get_req_var("content");
        return array("body" => "<div class=\"markdown-content\">" . $markup->transform($content, "markdown") . "</div>");
    }
    public function open()
    {
        if (!$this->project->permissions()->check("Associate Tickets")) {
            throw new Exception("You don't have permission to associate tickets.");
        }
        $userId = $this->project->userid;
        $contactId = \App::getFromRequest("contact");
        $name = !$userId ? \App::getFromRequest("name") : "";
        $email = !$userId ? \App::getFromRequest("email") : "";
        $subject = \App::getFromRequest("subject");
        $departmentId = \App::getFromRequest("department");
        $priority = \App::getFromRequest("priority");
        $message = \App::getFromRequest("message");
        $ticketDetails = localAPI("openticket", array("clientid" => $userId, "contactid" => $contactId, "name" => $name, "email" => $email, "deptid" => $departmentId, "subject" => $subject, "message" => $message, "priority" => $priority, "admin" => true));
        if ($ticketDetails["result"] != "success") {
            throw new Exception($ticketDetails["message"]);
        }
        $this->project->ticketids[] = $ticketDetails["tid"];
        $this->project->save();
        $this->project->log()->add("Support Ticket Created: #" . $ticketDetails["tid"]);
        return array("ticket" => $this->getTicketByMask($ticketDetails["tid"]), "ticketCount" => count($this->project->ticketids));
    }
    public function search()
    {
        $searchTerm = \App::getFromRequest("search");
        $tickets = array();
        try {
            $tickets[] = $this->getTicketByMask($searchTerm);
        } catch (\Exception $e) {
            $tickets = \WHMCS\Database\Capsule::table("tbltickets")->where("title", "like", "%" . $searchTerm . "%");
            if ($this->project->ticketids) {
                $tickets = $tickets->whereNotIn("tid", $this->project->ticketids);
            }
            $tickets = $tickets->get(array("id", "tid", "title", "status"));
            foreach ($tickets as $ticket) {
                $ticket->statusColour = $this->getStatusColour($ticket->status);
                $ticket->statusTextColour = $this->getContrastYIQ(substr($ticket->statusColour, 1));
            }
        }
        return array("tickets" => $tickets);
    }
    public function unlink()
    {
        $ticketMask = \App::getFromRequest("ticketmask");
        if (!$ticketMask) {
            throw new Exception("No Ticket Supplied");
        }
        if (!in_array($ticketMask, $this->project->ticketids)) {
            throw new Exception("Ticket not associated with Project");
        }
        $currentTicketList = $this->ticketLinks($this->project->ticketids);
        $ticketId = \WHMCS\Database\Capsule::table("tbltickets")->where("tid", "=", $ticketMask)->pluck("id");
        $tickets = array_flip($this->project->ticketids);
        unset($tickets[$ticketMask]);
        $this->project->ticketids = array_flip($tickets);
        $this->project->save();
        $this->project->log()->add("Support Ticket Unlinked: #" . $ticketMask);
        $newTicketList = $this->ticketLinks($this->project->ticketids);
        $projectChanges = array(array("field" => "Ticket Association Removed", "oldValue" => implode(", ", $currentTicketList), "newValue" => implode(", ", $newTicketList)));
        $this->project->notify()->staff($projectChanges);
        return array("ticketId" => $ticketId, "ticketCount" => count($this->project->ticketids));
    }
    protected function getStatusColour($status)
    {
        if (!$this->statusColours) {
            $this->statusColours = \WHMCS\Database\Capsule::table("tblticketstatuses")->lists("color", "title");
        }
        return $this->statusColours[$status] ?: "#F0AD4E";
    }
    protected function getContrastYIQ($hexColour)
    {
        $r = hexdec(substr($hexColour, 0, 2));
        $g = hexdec(substr($hexColour, 2, 2));
        $b = hexdec(substr($hexColour, 4, 2));
        $yiq = ($r * 299 + $g * 587 + $b * 114) / 1000;
        return 128 <= $yiq ? "black" : "white";
    }
    public function ticketLinks(array $ticketIds)
    {
        $systemUrl = \App::getSystemURL();
        $adminFolder = \App::get_admin_folder_name();
        $ticketList = array();
        $tickets = \WHMCS\Database\Capsule::table("tbltickets")->whereIn("tid", $ticketIds)->get();
        foreach ($tickets as $ticket) {
            $ticketLink = $systemUrl . $adminFolder . DIRECTORY_SEPARATOR . "supporttickets.php?action=viewticket&id=" . $ticket->id;
            $ticketList[] = "<a href=\"" . $ticketLink . "\">" . "#" . $ticket->tid . "</a>";
        }
        return $ticketList;
    }
}

?>