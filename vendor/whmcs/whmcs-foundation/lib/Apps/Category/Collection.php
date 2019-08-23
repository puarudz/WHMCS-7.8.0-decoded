<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Apps\Category;

class Collection
{
    public $categories = array();
    public function __construct()
    {
        foreach ((new \WHMCS\Apps\Feed())->categories() as $values) {
            $this->categories[$values["slug"]] = new Model($values);
        }
    }
    public function all()
    {
        return $this->categories;
    }
    public function first()
    {
        foreach ($this->categories as $category) {
            return $category;
        }
        return null;
    }
    public function homeFeatured()
    {
        $categoriesToReturn = array();
        foreach ($this->all() as $slug => $category) {
            if ($category->includeInHomeFeatured()) {
                $categoriesToReturn[$slug] = $category;
            }
        }
        return $categoriesToReturn;
    }
    public function getCategoryBySlug($slug)
    {
        return isset($this->categories[$slug]) ? $this->categories[$slug] : null;
    }
    public function getAllFeaturedKeys()
    {
        $allFeaturedAppKeys = array();
        foreach ($this->categories as $category) {
            $allFeaturedAppKeys = array_merge($allFeaturedAppKeys, $category->getFeaturedAppKeys());
        }
        return $allFeaturedAppKeys;
    }
}

?>