<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Cron\Task;

class AutoPruneTicketAttachments extends \WHMCS\Scheduling\Task\AbstractTask
{
    protected $defaultPriority = 1615;
    protected $defaultFrequency = 60;
    protected $defaultDescription = "Auto Remove Inactive Ticket Attachments";
    protected $defaultName = "Prune Ticket Attachments";
    protected $systemName = "AutoPruneTicketAttachments";
    protected $icon = "fas fa-file-minus";
    protected $successCountIdentifier = "removed";
    protected $successKeyword = "Removed";
    protected $outputs = array("removed" => array("defaultValue" => 0, "identifier" => "removed", "name" => "Removed"));
    protected $skipDailyCron = true;
    public function __invoke()
    {
        if (!function_exists("removeAttachmentsFromClosedTickets")) {
            require ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "ticketfunctions.php";
        }
        $data = removeAttachmentsFromClosedTickets((int) \WHMCS\Config\Setting::getValue("PruneTicketAttachmentsMonths"));
        $removedAttachments = $data["removed"];
        $this->output("removed")->write($removedAttachments);
        if (0 < $removedAttachments) {
            $limitHit = $data["limitHit"];
            $left = $data["left"];
            $message = "Automated Prune Ticket Attachments: " . "Processed " . $removedAttachments . " records.";
            if ($limitHit) {
                $message .= " Limit reached for a single run.";
            }
            $message .= " " . $left . " records remaining.";
            logActivity($message);
        }
        return $this;
    }
}

?>