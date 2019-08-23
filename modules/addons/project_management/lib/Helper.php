<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCSProjectManagement;

class Helper
{
    public static function getCurrentAdminId()
    {
        return (int) \WHMCS\Session::get("adminid");
    }
    public static function getAdmins()
    {
        static $adminNames = NULL;
        if (is_null($adminNames)) {
            $adminNames = array();
            $admins = \WHMCS\User\Admin::where("disabled", 0)->get();
            foreach ($admins as $admin) {
                $adminNames[$admin->id] = $admin->fullName;
            }
        }
        return $adminNames;
    }
    public static function timeToHuman($sec)
    {
        $string = "";
        $hours = intval(intval($sec) / 3600);
        if (1 <= $hours) {
            $string = $hours . " Hour";
            $string .= $hours != 1 ? "s" : "";
            $string .= " ";
        }
        $minutes = intval($sec / 60 % 60);
        $seconds = intval($sec % 60) / 60;
        $minutes = round($minutes + $seconds, 0);
        if ($minutes == 0) {
            $minutes = 1;
        }
        $string .= $minutes . " Minute";
        $string .= $minutes != 1 ? "s" : "";
        return $string;
    }
    public static function timeToReadable($sec)
    {
        if ($sec <= 0) {
            $sec = 0;
        }
        $hms = "";
        $hours = intval(intval($sec) / 3600);
        $hms .= $padHours ? str_pad($hours, 2, "0", STR_PAD_LEFT) . ":" : $hours . ":";
        $minutes = intval($sec / 60 % 60);
        $hms .= str_pad($minutes, 2, "0", STR_PAD_LEFT) . ":";
        $seconds = intval($sec % 60);
        $hms .= str_pad($seconds, 2, "0", STR_PAD_LEFT);
        return $hms;
    }
    public static function daysUntilDate($date)
    {
        if ($date == "0000-00-00") {
            return "N/A";
        }
        $currentTime = time();
        $date = strtotime($date);
        $days = ceil(($date - $currentTime) / 86400);
        if ($days == "-0") {
            $days = 0;
        }
        return $days;
    }
    public static function getFriendlyDaysToGo($date, $lang)
    {
        $days = self::daysUntilDate($date);
        $dueincolor = $days < 2 ? "cc0000" : "73BC10";
        if (30 < $days || $days < -10) {
            return date("jS M Y", strtotime($date));
        }
        if ($days == 7) {
            return "<span style=\"color:#" . $dueincolor . "\">1 " . $lang["week"] . "</span>";
        }
        if ($days == 14) {
            return "<span style=\"color:#" . $dueincolor . "\">2 " . $lang["weeks"] . "</span>";
        }
        if (0 < $days) {
            $daysString = $lang["days"];
            if ($days == 1) {
                $daysString = $lang["day"];
            }
            return "<span style=\"color:#" . $dueincolor . "\">" . $days . " " . $daysString . "</span>";
        }
        if ($days === 0 || $days == "N/A") {
            $daysString = $days;
            if ($days === 0) {
                $daysString = $lang["today"];
            }
            return "<span style=\"color:#" . $dueincolor . "\">" . $daysString . "</span>";
        }
        $daysString = $lang["daysago"];
        if ($days == -1) {
            $daysString = str_replace($lang["days"], $lang["day"], $daysString);
        }
        return "<span style=\"color:#" . $dueincolor . "\">" . $days * -1 . " " . $daysString . "</span>";
    }
    public static function getFriendlyMbValue($size)
    {
        if (preg_match("/G\$/", $size)) {
            $size = $size * 1024;
        } else {
            if (preg_match("/K\$/", $size)) {
                $size = $size / 1024;
            } else {
                if (!preg_match("/M\$/", $size)) {
                    $size = round($size / (1024 * 1024), 0);
                }
            }
        }
        return (int) $size;
    }
    public static function getClientLink($userId)
    {
        $link = "N/A";
        if ($userId) {
            $client = \WHMCS\User\Client::find($userId);
            if ($client) {
                $name = $client->fullName;
                if ($client->companyName) {
                    $name .= " (" . $client->companyName . ")";
                }
                $link = "<a class=\"autoLinked text-grey\" href=\"clientssummary.php?userid=" . $userId . "\">" . $name . "</a>";
            }
        }
        return $link;
    }
}

?>