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
require "includes/customfieldfunctions.php";
require "includes/clientfunctions.php";
$pagetitle = Lang::trans("supportticketssubmitticket");
$breadcrumbnav = "<a href=\"index.php\">" . Lang::trans("globalsystemname") . "</a> > <a href=\"clientarea.php\">" . Lang::trans("clientareatitle") . "</a> > <a href=\"supporttickets.php\">" . Lang::trans("supportticketspagetitle") . "</a> > <a href=\"submitticket.php\">" . Lang::trans("supportticketssubmitticket") . "</a>";
$pageicon = "images/submitticket_big.gif";
$displayTitle = Lang::trans("navopenticket");
$tagline = "";
initialiseClientArea($pagetitle, $displayTitle, $tagline, $pageicon, $breadcrumbnav);
$action = $whmcs->get_req_var("action");
$deptid = (int) $whmcs->get_req_var("deptid");
$step = $whmcs->get_req_var("step");
$name = $whmcs->get_req_var("name");
$email = $whmcs->get_req_var("email");
$urgency = $whmcs->get_req_var("urgency");
$subject = $whmcs->get_req_var("subject");
$message = $whmcs->get_req_var("message");
$attachments = $whmcs->get_req_var("attachments");
$relatedservice = $whmcs->get_req_var("relatedservice");
$customfield = $whmcs->get_req_var("customfield");
$file_too_large = $whmcs->get_req_var("file_too_large");
if ($action == "getkbarticles") {
    $kbarticles = getKBAutoSuggestions($text);
    if (count($kbarticles)) {
        $smarty->assign("kbarticles", $kbarticles);
        echo $smarty->fetch($whmcs->getClientAreaTemplate()->getName() . "/supportticketsubmit-kbsuggestions.tpl");
    }
    exit;
}
if ($action == "getcustomfields") {
    $customfields = getCustomFields("support", $deptid, "", "", "", $customfield);
    $smarty->assign("customfields", $customfields);
    $templateName = $whmcs->getClientAreaTemplate()->getName();
    if (file_exists(ROOTDIR . "/templates/" . $templateName . "/supportticketsubmit-customfields.tpl")) {
        echo $smarty->fetch($templateName . "/supportticketsubmit-customfields.tpl");
    } else {
        if (file_exists(ROOTDIR . "/templates/" . $templateName . "/supportticketsubmit-customFields.tpl")) {
            echo $smarty->fetch($templateName . "/supportticketsubmit-customFields.tpl");
        } else {
            echo "supportticketsubmit-customfields.tpl is missing";
        }
    }
    exit;
}
if ($action == "markdown") {
    $response = new WHMCS\Http\JsonResponse();
    $templatefile = "/templates/" . $whmcs->getClientAreaTemplate()->getName() . "/markdown-guide.tpl";
    $response->setData(array("body" => processSingleTemplate($templatefile, array())));
    $response->send();
    WHMCS\Terminus::getInstance()->doExit();
} else {
    if ($action == "markdown-page") {
        $ca = new WHMCS\ClientArea();
        $ca->setPageTitle(Lang::trans("markdown.title"));
        $ca->addToBreadCrumb("index.php", Lang::trans("globalsystemname"));
        $ca->addToBreadCrumb("submitticket.php?action=markdown-page", Lang::trans("markdown.title"));
        $ca->setTemplate("markdown-guide");
        $ca->initPage();
        $ca->output();
    }
}
$recentTickets = array();
$result = select_query("tbltickets", "", array("userid" => WHMCS\Session::get("uid")), "id", "DESC", "0,5");
while ($data = mysql_fetch_array($result)) {
    $recentTickets[] = array("id" => $data["id"], "tid" => $data["tid"], "c" => $data["c"], "date" => fromMySQLDate($data["date"], 1, 1), "department" => $data["did"], "subject" => $data["title"], "status" => getStatusColour($data["status"]), "urgency" => Lang::trans("supportticketsticketurgency" . strtolower($data["urgency"])), "lastreply" => fromMySQLDate($data["lastreply"], 1, 1), "unread" => $data["clientunread"]);
}
$smartyvalues["recenttickets"] = $recentTickets;
$captcha = new WHMCS\Utility\Captcha();
$validate = new WHMCS\Validate();
if ($step == "3") {
    if (checkTicketAttachmentSize()) {
        check_token();
        if (!isset($_SESSION["uid"])) {
            $validate->validate("required", "name", "supportticketserrornoname");
            if ($validate->validate("required", "email", "supportticketserrornoemail")) {
                $validate->validate("email", "email", "clientareaerroremailinvalid");
            }
        }
        $validate->validate("required", "subject", "supportticketserrornosubject");
        $validate->validate("required", "message", "supportticketserrornomessage");
        $validate->validate("fileuploads", "attachments", "supportticketsfilenotallowed");
        $validate->validateCustomFields("support", $deptid);
        $captcha->validateAppropriateCaptcha(WHMCS\Utility\Captcha::FORM_SUBMIT_TICKET, $validate);
        if (!$validate->hasErrors()) {
            $clientid = $contactid = 0;
            if (WHMCS\Session::get("uid")) {
                $clientid = WHMCS\Session::get("uid");
                if (WHMCS\Session::get("cid")) {
                    $contactid = WHMCS\Session::get("cid");
                }
            }
            $customfields = array();
            if (is_array($customfield)) {
                $customfields = getCustomFields("support", $deptid, "", "", "", $customfield);
            }
            $validationData = array("clientId" => $clientid, "contactId" => $contactid, "name" => $name, "email" => $email, "isAdmin" => false, "departmentId" => $deptid, "subject" => $subject, "message" => $message, "priority" => $urgency, "relatedService" => $relatedservice, "customfields" => $customfields);
            $ticketOpenValidateResults = run_hook("TicketOpenValidation", $validationData);
            if (is_array($ticketOpenValidateResults)) {
                foreach ($ticketOpenValidateResults as $hookReturn) {
                    if (is_string($hookReturn) && ($hookReturn = trim($hookReturn))) {
                        $validate->addError($hookReturn);
                    }
                }
            }
        }
        if ($validate->hasErrors()) {
            $step = "2";
        }
    } else {
        if (empty($_POST)) {
            redir("file_too_large=1", "submitticket.php");
        } else {
            $step = 2;
            $file_too_large = true;
        }
    }
}
if ($file_too_large) {
    $validate->addError(Lang::trans("supportticketsuploadtoolarge"));
}
checkContactPermission("tickets");
$usingsupportmodule = false;
if (WHMCS\Config\Setting::getValue("SupportModule")) {
    if (!isValidforPath(WHMCS\Config\Setting::getValue("SupportModule"))) {
        exit("Invalid Support Module");
    }
    $supportmodulepath = "modules/support/" . WHMCS\Config\Setting::getValue("SupportModule") . "/submitticket.php";
    if (file_exists($supportmodulepath)) {
        if (!isset($_SESSION["uid"])) {
            $goto = "submitticket";
            require "login.php";
        }
        $usingsupportmodule = true;
        $templatefile = "";
        require $supportmodulepath;
        outputClientArea($templatefile);
        exit;
    }
}
if ($step == "") {
    $templatefile = "supportticketsubmit-stepone";
    $departmentCollection = WHMCS\Support\Department::where("hidden", "");
    $totaldepartments = $departmentCollection->count();
    if (!WHMCS\Config\Setting::getValue("ShowClientOnlyDepts") && !isset($_SESSION["uid"])) {
        $departmentCollection = $departmentCollection->where("clientsonly", "");
    }
    $departments = array();
    foreach ($departmentCollection->get() as $department) {
        $departments[] = array("id" => $department->id, "name" => $department->name, "description" => $department->description);
    }
    if (!$departments && $totaldepartments) {
        $goto = "submitticket";
        include "login.php";
    }
    if (count($departments) == 1) {
        redir("step=2&deptid=" . $departments[0]["id"] . ($file_too_large ? "&file_too_large=1" : ""));
    }
    $smarty->assign("departments", $departments);
    $smarty->assign("errormessage", $validate->getHTMLErrorOutput());
} else {
    if ($step == "2") {
        $templatefile = "supportticketsubmit-steptwo";
        $department = WHMCS\Support\Department::find($deptid);
        if (!$department) {
            redir("", "submitticket.php");
        }
        $deptid = $department->id;
        $deptname = $department->name;
        $clientsonly = $department->clientsOnly;
        if ($clientsonly && !$_SESSION["uid"]) {
            $templatefile = "supportticketsubmit-stepone";
            $goto = "submitticket";
            include "login.php";
        }
        $smarty->assign("deptid", $deptid);
        $smarty->assign("department", $deptname);
        $departmentCollection = WHMCS\Support\Department::enforceUserVisibilityPermissions()->orWhere("id", $deptid);
        $departments = array();
        foreach ($departmentCollection->get() as $department) {
            $departments[] = array("id" => $department->id, "name" => $department->name, "description" => $department->description);
        }
        $smarty->assign("departments", $departments);
        $clientname = "";
        $relatedservices = array();
        if (WHMCS\Session::get("uid")) {
            $clientsdetails = getClientsDetails(WHMCS\Session::get("uid"), WHMCS\Session::get("cid"));
            $clientname = $clientsdetails["firstname"] . " " . $clientsdetails["lastname"];
            $email = $clientsdetails["email"];
            $result = select_query("tblhosting", "tblhosting.id,tblhosting.domain,tblhosting.domainstatus,tblhosting.packageid,tblproducts.name as product_name", array("userid" => $_SESSION["uid"]), "domain", "ASC", "", "tblproducts ON tblproducts.id=tblhosting.packageid");
            while ($data = mysql_fetch_array($result)) {
                $productname = WHMCS\Product\Product::getProductName($data["packageid"], $data["product_name"]);
                if ($data["domain"]) {
                    $productname .= " - " . $data["domain"];
                }
                $relatedservices[] = array("id" => "S" . $data["id"], "name" => $productname, "status" => Lang::trans("clientarea" . strtolower($data["domainstatus"])));
            }
            $result = select_query("tbldomains", "", array("userid" => $_SESSION["uid"]), "domain", "ASC");
            while ($data = mysql_fetch_array($result)) {
                $relatedservices[] = array("id" => "D" . $data["id"], "name" => Lang::trans("clientareahostingdomain") . " - " . $data["domain"], "status" => Lang::trans("clientarea" . strtolower(str_replace(" ", "", $data["status"]))));
            }
        }
        $smarty->assign("name", $name);
        $smarty->assign("clientname", $clientname);
        $smarty->assign("email", $email);
        $smartyvalues["relatedservices"] = $relatedservices;
        $customfields = getCustomFields("support", $deptid, "", "", "", $customfield);
        $tickets = new WHMCS\Tickets();
        $smarty->assign("customfields", $customfields);
        $smarty->assign("allowedfiletypes", implode(", ", $tickets->getAllowedAttachments()));
        $smarty->assign("errormessage", $validate->getHTMLErrorOutput());
        $smarty->assign("urgency", $urgency);
        $smarty->assign("subject", $subject);
        $smarty->assign("message", $message);
        $smarty->assign("captcha", $captcha);
        $smarty->assign("captchaForm", WHMCS\Utility\Captcha::FORM_SUBMIT_TICKET);
        $smarty->assign("recaptchahtml", clientAreaReCaptchaHTML());
        $smarty->assign("capatacha", $captcha);
        $smarty->assign("recapatchahtml", clientAreaReCaptchaHTML());
        if (WHMCS\Config\Setting::getValue("SupportTicketKBSuggestions")) {
            $smarty->assign("kbsuggestions", true);
        }
        $locale = preg_replace("/[^a-zA-Z0-9_\\-]*/", "", Lang::getLanguageLocale());
        $locale = $locale == "locale" ? "en" : substr($locale, 0, 2);
        $smarty->assign("mdeLocale", $locale);
        $smarty->assign("loadMarkdownEditor", true);
    } else {
        if ($step == "3") {
            $userId = WHMCS\Session::get("uid");
            $contactId = WHMCS\Session::get("cid");
            $ticketDepartment = WHMCS\Support\Department::find($deptid);
            if (!$ticketDepartment || $ticketDepartment->clientsOnly && !$userId) {
                redir("", "submitticket.php");
            }
            $attachments = uploadTicketAttachments();
            $from = array();
            $from["name"] = $name;
            $from["email"] = $email;
            $message .= "\n\n----------------------------\nIP Address: " . $remote_ip;
            $cc = "";
            if ($contactId) {
                $cc = get_query_val("tblcontacts", "email", array("id" => $contactId, "userid" => $userId));
            }
            $ticketdetails = openNewTicket($userId, $contactId, $deptid, $subject, $message, $urgency, $attachments, $from, $relatedservice, $cc, false, false, true);
            saveCustomFields($ticketdetails["ID"], $customfield);
            $_SESSION["tempticketdata"] = $ticketdetails;
            redir("step=4", "submitticket.php");
        } else {
            if ($step == "4") {
                $ticketdetails = $_SESSION["tempticketdata"];
                $templatefile = "supportticketsubmit-confirm";
                $smarty->assign("tid", $ticketdetails["TID"]);
                $smarty->assign("c", $ticketdetails["C"]);
                $smarty->assign("subject", $ticketdetails["Subject"]);
            }
        }
    }
}
Menu::addContext("departmentId", $deptid);
Menu::primarySidebar("ticketSubmit");
Menu::secondarySidebar("ticketSubmit");
outputClientArea($templatefile, false, array("ClientAreaPageSubmitTicket"));

?>