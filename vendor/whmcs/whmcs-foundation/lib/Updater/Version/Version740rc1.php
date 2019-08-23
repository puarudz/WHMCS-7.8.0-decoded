<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version740rc1 extends IncrementalVersion
{
    protected $updateActions = array("removeHtAccessTxt", "createDefaultNotificationEmailTemplate", "removeOldApplicationLink");
    protected function removeHtAccessTxt()
    {
        $knownShippedHashes = array("58e84cee556969309b8c7146b3edc5a2", "2ad298cf3b5973aaa8a2b3931ecc6e97", "4d2513272efefa886601253efd393404");
        $htaccessFilename = ROOTDIR . DIRECTORY_SEPARATOR . "htaccess.txt";
        if (in_array(md5_file($htaccessFilename), $knownShippedHashes)) {
            unlink($htaccessFilename);
        }
        return $this;
    }
    protected function createDefaultNotificationEmailTemplate()
    {
        $mailTemplate = \WHMCS\Mail\Template::where("name", "Default Notification Message")->first();
        if (!$mailTemplate) {
            $mail = new \WHMCS\Mail\Template();
            $mail->subject = "Notification Message";
            $mail->message = "<h2><a href=\"{\$notification_url}\">{\$notification_title}</a></h2>\n<div>{\$notification_message}</div>\n{foreach from=\$notification_attributes item=\$attribute}\n<div>\n<div>{\$attribute.label}: {if \$attribute.icon}<img src=\"{\$attribute.icon}\" alt=\"\" />{/if}{if \$attribute.style}<span class=\"{\$attribute.style}\">{/if}{if \$attribute.url}<a href=\"{\$attribute.url}\">{\$attribute.value}</a>{else}{\$attribute.value}{/if}{if \$attribute.style}</span>{/if}</div>\n</div>\n{/foreach}";
            $mail->type = "notification";
            $mail->custom = false;
            $mail->attachments = array();
            $mail->name = "Default Notification Message";
            $mail->language = "";
            $mail->fromName = "";
            $mail->fromEmail = "";
            $mail->disabled = false;
            $mail->copyTo = array();
            $mail->blindCopyTo = array();
            $mail->plaintext = false;
            $mail->save();
        }
        return $this;
    }
    protected function removeOldApplicationLink()
    {
        $servers = \WHMCS\Database\Capsule::table("tblapplinks")->where("tblapplinks.is_enabled", "=", 1)->select("tblapplinks.module_name")->get();
        $queue = new \WHMCS\Scheduling\Jobs\Queue();
        foreach ($servers as $server) {
            $queue->add($server->module_name . ".link.cleanup", "WHMCS\\ApplicationLink\\Provision", "cleanup", array($server->module_name), 0, true);
        }
        return $this;
    }
}

?>