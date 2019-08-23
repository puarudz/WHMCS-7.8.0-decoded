<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version710alpha1 extends IncrementalVersion
{
    protected $updateActions = array("addAdminPasswordResetVerificationEmailTemplate", "addAdminPasswordResetConfirmationEmailTemplate");
    public function addAdminPasswordResetVerificationEmailTemplate()
    {
        $email = \WHMCS\Mail\Template::whereName("Admin Password Reset Validation")->whereType("admin")->first();
        if (!$email) {
            $email = new \WHMCS\Mail\Template();
            $email->type = "admin";
            $email->name = "Admin Password Reset Validation";
            $email->subject = "WHMCS Password Reset";
            $email->language = "";
            $email->plaintext = false;
            $email->disabled = false;
            $email->custom = false;
            $email->message = "<p>Hi {\$firstname},</p>\n<p>Recently a request was submitted to reset your admin password. Follow the link below to reset it.</p>\n<p><a href=\"{\$pw_reset_url}\">{\$pw_reset_url}</a></p>\n<p>If you did not request a password reset, please ignore this email. The password reset link will expire in 2 hours.</p>\n<p>{\$whmcs_admin_link}</p>";
            $email->save();
        }
    }
    public function addAdminPasswordResetConfirmationEmailTemplate()
    {
        $email = \WHMCS\Mail\Template::whereName("Admin Password Reset Confirmation")->whereType("admin")->first();
        if (!$email) {
            $email = new \WHMCS\Mail\Template();
            $email->type = "admin";
            $email->name = "Admin Password Reset Confirmation";
            $email->subject = "WHMCS Password Reset Confirmation";
            $email->language = "";
            $email->plaintext = false;
            $email->disabled = false;
            $email->custom = false;
            $email->message = "<p>Hi {\$firstname},</p>\n<p>This is a confirmation that your admin password has now been reset.</p>\n<p>If you did not initiate this password reset, please notify your system administrator immediately.</p>\n<p>{\$whmcs_admin_link}</p>";
            $email->save();
        }
    }
}

?>