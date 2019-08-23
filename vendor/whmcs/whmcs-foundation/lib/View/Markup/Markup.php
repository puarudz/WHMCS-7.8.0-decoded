<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\View\Markup;

class Markup
{
    public function determineMarkupEditor($contentType = "", $definedEditor = "plain", $timestamp = NULL)
    {
        $markupEditor = "plain";
        $definableEditorContentTypes = array("ticket_msg", "ticket_reply", "ticket_note");
        if (in_array($contentType, $definableEditorContentTypes)) {
            if ($definedEditor == "markdown") {
                $markupEditor = "markdown";
            } else {
                $markupEditor = "bbcode";
            }
        } else {
            if ($timestamp && $this->isAfterMdeUpgrade($timestamp)) {
                $markupEditor = "markdown";
            }
        }
        return $markupEditor;
    }
    public function transform($text, $markupFormat = "plain", $emailFriendly = false)
    {
        $text = strip_tags($text);
        $text = \WHMCS\Input\Sanitize::decode($text);
        if (!function_exists("ticketAutoHyperlinks")) {
            require_once ROOTDIR . "/includes/ticketfunctions.php";
        }
        switch ($markupFormat) {
            case "markdown":
                $markdown = new Markdown\Markdown();
                $markdown->email_friendly = $emailFriendly;
                $formattedText = $markdown->transform(\WHMCS\Input\Sanitize::decode($text));
                break;
            case "bbcode":
                $text = \WHMCS\Input\Sanitize::encode($text);
                $formattedText = Bbcode\Bbcode::transform($text);
                $formattedText = ticketAutoHyperlinks(nl2br($formattedText));
                break;
            case "plain":
            default:
                $text = \WHMCS\Input\Sanitize::encode($text);
                $formattedText = ticketAutoHyperlinks(nl2br($text));
                break;
        }
        return $formattedText;
    }
    public function isAfterMdeUpgrade($timestamp)
    {
        $mdeFromTime = \WHMCS\Config\Setting::getValue("MDEFromTime");
        if ($mdeFromTime) {
            $mdeFromTime = \WHMCS\Carbon::createFromFormat("Y-m-d H:i:s", $mdeFromTime);
            return \WHMCS\Carbon::createFromFormat("Y-m-d H:i:s", $timestamp)->gte($mdeFromTime);
        }
        return false;
    }
}

?>