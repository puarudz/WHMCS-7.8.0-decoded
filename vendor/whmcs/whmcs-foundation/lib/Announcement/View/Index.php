<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Announcement\View;

class Index extends \WHMCS\ClientArea
{
    protected function initializeView()
    {
        \Menu::addContext("routeNamespace", "announcement");
        parent::initializeView();
        $this->setPageTitle(\Lang::trans("announcementstitle"));
        $this->setDisplayTitle(\Lang::trans("news"));
        $this->setTagLine(\Lang::trans("allthelatest") . " " . \WHMCS\Config\Setting::getValue("CompanyName"));
        $this->addOutputHookFunction("ClientAreaPageAnnouncements");
        $this->addToBreadCrumb(\WHMCS\Config\Setting::getValue("SystemURL"), \Lang::trans("globalsystemname"))->addToBreadCrumb(routePath("announcement-index"), \Lang::trans("announcementstitle"));
        $this->assign("twitterusername", \WHMCS\Config\Setting::getValue("TwitterUsername"));
        $this->assign("twittertweet", \WHMCS\Config\Setting::getValue("AnnouncementsTweet"));
        $this->assign("facebookrecommend", \WHMCS\Config\Setting::getValue("AnnouncementsFBRecommend"));
        $this->assign("facebookcomments", \WHMCS\Config\Setting::getValue("AnnouncementsFBComments"));
        $routeSetting = \WHMCS\Config\Setting::getValue("RouteUriPathMode");
        $seoSetting = $routeSetting == \WHMCS\Route\UriPath::MODE_REWRITE ? 1 : 0;
        $this->assign("seofriendlyurls", $seoSetting);
        \Menu::addContext("monthsWithAnnouncements", \WHMCS\Announcement\Announcement::getUniqueMonthsWithAnnouncements());
        \Menu::primarySidebar("announcementList");
        \Menu::secondarySidebar("announcementList");
        $this->setTemplate("announcements");
    }
    public function getAnnouncementTemplateData(\WHMCS\Announcement\Announcement $item)
    {
        $translatedItem = $item->bestTranslation();
        $editLink = "";
        if (0 < (int) \WHMCS\Session::get("adminid") && \WHMCS\User\Admin\Permission::currentAdminHasPermissionName("Manage Announcements")) {
            $editLink = \App::getSystemURL() . \App::get_admin_folder_name() . "/" . "supportannouncements.php?action=manage&id=" . $item->id;
        }
        return array("id" => $item->id, "date" => $item->publishDate->format("MM/DD/YYYY"), "timestamp" => $item->publishDate->getTimestamp(), "title" => $translatedItem->title, "urlfriendlytitle" => getModRewriteFriendlyString($translatedItem->title), "summary" => ticketsummary(strip_tags($translatedItem->announcement), 350), "text" => $translatedItem->announcement, "editLink" => $editLink);
    }
}

?>