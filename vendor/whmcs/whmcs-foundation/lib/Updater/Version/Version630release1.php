<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version630release1 extends IncrementalVersion
{
    protected $updateActions = array("newEmailHeader", "newEmailCss", "newEmailFooter", "updateCreateTicketNotificationEmail", "removeOldAdminSupportEmailTemplates");
    public function newEmailHeader()
    {
        $defaultMd5 = "3f7ab7eea9b8ffa7a1f6a6077c69b063";
        $existingHeaderMd5 = md5(\WHMCS\Config\Setting::getValue("EmailGlobalHeader"));
        if ($defaultMd5 == $existingHeaderMd5) {
            \WHMCS\Config\Setting::setValue("EmailGlobalHeader", "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n<html xmlns=\"http://www.w3.org/1999/xhtml\">\n    <head>\n        <meta http-equiv=\"Content-Type\" content=\"text/html; charset={\$charset}\" />\n        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no\">\n        <style type=\"text/css\">\n            [EmailCSS]\n        </style>\n    </head>\n    <body leftmargin=\"0\" marginwidth=\"0\" topmargin=\"0\" marginheight=\"0\" offset=\"0\">\n        <center>\n            <table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" height=\"100%\" width=\"100%\" id=\"bodyTable\">\n                <tr>\n                    <td align=\"center\" valign=\"top\" id=\"bodyCell\">\n                        <table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" id=\"templateContainer\">\n                            <tr>\n                                <td align=\"center\" valign=\"top\">\n                                    <table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" id=\"templateHeader\">\n                                        <tr>\n                                            <td valign=\"top\" class=\"headerContent\">\n                                                <a href=\"{\$company_domain}\">\n                                                    <img src=\"{\$company_logo_url}\" style=\"max-width:600px;padding:20px\" id=\"headerImage\" alt=\"{\$company_name}\" />\n                                            </td>\n                                        </tr>\n                                    </table>\n                                </td>\n                            </tr>\n                            <tr>\n                                <td align=\"center\" valign=\"top\">\n                                    <table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" id=\"templateBody\">\n                                        <tr>\n                                            <td valign=\"top\" class=\"bodyContent\">");
        }
    }
    public function newEmailFooter()
    {
        $defaultMd5 = "d41d8cd98f00b204e9800998ecf8427e";
        $existingFooterMd5 = md5(\WHMCS\Config\Setting::getValue("EmailGlobalFooter"));
        if ($defaultMd5 == $existingFooterMd5) {
            \WHMCS\Config\Setting::setValue("EmailGlobalFooter", "                                            </td>\n                                        </tr>\n                                    </table>\n                                </td>\n                            </tr>\n                            <tr>\n                                <td align=\"center\" valign=\"top\">\n                                    <table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" id=\"templateFooter\">\n                                        <tr>\n                                            <td valign=\"top\" class=\"footerContent\">\n                                                &nbsp;<a href=\"{\$company_domain}\">visit our website</a>\n                                                <span class=\"hide-mobile\"> | </span>\n                                                <a href=\"{\$whmcs_url}\">log in to your account</a>\n                                                <span class=\"hide-mobile\"> | </span>\n                                                <a href=\"{\$whmcs_url}submitticket.php\">get support</a>&nbsp;<br />\n                                                Copyright &copy; {\$company_name}, All rights reserved.\n                                            </td>\n                                        </tr>\n                                    </table>\n                                </td>\n                            </tr>\n                        </table>\n                    </td>\n                </tr>\n            </table>\n        </center>\n    </body>\n</html>");
        }
    }
    public function newEmailCSS()
    {
        $defaultMd5 = "fb12240d184fc801e7687e1e634f1f23";
        $defaultMd5AfterSave = "f058eed215337ebc78a8c2353f1deed7";
        $existingCssMd5 = md5(\WHMCS\Config\Setting::getValue("EmailCSS"));
        if ($existingCssMd5 == $defaultMd5 || $existingCssMd5 == $defaultMd5AfterSave) {
            \WHMCS\Config\Setting::setValue("EmailCSS", ".ExternalClass,.ExternalClass div,.ExternalClass font,.ExternalClass p,.ExternalClass span,.ExternalClass td,h1,img{line-height:100%}h1,h2{display:block;font-family:Helvetica;font-style:normal;font-weight:700}#outlook a{padding:0}.ExternalClass,.ReadMsgBody{width:100%}a,blockquote,body,li,p,table,td{-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%}table,td{mso-table-lspace:0;mso-table-rspace:0}img{-ms-interpolation-mode:bicubic;border:0;height:auto;outline:0;text-decoration:none}table{border-collapse:collapse!important}#bodyCell,#bodyTable,body{height:100%!important;margin:0;padding:0;width:100%!important}#bodyCell{padding:20px;}#templateContainer{width:600px;border:1px solid #ddd;background-color:#fff}#bodyTable,body{background-color:#FAFAFA}h1{color:#202020!important;font-size:26px;letter-spacing:normal;text-align:left;margin:0 0 10px}h2{color:#404040!important;font-size:20px;line-height:100%;letter-spacing:normal;text-align:left;margin:0 0 10px}h3,h4{display:block;font-style:italic;font-weight:400;letter-spacing:normal;text-align:left;margin:0 0 10px;font-family:Helvetica;line-height:100%}h3{color:#606060!important;font-size:16px}h4{color:grey!important;font-size:14px}.headerContent{background-color:#f8f8f8;border-bottom:1px solid #ddd;color:#505050;font-family:Helvetica;font-size:20px;font-weight:700;line-height:100%;text-align:left;vertical-align:middle;padding:0}.bodyContent,.footerContent{font-family:Helvetica;line-height:150%;text-align:left;}.footerContent{text-align:center}.bodyContent pre{padding:15px;background-color:#444;color:#f8f8f8;border:0}.bodyContent pre code{white-space:pre;word-break:normal;word-wrap:normal}.bodyContent table{margin:10px 0;background-color:#fff;border:1px solid #ddd}.bodyContent table th{padding:4px 10px;background-color:#f8f8f8;border:1px solid #ddd;font-weight:700;text-align:center}.bodyContent table td{padding:3px 8px;border:1px solid #ddd}.table-responsive{border:0}.bodyContent a{word-break:break-all}.headerContent a .yshortcuts,.headerContent a:link,.headerContent a:visited{color:#1f5d8c;font-weight:400;text-decoration:underline}#headerImage{height:auto;max-width:600px;padding:20px}#templateBody{background-color:#fff}.bodyContent{color:#505050;font-size:14px;padding:20px}.bodyContent a .yshortcuts,.bodyContent a:link,.bodyContent a:visited{color:#1f5d8c;font-weight:400;text-decoration:underline}.bodyContent a:hover{text-decoration:none}.bodyContent img{display:inline;height:auto;max-width:560px}.footerContent{color:grey;font-size:12px;padding:20px}.footerContent a .yshortcuts,.footerContent a span,.footerContent a:link,.footerContent a:visited{color:#606060;font-weight:400;text-decoration:underline}@media only screen and (max-width:640px){h1,h2,h3,h4{line-height:100%!important}#templateContainer{max-width:600px!important;width:100%!important}#templateContainer,body{width:100%!important}a,blockquote,body,li,p,table,td{-webkit-text-size-adjust:none!important}body{min-width:100%!important}#bodyCell{padding:10px!important}h1{font-size:24px!important}h2{font-size:20px!important}h3{font-size:18px!important}h4{font-size:16px!important}#templatePreheader{display:none!important}.headerContent{font-size:20px!important;line-height:125%!important}.footerContent{font-size:14px!important;line-height:115%!important}.footerContent a{display:block!important}.hide-mobile{display:none;}}");
        }
    }
    public function updateCreateTicketNotificationEmail()
    {
        $email = \WHMCS\Mail\Template::whereName("Support Ticket Change Notification")->whereType("admin")->first();
        if (!$email) {
            $email = new \WHMCS\Mail\Template();
            $email->type = "admin";
            $email->name = "Support Ticket Change Notification";
            $email->subject = "[Ticket ID: {\$ticket_tid}] {\$ticket_subject}";
            $email->custom = false;
            $email->disabled = false;
            $email->plaintext = false;
        }
        $email->message = "{if \$newTicket}\n    <p>Ticket #<a href=\"{\$whmcs_admin_url}supporttickets.php?action=viewticket&id={\$ticket_id}\"><strong>{\$ticket_tid}</strong></a> has been opened by <strong>{\$changer}</strong>.</p>\n    <p>\n        Client: {\$client_name}{if \$client_id} #{\$client_id}{/if}<br />\n        Department: {\$ticket_department}<br />\n        Subject: {\$ticket_subject}<br />\n        Priority: {\$ticket_priority}\n    </p>\n    <div class=\"quoted-content\">\n        {\$newTicket}\n    </div>\n{else}\n    <p>Ticket #<a href=\"{\$whmcs_admin_url}supporttickets.php?action=viewticket&id={\$ticket_id}\"><strong>{\$ticket_tid}</strong></a> {if \$newReply || \$newNote}has had a new {if \$newReply}reply{else}note{/if} posted by{else}has been updated by{/if} <strong>{\$changer}</strong>.</p>\n\n    {if \$changes}\n        <table class=\"keyvalue-table\" style=\"border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;\">\n            <tbody>\n                {foreach \$changes as \$change}\n                    <tr>\n                        <td>{\$change@key}:</td>\n                        <td>\n                            <span style=\"background-color:#ffe7e7;text-decoration:line-through;\">{\$change.old}</span>\n                            &nbsp;\n                            <span style=\"background-color:#ddfade;\">{\$change.new}</span>\n                        </td>\n                    </tr>\n                {/foreach}\n            </tbody>\n        </table>\n    {/if}\n\n    {if \$newReply}\n        <div class=\"quoted-content\">\n            {\$newReply}\n        </div>\n    {/if}\n\n    {if \$newNote}\n        <div class=\"quoted-content\">\n            {\$newNote}\n        </div>\n    {/if}\n\n    {if \$newAttachments}{\$newAttachments}{/if}\n{/if}\n<p>\n    You can respond to this ticket by simply replying to this email or through the admin area at the url below.\n</p>\n<p>\n    <a href=\"{\$whmcs_admin_url}supporttickets.php?action=viewticket&id={\$ticket_id}\">\n        {\$whmcs_admin_url}supporttickets.php?action=viewticket&id={\$ticket_id}\n    </a>\n</p>";
        $email->save();
        return $this;
    }
    public function removeOldAdminSupportEmailTemplates()
    {
        \WHMCS\Database\Capsule::table("tblemailtemplates")->whereIn("name", array("Support Ticket Created", "Support Ticket Department Reassigned", "Support Ticket Flagged", "Support Ticket Response"))->where("type", "=", "admin")->delete();
        return $this;
    }
}

?>