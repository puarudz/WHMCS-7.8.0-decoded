<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Knowledgebase\Controller;

class Knowledgebase
{
    public function index()
    {
        $view = new \WHMCS\Knowledgebase\View\Index();
        $pageicon = "images/knowledgebase_big.gif";
        $view->assign("kbcats", $view->getRootCategoryTemplateData());
        $routeSetting = \WHMCS\Config\Setting::getValue("RouteUriPathMode");
        $seoSetting = $routeSetting == \WHMCS\Route\UriPath::MODE_REWRITE ? 1 : 0;
        $view->assign("seofriendlyurls", $seoSetting);
        $articlesMostViewed = \WHMCS\Knowledgebase\Article::mostViewed();
        $kbMostViews = array();
        foreach ($articlesMostViewed as $item) {
            $kbMostViews[] = $view->getArticleTemplateData($item);
        }
        $view->assign("kbmostviews", $kbMostViews);
        return $view;
    }
    public function viewTag(\Psr\Http\Message\ServerRequestInterface $request)
    {
        if (!($userProvidedTag = $request->getAttribute("tag", ""))) {
            $query = $request->getQueryParams();
            $post = $request->getParsedBody();
            $userInput = new \Symfony\Component\HttpFoundation\ParameterBag(array_merge($query, $post));
            $userProvidedTag = $userInput->get("tag", "");
        }
        $tagForLookup = str_replace("-", "%", \WHMCS\Input\Sanitize::decode($userProvidedTag));
        $tagName = \WHMCS\Input\Sanitize::makeSafeForOutput(str_replace("%", " ", $tagForLookup));
        $view = new \WHMCS\Knowledgebase\View\Category();
        $view->addToBreadCrumb(routePath("knowledgebase-tag-view", $tagName), \Lang::trans("kbviewingarticlestagged") . " " . $tagName);
        $view->assign("tag", $tagName);
        $view->assign("kbcats", array());
        $kbArticles = array();
        $articles = \WHMCS\Knowledgebase\Article::whereHas("tags", function ($model) use($tagForLookup) {
            $model->tag($tagForLookup);
        })->orderBy("order", "asc")->orderBy("title", "asc")->get();
        foreach ($articles as $article) {
            $kbArticles[] = $view->getArticleTemplateData($article);
        }
        $view->assign("kbarticles", $kbArticles);
        return $view;
    }
    public function search(\Psr\Http\Message\ServerRequestInterface $request)
    {
        $query = $request->getQueryParams();
        $attributes = $request->getAttributes();
        $post = $request->getParsedBody();
        $userInput = new \Symfony\Component\HttpFoundation\ParameterBag(array_merge($query, $attributes, $post));
        $searchTerm = $userInput->get("search", "");
        if (!$searchTerm) {
            return new \Zend\Diactoros\Response\RedirectResponse(routePath("knowledgebase-index"));
        }
        $view = new \WHMCS\Knowledgebase\View\Category();
        $view->assign("searchterm", $searchTerm);
        $view->assign("tag", "");
        $searchableCategories = array();
        $categoryId = $userInput->get("catid", $userInput->get("kbcid", 0));
        if ($categoryId) {
            $category = \WHMCS\Knowledgebase\Category::find($categoryId);
            if ($category) {
                $searchableCategories[] = $category->id;
                $translatedCategory = $category->bestTranslation();
                $oldestCategoryParentId = 0;
                $categoryAncestors = $category->ancestors();
                foreach ($categoryAncestors as $ancestor) {
                    $searchableCategories[] = $oldestCategoryParentId = $ancestor->id;
                    $view->addToBreadCrumb(routePath("knowledgebase-category-view", $ancestor->id, $ancestor->bestTranslation()->name), $ancestor->bestTranslation()->name);
                }
                \Menu::addContext("kbCategoryParentId", (int) $oldestCategoryParentId);
                \Menu::addContext("kbCategoryId", $category->id);
                $view->assign("catid", $category->id);
                $view->assign("catname", $translatedCategory->name);
                $view->addToBreadCrumb(routePath("knowledgebase-category-view", $category->id, $translatedCategory->name), $translatedCategory->name);
            }
        }
        $view->addToBreadCrumb(routePath("knowledgebase-search", $searchTerm), \Lang::trans("knowledgebasesearch"));
        $kbArticles = array();
        $articlesQuery = new \WHMCS\Knowledgebase\Article();
        $articlesQuery = $articlesQuery->distinct();
        $searchWords = explode(" ", \WHMCS\Input\Sanitize::decode($searchTerm));
        if (1 < count($searchWords)) {
            foreach ($searchWords as $i => $searchWord) {
                $articlesQuery->where(function ($query) use($searchWord) {
                    $query->where("title", "like", "%" . $searchWord . "%")->orWhere("article", "like", "%" . $searchWord . "%");
                });
            }
        } else {
            $articlesQuery->where("title", "like", "%" . $searchWords[0] . "%")->orWhere("article", "like", "%" . $searchWords[0] . "%");
        }
        $articlesQuery->with("categories");
        $articles = $articlesQuery->get();
        foreach ($articles as $article) {
            $applicableCategory = false;
            foreach ($article->categories as $associatedCategory) {
                if ($associatedCategory->isHidden()) {
                    continue 2;
                }
                if (in_array($associatedCategory->id, $searchableCategories)) {
                    $applicableCategory = true;
                }
            }
            if (!$searchableCategories || $searchableCategories && !$applicableCategory) {
                $kbArticles[] = $view->getArticleTemplateData($article);
            }
        }
        $view->assign("kbcats", array());
        $view->assign("kbarticles", $kbArticles);
        return $view;
    }
}

?>