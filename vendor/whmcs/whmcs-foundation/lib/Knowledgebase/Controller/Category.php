<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Knowledgebase\Controller;

class Category
{
    public function view(\Psr\Http\Message\ServerRequestInterface $request)
    {
        $id = $request->getAttribute("categoryId");
        $category = \WHMCS\Knowledgebase\Category::findSystemTranslation($id);
        if (!$category) {
            return new \Zend\Diactoros\Response\RedirectResponse(routePath("knowledgebase-index"));
        }
        $view = new \WHMCS\Knowledgebase\View\Category();
        $translatedCategory = $category->bestTranslation();
        $oldestCategoryParentId = 0;
        $categoryAncestors = $category->ancestors();
        foreach ($categoryAncestors as $ancestor) {
            $oldestCategoryParentId = $ancestor->id;
            $view->addToBreadCrumb(routePath("knowledgebase-category-view", $ancestor->id, $ancestor->bestTranslation()->name), $ancestor->bestTranslation()->name);
        }
        \Menu::addContext("kbCategoryParentId", (int) $oldestCategoryParentId);
        \Menu::addContext("kbCategoryId", $category->id);
        $view->assign("catid", $category->id);
        $view->assign("catname", $translatedCategory->name);
        $view->assign("tag", "");
        $view->assign("searchterm", "");
        $view->addToBreadCrumb(routePath("knowledgebase-category-view", $category->id, $translatedCategory->name), $translatedCategory->name);
        $subCategories = $category->subCategories;
        $i = 1;
        $kbCategories = array();
        foreach ($subCategories as $subCategory) {
            $kbCategories[$i] = $view->getCategoryTemplateData($subCategory);
            $i++;
        }
        \Menu::addContext("kbCategories", $kbCategories);
        $view->assign("kbcats", $kbCategories);
        $articles = $category->articles;
        $articles = $articles->sort(function (\WHMCS\Knowledgebase\Article $model1, \WHMCS\Knowledgebase\Article $model2) {
            $diff = $model1->order - $model2->order;
            if (!$diff) {
                $diff = strcmp($model1->bestTranslation()->title, $model2->bestTranslation()->title);
            }
            return $diff;
        });
        $kbArticles = array();
        foreach ($articles as $article) {
            $kbArticles[] = $view->getArticleTemplateData($article);
        }
        $view->assign("kbarticles", $kbArticles);
        return $view;
    }
}

?>