<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version630alpha1 extends IncrementalVersion
{
    protected $updateActions = array("migrateOpenIDValues", "createTicketNotificationEmail");
    public function migrateOpenIDValues()
    {
        $rowsToMigrate = \WHMCS\Database\Capsule::table("tbloauthserver_clients")->select(array("id", "redirect_uri", "grant_types"))->get();
        foreach ($rowsToMigrate as $row) {
            $toUpdate = array();
            if (strpos($row->redirect_uri, ",") !== false) {
                $newRedirectUri = str_replace(",", " ", $row->redirect_uri);
                $toUpdate["redirect_uri"] = $newRedirectUri;
            }
            if (strpos($row->grant_types, ",") !== false) {
                $newGrantTypes = str_replace(",", " ", $row->grant_types);
                $toUpdate["grant_types"] = $newGrantTypes;
            }
            if (!empty($toUpdate)) {
                \WHMCS\Database\Capsule::table("tbloauthserver_clients")->where("id", $row->id)->update($toUpdate);
            }
        }
        return $this;
    }
    public function createTicketNotificationEmail()
    {
        $email = \WHMCS\Mail\Template::whereName("Support Ticket Change Notification")->whereType("admin")->first();
        if (!$email) {
            $email = new \WHMCS\Mail\Template();
            $email->type = "admin";
            $email->name = "Support Ticket Change Notification";
            $email->subject = "[Ticket ID: {\$ticket_tid}] {\$ticket_subject}";
            $email->message = "<p>Ticket #<a href=\"{\$whmcs_admin_url}supporttickets.php?action=viewticket&id={\$ticket_id}\"><strong>{\$ticket_tid}</strong></a> has been updated.</p>\n\n<table class=\"keyvalue-table\" style=\"border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt\">\n    {foreach \$changes as \$change}\n        <tr>\n            <td>{\$change.field}:</td>\n            <td>\n                <span style=\"background-color:#ffe7e7;text-decoration:line-through;\">{\$change.oldValue}</span>\n                &nbsp;\n                <span style=\"background-color:#ddfade;\">{\$change.newValue}</span>\n            </td>\n        </tr>\n    {/foreach}\n</table>\n\n<br />\n\n{if \$newReply}\n    ---<br />\n    {\$newReply}<br />\n    ---<br />\n    <br />\n{/if}\n\n<p>\n    You can respond to this ticket by simply replying to this email or through the admin area at the url below.\n</p>\n<p>\n    <a href=\"{\$whmcs_admin_url}supporttickets.php?action=viewticket&id={\$ticket_id}\">\n        {\$whmcs_admin_url}supporttickets.php?action=viewticket&id={\$ticket_id}\n    </a>\n</p>";
            $email->custom = false;
            $email->disabled = false;
            $email->plaintext = false;
            $email->save();
        }
        return $this;
    }
}

?>