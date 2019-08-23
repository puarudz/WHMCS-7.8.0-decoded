<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . "bootstrap.php";
require ROOTDIR . "/includes/adminfunctions.php";
require ROOTDIR . "/includes/ticketfunctions.php";
define("IN_CRON", true);
$transientData = WHMCS\TransientData::getInstance();
$transientData->delete("popCronComplete");
$whmcs = App::self();
$whmcsAppConfig = $whmcs->getApplicationConfig();
$cronOutput = array();
if (defined("PROXY_FILE")) {
    $cronOutput[] = WHMCS\Cron::getLegacyCronMessage();
}
$cronOutput[] = "<b>POP Import Log</b><br>Date: " . date("d/m/Y H:i:s") . "<hr>";
$ticketDepartments = Illuminate\Database\Capsule\Manager::table("tblticketdepartments")->where("host", "!=", "")->where("port", "!=", "")->where("login", "!=", "")->orderBy("order")->get();
$connectionErrors = array();
foreach ($ticketDepartments as $ticketDepartment) {
    ob_start();
    $cronOutput[] = "Host: " . $ticketDepartment->host . "<br>Email: " . $ticketDepartment->login . "<br>";
    $connectionFlags = "/pop3/notls";
    if ($ticketDepartment->port == 995) {
        $connectionFlags = "/pop3/ssl/novalidate-cert";
    }
    try {
        $connectionString = $ticketDepartment->host . ":" . $ticketDepartment->port;
        $mailbox = new WHMCS\WhmcsMailbox("{" . $connectionString . $connectionFlags . "}INBOX", $ticketDepartment->login, decrypt($ticketDepartment->password), sys_get_temp_dir(), WHMCS\Config\Setting::getValue("Charset"));
        $mailIds = $mailbox->searchMailbox();
        if (!$mailIds) {
            $cronOutput[] = "Mailbox is empty<hr>";
        } else {
            $cronOutput[] = "Email Count: " . $mailbox->countMails() . "<hr>";
        }
        foreach ($mailIds as $mailId) {
            $mail = $mailbox->getMail($mailId);
            $toEmails = array();
            $toString = $mail->toString;
            $ccEmails = array_keys($mail->cc);
            $subject = $mail->subject;
            $fromName = $mail->fromName;
            $fromEmail = $mail->fromAddress;
            if (!$fromName) {
                $fromName = $fromEmail;
            }
            $replyTo = $mail->replyTo;
            if ($replyTo) {
                $fromEmail = key($replyTo);
                $fromName = $replyTo[$fromEmail] ?: $fromEmail;
            }
            foreach (explode(",", $toString) as $toEmail) {
                if (strpos($toEmail, "<") !== false) {
                    $emailAddressesMatch = array();
                    preg_match("/<(\\S+)>/", $toEmail, $emailAddressesMatch);
                    $emailAddressesMatch = preg_grep("/</", $emailAddressesMatch, PREG_GREP_INVERT);
                    foreach ($emailAddressesMatch as $emailAddress) {
                        $toEmails[] = $emailAddress;
                    }
                } else {
                    $toEmails[] = $toEmail;
                }
            }
            $toEmails[] = $ticketDepartment->email;
            $processedCcEmails = array();
            foreach ($ccEmails as $ccEmail) {
                if (strpos($ccEmail, "<") !== false) {
                    $emailAddressesMatch = array();
                    preg_match("/<(\\S+)>/", $ccEmail, $emailAddressesMatch);
                    $emailAddressesMatch = preg_grep("/</", $emailAddressesMatch, PREG_GREP_INVERT);
                    foreach ($emailAddressesMatch as $emailAddress) {
                        $processedCcEmails[] = $emailAddress;
                    }
                } else {
                    $processedCcEmails[] = $ccEmail;
                }
            }
            $processedCcEmails = array_slice($processedCcEmails, 0, 20);
            $subject = str_replace(array("{", "}"), array("[", "]"), $mail->subject);
            $messageBody = $mail->textPlain;
            if (!$messageBody) {
                $messageBody = strip_tags($mail->textHtml);
            }
            if (!$messageBody) {
                $messageBody = "No message found.";
            }
            $messageBody = str_replace("&nbsp;", " ", $messageBody);
            $ticketAttachments = array();
            $attachments = $mail->getAttachments();
            $popAttachmentStorage = Storage::ticketAttachments();
            foreach ($attachments as $attachment) {
                $filename = $attachment->name;
                if (checkTicketAttachmentExtension($filename)) {
                    $filenameParts = explode(".", $filename);
                    $extension = end($filenameParts);
                    $filename = implode(array_slice($filenameParts, 0, -1));
                    $filename = preg_replace("/[^a-zA-Z0-9-_ ]/", "", $filename);
                    if (!$filename) {
                        $filename = "filename";
                    }
                    mt_srand(time());
                    $rand = mt_rand(100000, 999999);
                    $attachmentFilename = $rand . "_" . $filename . "." . $extension;
                    while ($popAttachmentStorage->has($attachmentFilename)) {
                        mt_srand(time());
                        $rand = mt_rand(100000, 999999);
                        $attachmentFilename = $rand . "_" . $filename . "." . $extension;
                    }
                    $ticketAttachments[] = $attachmentFilename;
                    try {
                        $popAttachmentStorage->write($attachmentFilename, file_get_contents($attachment->filePath));
                    } catch (Exception $e) {
                        $messageBody .= "\n\nAttachment " . $filename . " could not be saved.";
                    }
                } else {
                    $messageBody .= "\n\nAttachment " . $filename . " blocked - file type not allowed.";
                }
                unlink($attachment->filePath);
            }
            $attachmentList = implode("|", $ticketAttachments);
            processPoppedTicket(implode(",", $toEmails), $fromName, $fromEmail, $subject, $messageBody, $attachmentList, $processedCcEmails);
            $mailbox->deleteMail($mailId);
        }
        $mailbox->expungeDeletedMails();
    } catch (Exception $e) {
        $connectionErrors[] = array("department" => $ticketDepartment, "error" => $e->getMessage());
        $cronOutput[] = $e->getMessage();
    }
    $content = ob_get_contents();
    ob_end_clean();
    $cronOutput[] = $content;
}
if (0 < count($connectionErrors)) {
    $connectionErrorsString = "";
    foreach ($connectionErrors as $connectionError) {
        $connectionErrorsString .= "<br>" . $connectionError["department"]->name;
        $connectionErrorsString .= " &lt;" . $connectionError["department"]->email . "&gt;<br>";
        $connectionErrorsString .= "Error: " . $connectionError["error"] . "<br>";
        $connectionErrorsString .= "-----";
    }
    $failureMessage = "<p>One or more POP3 connections failed:<br><br>-----" . $connectionErrorsString . "<br></p>";
    try {
        sendAdminNotification("system", "POP3 Connection Error", $failureMessage);
    } catch (Exception $e) {
    }
}
if (WHMCS\Environment\Php::isCli() || DI::make("config")->pop_cron_debug) {
    $output = implode("", $cronOutput);
    if (WHMCS\Environment\Php::isCli()) {
        $output = strip_tags(str_replace(array("<br>", "<hr>"), array("\n", "\n---\n"), $output));
    }
    echo $output;
}
$transientData->store("popCronComplete", "true", 3600);
run_hook("PopEmailCollectionCronCompleted", array("connectionErrors" => $connectionErrors));

?>