<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Mail\Entity;

class Support extends \WHMCS\Mail\Emailer
{
    protected function getEntitySpecificMergeData($ticketId, $extra)
    {
        if (substr($this->message->getTemplateName(), strlen("Bounce Message") * -1) == "Bounce Message" && (isset($extra["clientTicket"]) && $extra["clientTicket"] == false || !isset($extra["clientTicket"]))) {
            list($name, $email) = $extra;
            $this->message->addRecipient("to", $email, $name);
            $this->isNonClientEmail = true;
            $email_merge_fields["client_name"] = $name;
            $email_merge_fields["client_first_name"] = $name;
            $email_merge_fields["client_last_name"] = "";
            $email_merge_fields["client_email"] = $email;
        } else {
            $result = select_query("tbltickets", "", array("id" => $ticketId));
            $data = mysql_fetch_array($result);
            $id = $data["id"];
            if (!$id) {
                throw new \WHMCS\Exception("Invalid ticket id provided");
            }
            $deptid = $data["did"];
            $tid = $data["tid"];
            $ticketcc = $data["cc"];
            $c = $data["c"];
            $userid = $data["userid"];
            $contactid = $data["contactid"];
            $name = $data["name"];
            $email = $data["email"];
            $date = $data["date"];
            $title = $data["title"];
            $tmessage = $data["message"];
            $status = $data["status"];
            $urgency = $data["urgency"];
            $attachment = $data["attachment"];
            $editor = $data["editor"];
            if ($ticketcc) {
                $ticketcc = explode(",", $ticketcc);
                foreach ($ticketcc as $ccaddress) {
                    $this->message->addRecipient("cc", $ccaddress);
                }
            }
            if ($userid) {
                $this->setRecipient($userid, $contactid);
            } else {
                if ($sessionLanguage = \WHMCS\Session::get("Language")) {
                    swapLang($sessionLanguage);
                }
            }
            $urgency = \Lang::trans("supportticketsticketurgency" . strtolower($urgency));
            if (!function_exists("getStatusColour")) {
                require_once ROOTDIR . "/includes/ticketfunctions.php";
            }
            $status = getStatusColour($status);
            $result = select_query("tblticketdepartments", "", array("id" => $deptid));
            $data = mysql_fetch_array($result);
            $this->message->setFromName(\WHMCS\Config\Setting::getValue("CompanyName") . " " . $data["name"]);
            $this->message->setFromEmail($data["email"]);
            $departmentname = $data["name"];
            $contentType = "ticket_msg";
            $replyid = 0;
            if ($extra && is_int($extra)) {
                $result = select_query("tblticketreplies", "", array("id" => $extra));
                $data = mysql_fetch_array($result);
                $replyid = $data["id"];
                $tmessage = $data["message"];
                $attachment = $data["attachment"];
                $editor = $data["editor"];
                $contentType = "ticket_reply";
            }
            $markup = new \WHMCS\View\Markup\Markup();
            $markupFormat = $markup->determineMarkupEditor($contentType, $editor);
            $includeAttachments = in_array($this->message->getTemplateName(), array("Support Ticket Opened by Admin", "Support Ticket Reply"));
            if ($includeAttachments && $attachment) {
                $storage = \Storage::ticketAttachments();
                $attachment = explode("|", $attachment);
                foreach ($attachment as $file) {
                    $this->message->addStringAttachment(substr($file, 7), $storage->read($file));
                }
            }
            $date = fromMySQLDate($date, 0, 1);
            if ($this->message->getTemplateName() != "Support Ticket Feedback Request") {
                $this->message->setSubject("[Ticket ID: {\$ticket_id}] {\$ticket_subject}");
            }
            $tmessage = $markup->transform($tmessage, $markupFormat, true);
            $kbarticles = getKBAutoSuggestions($tmessage);
            $kb_auto_suggestions = "";
            $sysurl = \App::getSystemURL();
            foreach ($kbarticles as $kbarticle) {
                $kb_auto_suggestions .= "<a href=\"" . $sysurl . "knowledgebase.php?action=displayarticle&id=" . $kbarticle["id"] . "\" target=\"_blank\">" . $kbarticle["title"] . "</a> - " . $kbarticle["article"] . "...<br />\n";
            }
            $email_merge_fields = array();
            $email_merge_fields["ticket_id"] = $tid;
            $email_merge_fields["ticket_reply_id"] = $replyid;
            $email_merge_fields["ticket_department"] = $departmentname;
            $email_merge_fields["ticket_date_opened"] = $date;
            $email_merge_fields["ticket_subject"] = $title;
            $email_merge_fields["ticket_message"] = $tmessage;
            $email_merge_fields["ticket_status"] = $status;
            $email_merge_fields["ticket_priority"] = $urgency;
            $email_merge_fields["ticket_url"] = $sysurl . "viewticket.php?tid=" . $tid . "&c=" . $c;
            $email_merge_fields["ticket_link"] = "<a href=\"" . $sysurl . "viewticket.php?tid=" . $tid . "&c=" . $c . "\">" . $sysurl . "viewticket.php?tid=" . $tid . "&c=" . $c . "</a>";
            $email_merge_fields["ticket_auto_close_time"] = \WHMCS\Config\Setting::getValue("CloseInactiveTickets");
            $email_merge_fields["ticket_kb_auto_suggestions"] = $kb_auto_suggestions;
            if ($userid == "0") {
                $this->isNonClientEmail = true;
                $this->message->addRecipient("to", $email, $name);
                $email_merge_fields["client_name"] = $name;
                $email_merge_fields["client_first_name"] = $name;
                $email_merge_fields["client_last_name"] = "";
                $email_merge_fields["client_email"] = $email;
            }
        }
        $this->massAssign($email_merge_fields);
    }
}

?>