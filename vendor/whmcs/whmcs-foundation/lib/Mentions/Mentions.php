<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Mentions;

class Mentions
{
    protected static function parseForMentions($message)
    {
        $mentionedUsers = array();
        $endOfRegex = "";
        $outsideGroup = "[<\\s]{1}";
        $data = \WHMCS\Config\Setting::getValue("AdminUserNamesWithSpaces");
        if ($data && $data === "1") {
            $endOfRegex = "#";
            $outsideGroup = "";
        }
        preg_match_all("/(?'whole'@(?'user'[[:word:]@. -]+?)" . $endOfRegex . ")" . $outsideGroup . "/i", $message, $mentionedUsers);
        return $mentionedUsers;
    }
    public static function getIdsForMentions($message)
    {
        $adminNames = array_unique(self::parseForMentions($message)["user"]);
        $admins = array();
        if ($adminNames) {
            $admins = \WHMCS\User\Admin::whereIn("username", $adminNames)->pluck("id")->toArray();
        }
        return $admins;
    }
    public static function getMentionReplacements($message)
    {
        $mentions = self::parseForMentions($message);
        $adminDetails = array("find" => array(), "replace" => array());
        if ($mentions["user"]) {
            $admins = \WHMCS\User\Admin::whereIn("username", array_unique($mentions["user"]))->get();
            foreach (array_unique($mentions["whole"]) as $key => $rawMatch) {
                $user = $admins->where("username", $mentions["user"][$key])->first();
                if ($user) {
                    $adminDetails["find"][] = $rawMatch;
                    $adminDetails["replace"][] = "<span class=\"label label-info\">" . $user->username . "</span>";
                }
            }
        }
        return $adminDetails;
    }
    public static function sendNotification($type, $relatedId, $message, array $recipients, $description = "")
    {
        if (in_array($type, array("ticket", "note")) && 0 < count($recipients) && $relatedId && is_int($relatedId)) {
            $mergeFields = array();
            $mergeFields["mention_admin_name"] = getAdminName();
            $mergeFields["mention_entity"] = \AdminLang::trans("mentions.entity" . ucfirst($type));
            $mergeFields["mention_entity_action"] = \AdminLang::trans("mentions.action", array(":type" => $mergeFields["mention_entity"]));
            $markup = new \WHMCS\View\Markup\Markup();
            $mentions = self::getMentionReplacements($message);
            if (0 < count($mentions)) {
                $message = str_replace($mentions["find"], $mentions["replace"], $message);
            }
            $mergeFields["message"] = $markup->transform($message, $markup->determineMarkupEditor("", "", \WHMCS\Carbon::now()->toDateTimeString()), true);
            $adminPath = \App::get_admin_folder_name();
            $link = $adminPath . "/clientsnotes.php?userid=" . (int) $relatedId;
            if ($type == "ticket") {
                $link = $adminPath . "/supporttickets.php?action=view&id=" . (int) $relatedId;
            }
            if ($type == "note") {
                $clientDetails = \WHMCS\User\Client::find($relatedId);
                $description = \AdminLang::trans("mentions.aNote", array(":clientName" => $clientDetails->fullName));
            }
            $mergeFields["mention_entity_description"] = $description;
            $mergeFields["mention_view_url"] = \App::getSystemURL() . $link;
            return sendAdminMessage("Mention Notification", $mergeFields, "mentions", 0, array_unique($recipients));
        }
        return false;
    }
}

?>