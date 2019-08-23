<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("CLIENTAREA", true);
require "init.php";
$pagetitle = Lang::trans("contacttitle");
$breadcrumbnav = "<a href=\"index.php\">" . Lang::trans("globalsystemname") . "</a> > <a href=\"contact.php\">" . Lang::trans("contacttitle") . "</a>";
$templatefile = "contact";
$pageicon = "images/contact_big.gif";
$displayTitle = Lang::trans("contactus");
$tagline = Lang::trans("readyforquestions");
initialiseClientArea($pagetitle, $displayTitle, $tagline, $pageicon, $breadcrumbnav);
$action = $whmcs->get_req_var("action");
$name = $whmcs->get_req_var("name");
$email = $whmcs->get_req_var("email");
$subject = $whmcs->get_req_var("subject");
$message = $whmcs->get_req_var("message");
if (WHMCS\Config\Setting::getValue("ContactFormDept")) {
    redir("step=2&deptid=" . WHMCS\Config\Setting::getValue("ContactFormDept"), "submitticket.php");
}
$captcha = new WHMCS\Utility\Captcha();
$validate = new WHMCS\Validate();
$contactFormSent = false;
$sendError = "";
if ($action == "send") {
    check_token();
    $validate->validate("required", "name", "contacterrorname");
    if ($validate->validate("required", "email", "clientareaerroremail")) {
        $validate->validate("email", "email", "clientareaerroremailinvalid");
    }
    $validate->validate("required", "subject", "contacterrorsubject");
    $validate->validate("required", "message", "contacterrormessage");
    $captcha->validateAppropriateCaptcha(WHMCS\Utility\Captcha::FORM_CONTACT_US, $validate);
    if (!$validate->hasErrors()) {
        $logoUrl = $whmcs->getLogoUrlForEmailTemplate();
        if ($logoUrl) {
            $sendmessage = "<p><a href=\"" . WHMCS\Config\Setting::getValue("Domain") . "\" target=\"_blank\"><img src=\"" . $logoUrl . "\" alt=\"" . WHMCS\Config\Setting::getValue("CompanyName") . "\" border=\"0\"></a></p>";
        }
        $sendmessage .= "<font style=\"font-family:Verdana;font-size:11px\"><p>" . nl2br($message) . "</p>";
        try {
            $systemFromEmail = WHMCS\Config\Setting::getValue("SystemEmailsFromEmail");
            $mail = new WHMCS\Mail($name, $systemFromEmail);
            $mail->Subject = Lang::trans("contactform") . ": " . $subject;
            $message_text = str_replace("</p>", "\n\n", $sendmessage);
            $message_text = str_replace("<br>", "\n", $message_text);
            $message_text = str_replace("<br />", "\n", $message_text);
            $message_text = strip_tags($message_text);
            $mail->Body = $sendmessage;
            $mail->AltBody = $message_text;
            if (!WHMCS\Config\Setting::getValue("ContactFormTo")) {
                $contactformemail = WHMCS\Config\Setting::getValue("Email");
            } else {
                $contactformemail = WHMCS\Config\Setting::getValue("ContactFormTo");
            }
            $mail->From = $systemFromEmail;
            $mail->FromName = WHMCS\Config\Setting::getValue("SystemEmailsFromName");
            $mail->AddAddress($contactformemail);
            $mail->addReplyTo($email, $name);
            if ($smtp_debug) {
                $mail->SMTPDebug = true;
            }
            $mail->Send();
            $contactFormSent = true;
        } catch (PHPMailer\PHPMailer\Exception $e) {
            $sendError = "<li>" . Lang::trans("clientareaerroroccured") . "</li>";
            logActivity("Contact form mail sending failed with a PHPMailer Exception: " . $e->getMessage() . " (Subject: " . $subject . ")");
        } catch (Exception $e) {
            $sendError = "<li>" . Lang::trans("clientareaerroroccured") . "</li>";
            logActivity("Contact form mail sending failed with this error: " . $e->getMessage());
        }
    }
}
$smarty->assign("sent", $contactFormSent);
if ($validate->hasErrors() || $sendError) {
    $smarty->assign("errormessage", implode("\n", array($validate->getHTMLErrorOutput(), $sendError)));
}
$smarty->assign("name", $name);
$smarty->assign("email", $email);
$smarty->assign("subject", $subject);
$smarty->assign("message", $message);
$smarty->assign("captcha", $captcha);
$smarty->assign("captchaForm", WHMCS\Utility\Captcha::FORM_CONTACT_US);
$smarty->assign("recaptchahtml", clientAreaReCaptchaHTML());
$smarty->assign("capatacha", $captcha);
$smarty->assign("recapatchahtml", clientAreaReCaptchaHTML());
outputClientArea($templatefile, false, array("ClientAreaPageContact"));

?>