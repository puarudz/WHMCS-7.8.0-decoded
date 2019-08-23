<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Knowledgebase;

class Article extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblknowledgebase";
    protected $fillable = array("votes", "views", "useful");
    public $timestamps = false;
    public static function boot()
    {
        parent::boot();
        static::addGlobalScope("order", function (\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->orderBy("tblknowledgebase.order")->orderBy("tblknowledgebase.id");
        });
    }
    public function scopeTranslationsOf($query, $id = "", $language = "")
    {
        if ($id) {
            $query = $query->where("parentid", "=", $id);
        }
        if ($language) {
            $query = $query->where("language", "=", $language);
        }
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
    public function categories()
    {
        return $this->belongsToMany("\\WHMCS\\Knowledgebase\\Category", "tblknowledgebaselinks", "articleid", "categoryid")->orderBy("categoryid", "asc");
    }
    public function getPrimaryCategoryAttribute()
    {
        return $this->categories()->first();
    }
    public function getSiblingsAttribute()
    {
        $categories = $this->categories()->lists("categoryid")->toArray();
        $links = (new ArticleCategoryLink())->with("article")->whereIn("categoryid", $categories)->where("articleid", "!=", $this->id)->limit(5)->get();
        $siblings = new \Illuminate\Database\Eloquent\Collection();
        foreach ($links as $link) {
            $siblings->add($link->article);
        }
        return $siblings;
    }
    public function isOrphaned()
    {
        $primaryCategory = $this->primaryCategory;
        if (!$primaryCategory || !$primaryCategory instanceof Category || !$primaryCategory->exists) {
            return true;
        }
        return false;
    }
    public static function filterOrphanedArticles(\Illuminate\Database\Eloquent\Collection $items)
    {
        return $items->filter(function ($model) {
            return !$model->isOrphaned();
        });
    }
    public static function mostViewed($limit = 5)
    {
        $items = (new static())->with("categories")->orderBy("views", "desc")->where("parentid", "=", 0)->limit(100)->get();
        $items = static::filterOrphanedArticles($items);
        $items = $items->filter(function ($model) {
            return !$model->primaryCategory->isHidden();
        });
        $items->splice($limit);
        return $items;
    }
    public function tags()
    {
        return $this->hasMany("\\WHMCS\\Knowledgebase\\Tag", "articleid");
    }
}

?>