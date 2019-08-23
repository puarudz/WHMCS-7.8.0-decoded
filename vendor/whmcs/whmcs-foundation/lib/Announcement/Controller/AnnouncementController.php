<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Announcement\Controller;

class AnnouncementController
{
    public function __construct()
    {
        if (!function_exists("ticketsummary")) {
            require ROOTDIR . "/includes/ticketfunctions.php";
        }
    }
    private function setPageContexts(\Psr\Http\Message\ServerRequestInterface $request)
    {
        \Menu::addContext("announcementView", $request->getAttribute("view"));
    }
    public function index(\Psr\Http\Message\ServerRequestInterface $request)
    {
        $this->setPageContexts($request);
        $view = new \WHMCS\Announcement\View\Index();
        $pageLimit = 10;
        $page = $request->getAttribute("page", 1);
        $query = \WHMCS\Announcement\Announcement::published();
        $userView = $request->getAttribute("view");
        if ($userView) {
            $query = $query->where("date", "like", "%" . $userView . "%");
        }
        $view->assign("view", $userView);
        $results = $query->orderBy("date", "DESC")->skip((int) (($page - 1) * $pageLimit))->limit((int) $pageLimit)->get();
        $announcements = array();
        foreach ($results as $announcement) {
            $announcements[] = $view->getAnnouncementTemplateData($announcement);
        }
        $view->assign("announcements", $announcements);
        $numannouncements = \WHMCS\Announcement\Announcement::published()->count();
        $totalpages = ceil($numannouncements / $pageLimit);
        $prevpage = $nextpage = "";
        if ($page != 1) {
            $prevpage = $page - 1;
        }
        if ($page != $totalpages && $numannouncements) {
            $nextpage = $page + 1;
        }
        if (!$totalpages) {
            $totalpages = 1;
        }
        $view->assign("numannouncements", $numannouncements);
        $view->assign("pagenumber", $page);
        $view->assign("totalpages", $totalpages);
        $view->assign("prevpage", $prevpage);
        $view->assign("nextpage", $nextpage);
        return $view;
    }
    public function twitterFeed(\Psr\Http\Message\ServerRequestInterface $request)
    {
        if (!function_exists("twitter_getTwitterIntents")) {
            require_once ROOTDIR . "/modules/social/twitter/twitter.php";
        }
        $view = new \WHMCS\Announcement\View\TwitterFeed();
        $tweets = twitter_getTwitterIntents(\WHMCS\Config\Setting::getValue("TwitterUsername"), \App::getDBVersion());
        $view->assign("tweets", $tweets);
        $view->assign("numtweets", $request->getAttribute("numtweets", 3));
        return $view;
    }
    public function view(\Psr\Http\Message\ServerRequestInterface $request)
    {
        $this->setPageContexts($request);
        $announcement = \WHMCS\Announcement\Announcement::published()->find($request->getAttribute("id"));
        if (!$announcement) {
            return new \Zend\Diactoros\Response\RedirectResponse(routePath("announcement-index"));
        }
        $translatedAnnouncement = $announcement->bestTranslation();
        $view = new \WHMCS\Announcement\View\Item();
        $view->setDisplayTitle($translatedAnnouncement->title);
        $view->setTagLine("");
        $view->setTemplateVariables($view->getAnnouncementTemplateData($translatedAnnouncement));
        $view->addToBreadCrumb(routePath("announcement-view", $translatedAnnouncement->id), $translatedAnnouncement->title);
        return $view;
    }
}

?>