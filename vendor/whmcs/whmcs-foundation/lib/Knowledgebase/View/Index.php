<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Knowledgebase\View;

class Index extends \WHMCS\ClientArea
{
    protected function initializeView()
    {
        parent::initializeView();
        $this->setPageTitle(\Lang::trans("knowledgebasetitle"));
        $this->setDisplayTitle(\Lang::trans("knowledgebasetitle"));
        $this->setTemplate("knowledgebase");
        $this->addOutputHookFunction("ClientAreaPageKnowledgebase");
        $this->addToBreadCrumb(\WHMCS\Config\Setting::getValue("SystemURL"), \Lang::trans("globalsystemname"))->addToBreadCrumb(routePath("knowledgebase-index"), \Lang::trans("knowledgebasetitle"));
        \Menu::addContext("kbRootCategories", $this->getRootCategoryTemplateData());
        \Menu::addContext("knowledgeBaseTags", \WHMCS\Knowledgebase\Tag::getTagTotals());
        \Menu::addContext("routeNamespace", "knowledgebase");
        \Menu::primarySidebar("supportKnowledgeBase");
        \Menu::secondarySidebar("supportKnowledgeBase");
    }
    public function getRootCategoryTemplateData()
    {
        $kbRootCategories = array();
        $rootCategories = \WHMCS\Knowledgebase\Category::rootCategories()->get();
        $i = 1;
        foreach ($rootCategories as $category) {
            $kbRootCategories[$i] = $this->getCategoryTemplateData($category);
            $i++;
        }
        return $kbRootCategories;
    }
    public function getArticleTemplateData(\WHMCS\Knowledgebase\Article $item)
    {
        $translatedItem = $item->bestTranslation();
        $editLink = "";
        if (0 < (int) \WHMCS\Session::get("adminid") && \WHMCS\User\Admin\Permission::currentAdminHasPermissionName("Manage Knowledgebase")) {
            $editLink = \App::getSystemURL() . \App::get_admin_folder_name() . "/" . "supportkb.php?action=edit&id=" . $item->id;
        }
        return array("id" => $item->id, "title" => $translatedItem->title, "urlfriendlytitle" => getModRewriteFriendlyString($translatedItem->title), "article" => strip_tags($translatedItem->article), "views" => $item->views, "editLink" => $editLink);
    }
    public function getCategoryTemplateData(\WHMCS\Knowledgebase\Category $item)
    {
        $translatedItem = $item->bestTranslation();
        $editLink = "";
        if (0 < (int) \WHMCS\Session::get("adminid") && \WHMCS\User\Admin\Permission::currentAdminHasPermissionName("Manage Knowledgebase")) {
            $editLink = \App::getSystemURL() . \App::get_admin_folder_name() . "/" . "supportkb.php?action=editcat&id=" . $item->id;
        }
        return array("id" => $item->id, "name" => $translatedItem->name, "urlfriendlyname" => getModRewriteFriendlyString($translatedItem->name), "description" => $translatedItem->description, "numarticles" => $item->articles()->count() + $item->subCategoryArticleCount, "editLink" => $editLink);
    }
}

?>