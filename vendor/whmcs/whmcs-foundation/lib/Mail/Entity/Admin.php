<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Mail\Entity;

class Admin extends \WHMCS\Mail\Emailer
{
    protected $isNonClientEmail = true;
    public function __construct($message, $entityId, $extraParams = NULL)
    {
        parent::__construct($message, $entityId, $extraParams);
        $this->message->setFromName(\WHMCS\Config\Setting::getValue("SystemEmailsFromName"));
        $this->message->setFromEmail(\WHMCS\Config\Setting::getValue("SystemEmailsFromEmail"));
    }
    protected function getEntitySpecificMergeData($userId, $extra)
    {
        $adminUrl = \App::getSystemUrl();
        $adminUrl .= \App::get_admin_folder_name() . "/";
        $this->massAssign(array("whmcs_admin_url" => $adminUrl, "whmcs_admin_link" => "<a href=\"" . $adminUrl . "\">" . $adminUrl . "</a>"));
    }
    public function determineAdminRecipientsAndSender($to, $deptid, $adminid, $ticketnotify)
    {
        if ($deptid) {
            $result = select_query("tblticketdepartments", "name,email", array("id" => $deptid));
            $data = mysql_fetch_array($result);
            $fromEmail = $data["email"];
            $fromName = \WHMCS\Config\Setting::getValue("CompanyName") . " " . $data["name"];
            $this->message->setFromName($fromName);
            $this->message->setFromEmail($fromEmail);
        }
        if ($adminid) {
            if (is_array($adminid)) {
                $where = "tbladmins.disabled = 0 AND tbladmins.id IN (" . db_build_in_array($adminid) . ")";
            } else {
                $where = "tbladmins.disabled=0 AND tbladmins.id='" . (int) $adminid . "'";
            }
        } else {
            if (in_array($to, array("ticket_changes", "mentions"))) {
                return false;
            }
            $where = "tbladmins.disabled=0 AND tbladminroles." . db_escape_string($to) . "emails='1'";
            if ($deptid) {
                $where .= " AND tbladmins.ticketnotifications!=''";
            }
        }
        $result = select_query("tbladmins", "firstname,lastname,email,supportdepts,ticketnotifications", $where, "", "", "", "tbladminroles ON tbladminroles.id=tbladmins.roleid");
        while ($data = mysql_fetch_array($result)) {
            if ($data["email"]) {
                $adminsend = true;
                if ($ticketnotify) {
                    $ticketnotifications = $data["ticketnotifications"];
                    $ticketnotifications = explode(",", $ticketnotifications);
                    if (!$adminid && !in_array($deptid, $ticketnotifications)) {
                        $adminsend = false;
                    }
                } else {
                    if ($deptid) {
                        $supportdepts = $data["supportdepts"];
                        $supportdepts = explode(",", $supportdepts);
                        if (!$adminid && !in_array($deptid, $supportdepts)) {
                            $adminsend = false;
                        }
                    }
                }
                if ($adminsend) {
                    $this->message->addRecipient("to", $data["email"], $data["firstname"] . " " . $data["lastname"]);
                }
            }
        }
    }
}

?>