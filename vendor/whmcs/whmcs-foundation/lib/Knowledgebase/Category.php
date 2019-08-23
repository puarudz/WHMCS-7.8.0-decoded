<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Knowledgebase;

class Category extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblknowledgebasecats";
    public $timestamps = false;
    public static function findSystemTranslation($id)
    {
        return (new static())->where("catid", "=", 0)->where("id", "=", $id)->first();
    }
    public function scopeTranslationsOf($query, $id = "", $language = "")
    {
        if ($id) {
            $query = $query->where("catid", "=", $id);
        }
        $query = $query->where("language", "=", $language);
        return $query;
    }
    public function bestTranslation($language = "")
    {
        if (!$language) {
            $language = \WHMCS\Session::get("Language");
        }
        if (!$language) {
            $language = \WHMCS\Config\Setting::getValue("Language");
        }
        static $cache = array();
        if (!isset($cache[$this->id][$language])) {
            $translation = $this->scopeTranslationsOf($this->newQuery(), $this->id, $language)->first();
            if ($translation) {
                $cache[$this->id][$language] = $translation;
            } else {
                $cache[$this->id][$language] = $this;
            }
        }
        return $cache[$this->id][$language];
    }
    public function isHidden()
    {
        return $this->getAttribute("hidden") == "on";
    }
    public function ancestors($maxAncestors = 100)
    {
        $ancestors = array();
        $categoryParentId = $this->parentid;
        if ($categoryParentId) {
            $i = 0;
            while ($categoryParentId != "0") {
                $category = (new static())->find($categoryParentId);
                if (!$category) {
                    break;
                }
                $categoryParentId = $category->parentid;
                $ancestors = array_unshift($ancestors, $category);
                $i++;
                if ($maxAncestors < $i) {
                    break;
                }
            }
        }
        return $ancestors;
    }
    public function articles()
    {
        return $this->belongsToMany("\\WHMCS\\Knowledgebase\\Article", "tblknowledgebaselinks", "categoryid", "articleid");
    }
    public function subCategories()
    {
        return $this->hasMany(static::class, "parentid")->where("catid", "=", 0)->where("hidden", "=", "")->orderBy("name", "asc");
    }
    public function getSubCategoryArticleCountAttribute()
    {
        $subArticleTotal = 0;
        foreach ($this->subCategories as $sub) {
            $subArticleTotal += $sub->articles()->count() + $sub->subCategoryArticleCount;
        }
        return $subArticleTotal;
    }
    public static function rootCategories()
    {
        return static::where("parentid", "=", 0)->where("hidden", "=", "")->where("catid", "=", 0)->orderBy("name", "asc");
    }
    public static function hiddenCategoryIds()
    {
        return static::where("hidden", "=", "on")->lists("id")->all();
    }
}

?>