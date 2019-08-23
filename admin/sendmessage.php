<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Mass Mail", false);
$aInt->title = $aInt->lang("sendmessage", "sendmessagetitle");
$aInt->sidebar = "clients";
$aInt->icon = "massmail";
ob_start();
$massmailquery = $query = $safeStoredQuery = $queryMadeFromEmailType = $token = NULL;
$userInput_massmailquery = $whmcs->get_req_var("massmailquery");
$queryMgr = new WHMCS\Token\Query("Admin.Massmail");
$preaction = $whmcs->getFromRequest("preaction");
$showform = false;
$errors = array();
if (!$queryMgr->isValidTokenFormat($userInput_massmailquery)) {
    $userInput_massmailquery = NULL;
}
$action = $whmcs->getFromRequest("action");
if ($preaction == "preview") {
    $action = "";
    check_token("WHMCS.admin.default");
    WHMCS\Mail\Template::where("name", "=", "Mass Mail Template")->delete();
    if ($type == "addon") {
        $type = "product";
    }
    $template = new WHMCS\Mail\Template();
    $template->type = $type;
    $template->name = "Mass Mail Template";
    $template->subject = WHMCS\Input\Sanitize::decode($subject);
    $template->message = WHMCS\Input\Sanitize::decode($message);
    $template->fromName = "";
    $template->fromEmail = "";
    $template->copyTo = array();
    $template->blindCopyTo = array();
    $template->disabled = false;
    $template->custom = false;
    $template->plaintext = false;
    $safeStoredQuery = $queryMgr->getQuery($queryMgr->getTokenValue());
    $relatedId = NULL;
    if ($massmail && $safeStoredQuery) {
        $massmailquery = $safeStoredQuery;
        $result = full_query($massmailquery . " LIMIT 0,1");
        $data = mysql_fetch_array($result);
        $relatedId = isset($data["id"]) ? $data["id"] : 0;
    } else {
        if ($multiple) {
            $relatedId = isset($selectedclients[0]) ? $selectedclients[0] : 0;
        } else {
            $relatedId = isset($id) ? $id : 0;
        }
    }
    if ($relatedId) {
        try {
            $emailer = WHMCS\Mail\Emailer::factoryByTemplate($template, $relatedId);
            echo $emailer->preview()->getBodyWithoutCSS();
        } catch (Exception $e) {
        }
    } else {
        echo "No related entities found to preview message. Unable to preview.";
    }
    throw new WHMCS\Exception\ProgramExit();
}
if ($action == "send") {
    check_token("WHMCS.admin.default");
    $save = $whmcs->getFromRequest("save");
    $savename = $whmcs->getFromRequest("savename");
    $message = $whmcs->getFromRequest("message");
    $subject = $whmcs->getFromRequest("subject");
    $fromemail = $whmcs->getFromRequest("fromemail");
    $cc = explode(",", $whmcs->getFromRequest("cc"));
    $bcc = explode(",", $whmcs->getFromRequest("bcc"));
    if (!$step) {
        if (!$message) {
            $errors[] = AdminLang::trans("sendmessage.validationerrormsg");
        }
        if (!$subject) {
            $errors[] = AdminLang::trans("sendmessage.validationerrorsub");
        }
        if (!$fromemail) {
            $errors[] = AdminLang::trans("sendmessage.validationerroremail");
        }
        if (!$fromname) {
            $errors[] = AdminLang::trans("sendmessage.validationerrorname");
        }
        if ($save == "on" && !$savename) {
            $errors[] = AdminLang::trans("sendmessage.noSaveNameEntered");
        } else {
            if ($save == "on" && WHMCS\Mail\Template::where("name", "=", $savename)->first()) {
                $errors[] = AdminLang::trans("sendmessage.uniqueSaveNameRequired");
            }
        }
    }
    if ($errors) {
        $showform = true;
    } else {
        $done = false;
        $additionalMergeFields = array();
        if ($type == "addon") {
            $type = "product";
            $additionalMergeFields["addonemail"] = true;
        }
        if ($save == "on") {
            $template = new WHMCS\Mail\Template();
            $template->type = $type;
            $template->name = $savename;
            $template->subject = WHMCS\Input\Sanitize::decode($subject);
            $template->message = WHMCS\Input\Sanitize::decode($message);
            $template->fromName = $fromname;
            $template->fromEmail = $fromemail;
            $template->copyTo = $cc;
            $template->blindCopyTo = $cc;
            $template->custom = true;
            $template->save();
            echo "<p>" . $aInt->lang("sendmessage", "msgsavedsuccess") . "</p>";
        }
        if (!$step) {
            WHMCS\Mail\Template::where("name", "=", "Mass Mail Template")->delete();
            $template = new WHMCS\Mail\Template();
            $template->type = $type;
            $template->name = "Mass Mail Template";
            $template->subject = WHMCS\Input\Sanitize::decode($subject);
            $template->message = WHMCS\Input\Sanitize::decode($message);
            $template->fromName = $fromname;
            $template->fromEmail = $fromemail;
            $template->copyTo = $cc;
            $template->blindCopyTo = $bcc;
            $template->save();
            $_SESSION["massmail"]["massmailamount"] = $massmailamount;
            $_SESSION["massmail"]["massmailinterval"] = $massmailinterval;
            $attachments = array();
            foreach (WHMCS\File\Upload::getUploadedFiles("attachments") as $uploadedFile) {
                try {
                    $filename = $uploadedFile->storeAsEmailAttachment();
                    $attachments[] = array("path" => Storage::emailAttachments()->getAdapter()->getPathPrefix() . $filename, "filename" => $filename, "displayname" => $uploadedFile->getCleanName());
                } catch (Exception $e) {
                    $aInt->gracefulExit("Could not save file: " . $e->getMessage());
                }
            }
            $_SESSION["massmail"]["attachments"] = $attachments;
            $step = 0;
        }
        $mail_attachments = array();
        if (isset($_SESSION["massmail"]["attachments"])) {
            foreach ($_SESSION["massmail"]["attachments"] as $parts) {
                $mail_attachments[] = array("displayname" => $parts["displayname"], "path" => $parts["path"]);
            }
        }
        if ($massmail && ($safeStoredQuery = $queryMgr->getQuery($queryMgr->getTokenValue()))) {
            $massmailquery = $safeStoredQuery;
            if ($emailoptout || WHMCS\Session::get("massmailemailoptout")) {
                WHMCS\Session::set("massmailemailoptout", true);
                if (WHMCS\Marketing\EmailSubscription::isUsingOptInField()) {
                    $thisCriteria = "marketing_emails_opt_in = '1'";
                } else {
                    $thisCriteria = "emailoptout = '0'";
                }
                $massmailquery .= " AND tblclients." . $thisCriteria;
            }
            $sentids = $_SESSION["massmail"]["sentids"];
            $massmailamount = (int) $_SESSION["massmail"]["massmailamount"];
            $massmailinterval = (int) $_SESSION["massmail"]["massmailinterval"];
            if (!$massmailamount) {
                $massmailamount = 25;
            }
            if (!$massmailinterval) {
                $massmailinterval = 30;
            }
            $result = full_query($massmailquery);
            $totalemails = mysql_num_rows($result);
            $totalsteps = ceil($totalemails / $massmailamount);
            $esttotaltime = ($totalsteps - ($step + 1)) * $massmailinterval;
            infoBox($aInt->lang("sendmessage", "massmailqueue"), $totalemails . $aInt->lang("sendmessage", "massmailspart1") . ($step + 1) . $aInt->lang("sendmessage", "massmailspart2") . $totalsteps . $aInt->lang("sendmessage", "massmailspart3") . $esttotaltime . $aInt->lang("sendmessage", "massmailspart4"));
            echo $infobox;
            $result = full_query($massmailquery . " LIMIT " . (int) ($step * $massmailamount) . "," . (int) $massmailamount);
            ob_start();
            while ($data = mysql_fetch_array($result)) {
                if ($data["aid"]) {
                    $additionalMergeFields["addonid"] = $data["aid"];
                }
                if ($sendforeach || !$sendforeach && !in_array($data["userid"], $sentids)) {
                    sendMessage("Mass Mail Template", $data["id"], $additionalMergeFields, true, $mail_attachments);
                    $sentids[] = $data["userid"];
                } else {
                    echo "<li>" . $aInt->lang("sendmessage", "skippedduplicate") . $data["userid"] . "<br>";
                }
            }
            $_SESSION["massmail"]["sentids"] = $sentids;
            $content = ob_get_contents();
            ob_end_clean();
            echo "<ul>" . str_replace(array("<p>", "</p>"), array("<li>", "</li>"), $content) . "</ul>";
            $totalsent = $step * $massmailamount + $massmailamount;
            if ($totalemails <= $totalsent) {
                $done = true;
            } else {
                $massmaillink = "sendmessage.php?action=send&sendforeach=" . $sendforeach . "&massmail=1&step=" . ($step + 1) . generate_token("link");
                echo "<p><a href=\"" . $massmaillink . "\">" . $aInt->lang("sendmessage", "forcenextbatch") . "</a></p><meta http-equiv=\"refresh\" content=\"" . $massmailinterval . ";url=" . $massmaillink . "\">";
            }
        } else {
            if ($multiple) {
                foreach ($selectedclients as $selectedclient) {
                    $skipemail = false;
                    $checkValue = true;
                    if ($emailoptout) {
                        if (WHMCS\Marketing\EmailSubscription::isUsingOptInField()) {
                            $field = "marketing_emails_opt_in";
                            $checkValue = false;
                        } else {
                            $field = "emailoptout";
                            $checkValue = true;
                        }
                        if ($type == "general") {
                            $skipemail = (bool) (int) get_query_val("tblclients", $field, array("id" => $selectedclient));
                        } else {
                            if ($type == "product") {
                                $skipemail = (bool) (int) get_query_val("tblhosting", $field, array("tblhosting.id" => $selectedclient), "", "", "", "tblclients ON tblclients.id=tblhosting.userid");
                            } else {
                                if ($type == "domain") {
                                    $skipemail = (bool) (int) get_query_val("tbldomains", $field, array("tbldomains.id" => $selectedclient), "", "", "", "tblclients ON tblclients.id=tbldomains.userid");
                                } else {
                                    if ($type == "affiliate") {
                                        $skipemail = (bool) (int) get_query_val("tblaffiliates", $field, array("tblaffiliates.id" => $selectedclient), "", "", "", "tblclients ON tblclients.id=tblaffiliates.clientid");
                                    }
                                }
                            }
                        }
                    }
                    if ($skipemail === $checkValue) {
                        echo "<p>Email Skipped for ID " . $selectedclient . " due to Marketing Email Opt-Out</p>";
                    } else {
                        sendMessage("Mass Mail Template", $selectedclient, "", true, $mail_attachments);
                    }
                    $done = true;
                }
            } else {
                sendMessage("Mass Mail Template", $id, "", true, $mail_attachments);
                $done = true;
            }
        }
        if ($done) {
            echo "<p><b>" . $aInt->lang("sendmessage", "sendingcompleted") . "</b></p>";
            WHMCS\Mail\Template::where("name", "=", "Mass Mail Template")->delete();
            foreach ($_SESSION["massmail"]["attachments"] as $parts) {
                try {
                    Storage::emailAttachments()->deleteAllowNotPresent($parts["filename"]);
                } catch (Exception $e) {
                    $aInt->gracefulExit("Could not delete file: " . htmlentities($e->getMessage()));
                }
            }
            unset($_SESSION["massmail"]);
        }
    }
} else {
    $showform = true;
}
if ($showform) {
    if (!$errors) {
        unset($_SESSION["massmail"]);
    }
    $todata = array();
    $query = "";
    if (!$type) {
        $type = "general";
    }
    $queryMadeFromEmailType = "";
    if ($type == "massmail") {
        $clientstatus = db_build_in_array($clientstatus);
        $clientgroup = db_build_in_array($clientgroup);
        $clientcountry = db_build_in_array($clientcountry, true);
        $clientlanguage = db_build_in_array($clientlanguage, true);
        $productids = db_build_in_array($productids);
        $productstatus = db_build_in_array($productstatus);
        $server = db_build_in_array($server);
        $addonids = db_build_in_array($addonids);
        $addonstatus = db_build_in_array($addonstatus);
        $domainstatus = db_build_in_array($domainstatus);
        if ($emailtype == "General") {
            $type = "general";
            $query = "SELECT id,id AS userid,tblclients.firstname,tblclients.lastname,tblclients.email FROM tblclients WHERE id!=''";
            if ($clientstatus) {
                $query .= " AND tblclients.status IN (" . $clientstatus . ")";
            }
            if ($clientgroup) {
                $query .= " AND tblclients.groupid IN (" . $clientgroup . ")";
            }
            if ($clientcountry) {
                $query .= " AND tblclients.country IN (" . $clientcountry . ")";
            }
            if ($clientlanguage) {
                $query .= " AND tblclients.language IN (" . $clientlanguage . ")";
            }
            if (is_array($customfield)) {
                foreach ($customfield as $k => $v) {
                    if ($v) {
                        if ($v == "cfon") {
                            $v = "on";
                        }
                        if ($v == "cfoff") {
                            $query .= " AND ((SELECT value FROM tblcustomfieldsvalues WHERE fieldid='" . db_escape_string($k) . "' AND relid=tblclients.id LIMIT 1)='' OR (SELECT value FROM tblcustomfieldsvalues WHERE fieldid='" . db_escape_string($k) . "' AND relid=tblclients.id LIMIT 1) IS NULL)";
                        } else {
                            $query .= " AND (SELECT value FROM tblcustomfieldsvalues WHERE fieldid='" . db_escape_string($k) . "' AND relid=tblclients.id LIMIT 1)='" . db_escape_string($v) . "'";
                        }
                    }
                }
            }
        } else {
            if ($emailtype == "Product/Service") {
                $type = "product";
                $query = "SELECT tblhosting.id,tblhosting.userid,tblhosting.domain,tblclients.firstname,tblclients.lastname,tblclients.email FROM tblhosting INNER JOIN tblclients ON tblclients.id=tblhosting.userid INNER JOIN tblproducts ON tblproducts.id=tblhosting.packageid WHERE tblhosting.id!=''";
                if ($productids) {
                    $query .= " AND tblproducts.id IN (" . $productids . ")";
                }
                if ($productstatus) {
                    $query .= " AND tblhosting.domainstatus IN (" . $productstatus . ")";
                }
                if ($server) {
                    $query .= " AND tblhosting.server IN (" . $server . ")";
                }
                if ($clientstatus) {
                    $query .= " AND tblclients.status IN (" . $clientstatus . ")";
                }
                if ($clientgroup) {
                    $query .= " AND tblclients.groupid IN (" . $clientgroup . ")";
                }
                if ($clientcountry) {
                    $query .= " AND tblclients.country IN (" . $clientcountry . ")";
                }
                if ($clientlanguage) {
                    $query .= " AND tblclients.language IN (" . $clientlanguage . ")";
                }
                if (is_array($customfield)) {
                    foreach ($customfield as $k => $v) {
                        if ($v) {
                            $query .= " AND (SELECT value FROM tblcustomfieldsvalues WHERE fieldid='" . db_escape_string($k) . "' AND relid=tblclients.id LIMIT 1)='" . db_escape_string($v) . "'";
                        }
                    }
                }
            } else {
                if ($emailtype == "Addon") {
                    $type = "addon";
                    $query = "        SELECT tblhosting.id, tblhosting.userid, tblhosting.domain, tblclients.firstname,\n                tblclients.lastname, tblclients.email, tblhostingaddons.id as aid\n                FROM tblhosting\n                INNER JOIN tblclients ON tblclients.id=tblhosting.userid\n                INNER JOIN tblhostingaddons ON tblhostingaddons.hostingid = tblhosting.id\n                WHERE tblhostingaddons.id!=''";
                    if ($addonids) {
                        $query .= " AND tblhostingaddons.addonid IN (" . $addonids . ")";
                    }
                    if ($addonstatus) {
                        $query .= " AND tblhostingaddons.status IN (" . $addonstatus . ")";
                    }
                    if ($clientstatus) {
                        $query .= " AND tblclients.status IN (" . $clientstatus . ")";
                    }
                    if ($clientgroup) {
                        $query .= " AND tblclients.groupid IN (" . $clientgroup . ")";
                    }
                    if ($clientcountry) {
                        $query .= " AND tblclients.country IN (" . $clientcountry . ")";
                    }
                    if ($clientlanguage) {
                        $query .= " AND tblclients.language IN (" . $clientlanguage . ")";
                    }
                    if (is_array($customfield)) {
                        foreach ($customfield as $k => $v) {
                            if ($v) {
                                $query .= " AND (SELECT value FROM tblcustomfieldsvalues WHERE fieldid='" . db_escape_string($k) . "' AND relid=tblclients.id LIMIT 1)='" . db_escape_string($v) . "'";
                            }
                        }
                    }
                } else {
                    if ($emailtype == "Domain") {
                        $type = "domain";
                        $query = "SELECT tbldomains.id,tbldomains.userid,tbldomains.domain,tblclients.firstname,tblclients.lastname,tblclients.email FROM tbldomains INNER JOIN tblclients ON tblclients.id=tbldomains.userid WHERE tbldomains.id!=''";
                        if ($domainstatus) {
                            $query .= " AND tbldomains.status IN (" . $domainstatus . ")";
                        }
                        if ($clientstatus) {
                            $query .= " AND tblclients.status IN (" . $clientstatus . ")";
                        }
                        if ($clientgroup) {
                            $query .= " AND tblclients.groupid IN (" . $clientgroup . ")";
                        }
                        if ($clientcountry) {
                            $query .= " AND tblclients.country IN (" . $clientcountry . ")";
                        }
                        if ($clientlanguage) {
                            $query .= " AND tblclients.language IN (" . $clientlanguage . ")";
                        }
                        if (is_array($customfield)) {
                            foreach ($customfield as $k => $v) {
                                if ($v) {
                                    $query .= " AND (SELECT value FROM tblcustomfieldsvalues WHERE fieldid='" . db_escape_string($k) . "' AND relid=tblclients.id LIMIT 1)='" . db_escape_string($v) . "'";
                                }
                            }
                        }
                    }
                }
            }
        }
        $queryMadeFromEmailType = $query;
    }
    if ($queryMadeFromEmailType || $userInput_massmailquery) {
        if ($queryMadeFromEmailType) {
            $massmailquery = $queryMadeFromEmailType;
        } else {
            if (!$queryMadeFromEmailType && $queryMgr->isValidTokenFormat($userInput_massmailquery)) {
                $massmailquery = $queryMgr->getQuery($userInput_massmailquery);
            } else {
                $massmailquery = "";
            }
        }
        $useridsdone = array();
        $result = full_query($massmailquery);
        while ($data = mysql_fetch_array($result)) {
            if ($sendforeach || !$sendforeach && !in_array($data["userid"], $useridsdone)) {
                $temptodata = (string) $data["firstname"] . " " . $data["lastname"];
                if ($data["domain"]) {
                    $temptodata .= " - " . $data["domain"];
                }
                $temptodata .= " &lt;" . $data["email"] . "&gt;";
                $todata[] = $temptodata;
                $useridsdone[] = $data["userid"];
            }
        }
    } else {
        if ($multiple) {
            if ($type == "general") {
                foreach ($selectedclients as $id) {
                    $result = select_query("tblclients", "", array("id" => $id));
                    $data = mysql_fetch_array($result);
                    $todata[] = (string) $data["firstname"] . " " . $data["lastname"] . " &lt;" . $data["email"] . "&gt;";
                }
            } else {
                if ($type == "product") {
                    foreach ($selectedclients as $id) {
                        $result = select_query("tblhosting", "tblclients.firstname,tblclients.lastname,tblclients.email,tblhosting.domain", array("tblhosting.id" => $id), "", "", "", "tblclients ON tblclients.id=tblhosting.userid");
                        $data = mysql_fetch_array($result);
                        $todata[] = (string) $data["firstname"] . " " . $data["lastname"] . " - " . $data["domain"] . " &lt;" . $data["email"] . "&gt;";
                    }
                } else {
                    if ($type == "domain") {
                        foreach ($selectedclients as $id) {
                            $result = select_query("tbldomains", "tblclients.firstname,tblclients.lastname,tblclients.email,tbldomains.domain", array("tbldomains.id" => $id), "", "", "", "tblclients ON tblclients.id=tbldomains.userid");
                            $data = mysql_fetch_array($result);
                            $todata[] = (string) $data["firstname"] . " " . $data["lastname"] . " - " . $data["domain"] . " &lt;" . $data["email"] . "&gt;";
                        }
                    } else {
                        if ($type == "affiliate") {
                            foreach ($selectedclients as $id) {
                                $result = select_query("tblaffiliates", "tblclients.firstname,tblclients.lastname,tblclients.email", array("tblaffiliates.id" => $id), "", "", "", "tblclients ON tblclients.id=tblaffiliates.clientid");
                                $data = mysql_fetch_array($result);
                                $todata[] = (string) $data["firstname"] . " " . $data["lastname"] . " - " . $data["domain"] . " &lt;" . $data["email"] . "&gt;";
                            }
                        }
                    }
                }
            }
        } else {
            $id = (int) App::get_req_var("id");
            if ($resend) {
                $result = select_query("tblemails", "", array("id" => $emailid));
                $data = mysql_fetch_array($result);
                $id = $data["userid"];
                $subject = $data["subject"];
                $message = $data["message"];
                $message = str_replace("<p><a href=\"" . $CONFIG["Domain"] . "\" target=\"_blank\"><img src=\"" . $whmcs->getLogoUrlForEmailTemplate() . "\" alt=\"" . $CONFIG["CompanyName"] . "\" border=\"0\"></a></p>", "", $message);
                $message = str_replace("<p><a href=\"" . $CONFIG["Domain"] . "\" target=\"_blank\"><img src=\"" . $whmcs->getLogoUrlForEmailTemplate() . "\" alt=\"" . $CONFIG["CompanyName"] . "\" border=\"0\" /></a></p>", "", $message);
                $message = str_replace(WHMCS\Input\Sanitize::decode($CONFIG["EmailGlobalHeader"]), "", $message);
                $message = str_replace(WHMCS\Input\Sanitize::decode($CONFIG["EmailGlobalFooter"]), "", $message);
                $headerMarkerPos = strpos($message, WHMCS\Mail\Message::HEADER_MARKER);
                if ($headerMarkerPos !== false) {
                    $message = substr($message, $headerMarkerPos + strlen(WHMCS\Mail\Message::HEADER_MARKER));
                }
                $footerMarkerPos = strpos($message, WHMCS\Mail\Message::FOOTER_MARKER);
                if ($footerMarkerPos !== false) {
                    $message = substr($message, 0, $footerMarkerPos);
                }
                $styleend = strpos($message, "</style>");
                if ($styleend !== false) {
                    $message = trim(substr($message, $styleend + 8));
                }
                $type = "general";
            }
            if ($type == "general") {
                $result = select_query("tblclients", "", array("id" => $id));
                $data = mysql_fetch_array($result);
                if ($data["email"]) {
                    $todata[] = (string) $data["firstname"] . " " . $data["lastname"] . " &lt;" . $data["email"] . "&gt;";
                }
            } else {
                if ($type == "product") {
                    $result = select_query("tblclients", "tblclients.id,tblclients.firstname,tblclients.lastname,tblclients.email,tblhosting.domain", array("tblhosting.id" => $id), "", "", "", "tblhosting on tblclients.id = tblhosting.userid");
                    $data = mysql_fetch_array($result);
                    if ($data["email"]) {
                        $todata[] = (string) $data["firstname"] . " " . $data["lastname"] . " - " . $data["domain"] . " &lt;" . $data["email"] . "&gt;";
                    }
                } else {
                    if ($type == "domain") {
                        $result = select_query("tblclients", "tblclients.id,tblclients.firstname,tblclients.lastname,tblclients.email,tbldomains.domain", array("tbldomains.id" => $id), "", "", "", "tbldomains on tblclients.id = tbldomains.userid");
                        $data = mysql_fetch_array($result);
                        if ($data["email"]) {
                            $todata[] = (string) $data["firstname"] . " " . $data["lastname"] . " - " . $data["domain"] . " &lt;" . $data["email"] . "&gt;";
                        }
                    }
                }
            }
        }
    }
    $numRecipients = count($todata);
    if (!$numRecipients) {
        infoBox($aInt->lang("sendmessage", "noreceiptients"), $aInt->lang("sendmessage", "noreceiptientsdesc"));
    }
    if ($errors) {
        echo infoBox(AdminLang::trans("sendmessage.validationerrortitle"), implode("<br />", $errors));
    }
    if ($sub == "loadmessage") {
        $language = !$massmailquery && !$multiple && (int) $data["id"] ? get_query_val("tblclients", "language", array("id" => $data["id"])) : "";
        $messageName = $whmcs->get_req_var("messagename");
        $template = WHMCS\Mail\Template::where("name", "=", $messageName)->where("language", "=", $language)->get()->first();
        if (is_null($template)) {
            $template = WHMCS\Mail\Template::where("name", "=", $messageName)->get()->first();
        }
        $subject = $template->subject;
        $message = $template->message;
        $fromname = $template->fromName;
        $fromemail = $template->fromEmail;
        $plaintext = $template->plaintext;
        if ($plaintext) {
            $message = nl2br($message);
        }
    }
    echo "\n<form method=\"post\" action=\"";
    echo $whmcs->getPhpSelf();
    echo "\" name=\"frmmessage\"\n    id=\"sendmsgfrm\" enctype=\"multipart/form-data\">\n    <input type=\"hidden\" name=\"action\" value=\"send\" /> <input type=\"hidden\"\n        name=\"type\" value=\"";
    echo $type;
    echo "\" />\n";
    $token = $queryMgr->generateToken();
    $queryMgr->setQuery($token, "");
    $_SESSION["massmail"]["sentids"] = array();
    WHMCS\Session::set("massmailemailoptout", false);
    if ($massmailquery) {
        if ($queryMgr->isValidTokenFormat($massmailquery)) {
            $queryToStore = $queryMgr->getQuery($massmailquery);
        } else {
            $queryToStore = $massmailquery;
        }
        $queryMgr->setQuery($token, $queryToStore);
        echo "<input type=\"hidden\" name=\"massmailquery\" value=\"" . $token . "\">";
        echo "<input type=\"hidden\" name=\"massmail\" value=\"true\" /><input type=\"hidden\" name=\"sendforeach\" value=\"" . $sendforeach . "\" />";
    } else {
        if ($multiple) {
            echo "<input type=\"hidden\" name=\"multiple\" value=\"true\" />";
            foreach ($selectedclients as $selectedclient) {
                echo "<input type=\"hidden\" name=\"selectedclients[]\" value=\"" . $selectedclient . "\" />";
            }
        } else {
            echo "<input type=\"hidden\" name=\"id\" value=\"" . $id . "\" />";
        }
    }
    echo "\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\"\n        cellpadding=\"3\">\n        <tr>\n            <td width=\"140\" class=\"fieldlabel\">";
    echo $aInt->lang("emails", "from");
    echo "</td>\n            <td class=\"fieldarea\">\n                <input type=\"text\" class=\"form-control input-200 input-inline\" name=\"fromname\" value=\"";
    if (!$fromname) {
        echo $CONFIG["CompanyName"];
    } else {
        echo $fromname;
    }
    echo "\">\n                <input type=\"text\" name=\"fromemail\" class=\"form-control input-400 input-inline\" value=\"";
    if (!$fromemail) {
        echo $CONFIG["Email"];
    } else {
        echo $fromemail;
    }
    echo "\">\n            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">";
    echo $aInt->lang("emails", "recipients");
    echo "</td>\n            <td class=\"fieldarea\"><table cellspacing=\"0\" cellpadding=\"0\">\n                    <tr>\n                        <td>";
    echo "<select class=\"form-control\" size=\"4\" style=\"width:450px;\"><option>" . $numRecipients . " recipients matched sending criteria.";
    if (50 < $numRecipients) {
        echo " Showing first 50 only...";
    }
    echo "</option>";
    foreach ($todata as $i => $to) {
        echo "<option>" . $to . "</option>";
        if (49 < $i) {
            break;
        }
    }
    echo "</select></td>\n                        <td> &nbsp; ";
    echo $aInt->lang("sendmessage", "emailsentindividually1");
    echo "<br /> &nbsp; ";
    echo $aInt->lang("sendmessage", "emailsentindividually2");
    echo "</td>\n\n                </table></td>\n            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">CC</td>\n            <td class=\"fieldarea\">\n                <input type=\"text\" name=\"cc\" class=\"form-control input-600 input-inline\" value=\"\"> ";
    echo $aInt->lang("sendmessage", "commaseparateemails");
    echo "            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">";
    echo AdminLang::trans("sendmessage.bcc");
    echo "</td>\n            <td class=\"fieldarea\"><input type=\"text\" name=\"bcc\" class=\"form-control input-600 input-inline\" value=\"\"> ";
    echo AdminLang::trans("sendmessage.commaseparateemails");
    echo "</td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">Subject</td>\n            <td class=\"fieldarea\">\n                <input type=\"text\" name=\"subject\"  class=\"form-control\" value=\"";
    echo $subject;
    echo "\" id=\"subject\">\n            </td>\n        </tr>\n    </table>\n\n    <script langauge=\"javascript\">\nfrmmessage.subject.select();\n</script>\n\n    <textarea name=\"message\" id=\"email_msg1\" rows=\"25\" class=\"tinymce form-control\">\n        ";
    echo $message;
    echo "    </textarea>\n\n    <br />\n\n    <table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\"\n        cellpadding=\"3\">\n        <tr>\n            <td width=\"140\" class=\"fieldlabel\">";
    echo $aInt->lang("support", "attachments");
    echo "</td>\n            <td class=\"fieldarea\"><div style=\"float: right;\">\n                    <input type=\"button\"\n                        value=\"";
    echo $aInt->lang("emailtpls", "rteditor");
    echo "\"\n                        class=\"btn btn-default\" onclick=\"toggleEditor()\" />\n                </div>\n                <input type=\"file\" name=\"attachments[]\" style=\"width: 60%;\" /> <a\n                href=\"#\" id=\"addfileupload\"><img src=\"images/icons/add.png\"\n                    align=\"absmiddle\" border=\"0\" /> ";
    echo $aInt->lang("support", "addmore");
    echo "</a><br />\n            <div id=\"fileuploads\"></div></td>\n        </tr>\n";
    if ($massmailquery || $multiple) {
        echo "<tr>\n            <td class=\"fieldlabel\">";
        echo $aInt->lang("sendmessage", "marketingemail");
        echo "</td>\n            <td class=\"fieldarea\">\n                <label class=\"checkbox-inline\">\n                    <input type=\"checkbox\" id=\"emailoptout\" name=\"emailoptout\">\n                    ";
        echo $aInt->lang("sendmessage", "dontsendemailunsubscribe");
        echo "                </label>\n            </td>\n        </tr>\n";
    }
    if (checkPermission("Create/Edit Email Templates", true)) {
        echo "<tr>\n            <td class=\"fieldlabel\">";
        echo $aInt->lang("sendmessage", "savemesasge");
        echo "</td>\n            <td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"save\"";
        echo $save == "on" ? " checked" : "";
        echo "> ";
        echo $aInt->lang("sendmessage", "entersavename");
        echo ":</label>\n                <input type=\"text\" name=\"savename\" class=\"form-control input-300 input-inline\" value=\"";
        echo !empty($savename) ? $savename : "";
        echo "\">\n            </td>\n        </tr>";
    }
    if ($massmailquery) {
        echo "<tr>\n            <td class=\"fieldlabel\">";
        echo $aInt->lang("sendmessage", "massmailsettings");
        echo "</td>\n            <td class=\"fieldarea\">";
        echo $aInt->lang("sendmessage", "massmailsetting1");
        echo "                <input type=\"text\" name=\"massmailamount\" class=\"form-control input-50 input-inline\" value=\"25\" /> ";
        echo $aInt->lang("sendmessage", "massmailsetting2");
        echo "                <input type=\"text\" name=\"massmailinterval\" class=\"form-control input-50 input-inline\" value=\"30\" /> ";
        echo $aInt->lang("sendmessage", "massmailsetting3");
        echo "            </td>\n        </tr>";
    }
    echo "</table>\n\n    <div class=\"btn-container\">\n        <input type=\"button\"\n            value=\"";
    echo $aInt->lang("sendmessage", "preview");
    echo "\"\n            onclick=\"previewMsg()\" class=\"btn btn-default\" /> <input type=\"submit\"\n            value=\"";
    echo $aInt->lang("global", "sendmessage");
    echo " &raquo;\"\n            class=\"btn btn-primary\" />\n    </div>\n\n</form>\n\n";
    $aInt->richTextEditor();
    echo "<div id=\"emailoptoutinfo\">";
    infoBox($aInt->lang("sendmessage", "marketingemail"), sprintf($aInt->lang("sendmessage", "marketingemaildesc"), "{\$unsubscribe_url}"));
    echo $infobox;
    echo "</div>";
    $i = 1;
    include "mergefields.php";
    echo "\n<form method=\"post\" action=\"";
    echo $_SERVER["PHP_SELF"];
    echo "\">\n    <input type=\"hidden\" name=\"sub\" value=\"loadmessage\"> <input\n        type=\"hidden\" name=\"type\" value=\"";
    echo $type;
    echo "\">\n";
    if ($massmailquery) {
        if ($queryMgr->isValidTokenFormat($massmailquery)) {
            $queryToStore = $queryMgr->getQuery($massmailquery);
        } else {
            $queryToStore = $massmailquery;
        }
        $token = $queryMgr->generateToken();
        $queryMgr->setQuery($token, $queryToStore);
        echo "<input type=\"hidden\" name=\"massmailquery\" value=\"" . $token . "\">";
        if ($sendforeach) {
            echo "<input type=\"hidden\" name=\"sendforeach\" value=\"" . $sendforeach . "\">";
        }
    } else {
        if ($multiple) {
            echo "<input type=\"hidden\" name=\"multiple\" value=\"true\">";
            foreach ($selectedclients as $selectedclient) {
                echo "<input type=\"hidden\" name=\"selectedclients[]\" value=\"" . $selectedclient . "\">";
            }
        } else {
            echo "<input type=\"hidden\" name=\"id\" value=\"" . $id . "\">";
        }
    }
    echo "<div class=\"contentbox\">\n        <b>";
    echo $aInt->lang("sendmessage", "loadsavedmsg");
    echo ":</b> <select\n            name=\"messagename\" class=\"form-control select-inline\"><option value=\"\">";
    echo $aInt->lang("sendmessage", "choose");
    echo "...";
    $templates = WHMCS\Mail\Template::where("type", "=", "general")->where("language", "=", "")->orderBy("custom")->orderby("name")->get();
    foreach ($templates as $template) {
        echo "<option style=\"background-color: #ffffff\">" . $template->name . "</option>";
    }
    if ($type != "general") {
        $templates = WHMCS\Mail\Template::where("type", "=", $type)->where("language", "=", "")->orderBy("custom")->orderby("name")->get();
        foreach ($templates as $template) {
            echo "<option";
            if (!$template->custom) {
                echo " style=\"background-color: #efefef\"";
            }
            echo ">" . $template->name . "</option>";
        }
    }
    echo "</select> <input type=\"submit\" class=\"btn btn-default\"\n            value=\"";
    echo $aInt->lang("sendmessage", "loadMessage");
    echo "\">\n    </div>\n</form>\n\n";
    echo $aInt->modal("PreviewWindow", $aInt->lang("sendmessage", "preview"), "<div id=\"previewwndcontent\">" . $aInt->lang("global", "loading") . "</div>", array(array("title" => $aInt->lang("global", "ok"), "onclick" => "jQuery(\"#modalPreviewWindow\").modal(\"hide\");jQuery(\"#previewwndcontent\").html(\"<div id=\\\"previewwndcontent\\\">" . $aInt->lang("global", "loading", true) . "</div>\");")), "large");
    $jquerycode .= "\$(\"#addfileupload\").click(function () {\n    \$(\"#fileuploads\").append(\"<input type=\\\"file\\\" name=\\\"attachments[]\\\" style=\\\"width:70%;\\\" /><br />\");\n    return false;\n});\n\$(\"#emailoptoutinfo\").hide();\n\$(\"#emailoptout\").click(function(){\n    if (this.checked) {\n        \$(\"#emailoptoutinfo\").slideDown(\"slow\");\n    } else {\n        \$(\"#emailoptoutinfo\").slideUp(\"slow\");\n    }\n});";
    $jscode = "function previewMsg() {\n    if (jQuery(\"#email_msg1_ifr\").length === 0) {\n        alert(\"Cannot preview message while the rich-text editor is disabled - please re-enable and then try again\");\n    } else {\n        jQuery(\"#modalPreviewWindow\").modal(\"show\");\n        jQuery(\"#email_msg1\").val(tinymce.activeEditor.getContent());\n        WHMCS.http.jqClient.post(\"sendmessage.php\", jQuery(\"#sendmsgfrm\").serialize()+\"&preaction=preview\",\n        function(data){\n            if (data) {\n                jQuery(\"#previewwndcontent\").html(data);}\n            else {\n                jQuery(\"#previewwndcontent\").html(\"Syntax Error - Please check your email message for invalid template syntax or missing closing tags\");\n            }\n        });\n        return false;\n    }\n}";
}
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jquerycode = $jquerycode;
$aInt->jscode = $jscode;
$aInt->display();

?>