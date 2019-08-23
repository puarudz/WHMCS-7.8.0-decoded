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
if (!function_exists("saveCustomFields")) {
    require ROOTDIR . "/includes/customfieldfunctions.php";
}
if (!function_exists("openNewTicket")) {
    require ROOTDIR . "/includes/ticketfunctions.php";
}
$useMarkdown = (bool) (int) App::getFromRequest("markdown");
$from = array();
$clientid = (int) App::getFromRequest("clientid");
$contactid = (int) App::getFromRequest("contactid");
$name = (string) App::getFromRequest("name");
$email = (string) App::getFromRequest("email");
$deptid = (int) App::getFromRequest("deptid");
$subject = (string) App::getFromRequest("subject");
$message = (string) App::getFromRequest("message");
$priority = (string) App::getFromRequest("priority");
$serviceid = (string) App::getFromRequest("serviceid");
$domainid = (int) App::getFromRequest("domainid");
$customfields = (string) App::getFromRequest("customfields");
if ($customfields) {
    $customfields = base64_decode($customfields);
    $customfields = safe_unserialize($customfields);
}
if (!is_array($customfields)) {
    $customfields = array();
}
if ($clientid) {
    $result = select_query("tblclients", "id", array("id" => $clientid));
    $data = mysql_fetch_array($result);
    if (!$data["id"]) {
        $apiresults = array("result" => "error", "message" => "Client ID Not Found");
        return NULL;
    }
    if ($contactid) {
        $result = select_query("tblcontacts", "id", array("id" => $contactid, "userid" => $clientid));
        $data = mysql_fetch_array($result);
        if (!$data["id"]) {
            $apiresults = array("result" => "error", "message" => "Contact ID Not Found");
            return NULL;
        }
    }
    $from = array("name" => "", "email" => "");
} else {
    if (!$name || !$email) {
        $apiresults = array("result" => "error", "message" => "Name and email address are required if not a client");
        return NULL;
    }
    $from = array("name" => $name, "email" => $email);
}
$result = select_query("tblticketdepartments", "", array("id" => $deptid));
$data = mysql_fetch_array($result);
$deptid = $data["id"];
if (!$deptid) {
    $apiresults = array("result" => "error", "message" => "Department ID not found");
} else {
    if (!$subject) {
        $apiresults = array("result" => "error", "message" => "Subject is required");
    } else {
        if (!$message) {
            $apiresults = array("result" => "error", "message" => "Message is required");
        } else {
            if (!$priority || !in_array($priority, array("Low", "Medium", "High"))) {
                $priority = "Low";
            }
            if ($serviceid) {
                if (is_numeric($serviceid) || substr($serviceid, 0, 1) == "S") {
                    $result = select_query("tblhosting", "id", array("id" => $serviceid, "userid" => $clientid));
                    $data = mysql_fetch_array($result);
                    if (!$data["id"]) {
                        $apiresults = array("result" => "error", "message" => "Service ID Not Found");
                        return NULL;
                    }
                    $serviceid = "S" . $data["id"];
                } else {
                    $serviceid = substr($serviceid, 1);
                    $result = select_query("tbldomains", "id", array("id" => $serviceid, "userid" => $clientid));
                    $data = mysql_fetch_array($result);
                    if (!$data["id"]) {
                        $apiresults = array("result" => "error", "message" => "Service ID Not Found");
                        return NULL;
                    }
                    $serviceid = "D" . $data["id"];
                }
            }
            if ($domainid) {
                $result = select_query("tbldomains", "id", array("id" => $domainid, "userid" => $clientid));
                $data = mysql_fetch_array($result);
                if (!$data["id"]) {
                    $apiresults = array("result" => "error", "message" => "Domain ID Not Found");
                    return NULL;
                }
                $serviceid = "D" . $data["id"];
            }
            $treatAsAdmin = $whmcs->getFromRequest("admin") ? true : false;
            $validationData = array("clientId" => $clientid, "contactId" => $contactid, "name" => $name, "email" => $email, "isAdmin" => $treatAsAdmin, "departmentId" => $deptid, "subject" => $subject, "message" => $message, "priority" => $priority, "relatedService" => $serviceid, "customfields" => $customfields);
            $ticketOpenValidateResults = run_hook("TicketOpenValidation", $validationData);
            if (is_array($ticketOpenValidateResults)) {
                $hookErrors = array();
                foreach ($ticketOpenValidateResults as $hookReturn) {
                    if (is_string($hookReturn) && ($hookReturn = trim($hookReturn))) {
                        $hookErrors[] = $hookReturn;
                    }
                }
                if ($hookErrors) {
                    $apiresults = array("result" => "error", "message" => implode(". ", $hookErrors));
                    return NULL;
                }
            }
            if ($attachment = App::getFromRequest("attachments")) {
                if (!is_array($attachment)) {
                    $attachment = json_decode(base64_decode($attachment), true);
                }
                if (is_array($attachment)) {
                    $attachments = saveTicketAttachmentsFromApiCall($attachment);
                }
            } else {
                $attachments = uploadTicketAttachments();
            }
            $noemail = "";
            $ticketdata = openNewTicket($clientid, $contactid, $deptid, $subject, $message, $priority, $attachments, $from, $serviceid, $cc, $noemail, $treatAsAdmin, $useMarkdown);
            if ($customfields) {
                saveCustomFields($ticketdata["ID"], $customfields, "support", true);
            }
            $apiresults = array("result" => "success", "id" => $ticketdata["ID"], "tid" => $ticketdata["TID"], "c" => $ticketdata["C"]);
        }
    }
}

?>