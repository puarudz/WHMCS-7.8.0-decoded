<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Domain\TopLevel;

class Category extends \WHMCS\Model\AbstractModel
{
    protected $table = "tbltld_categories";
    public $unique = array("category");
    protected $booleans = array("isPrimary");
    public static function boot()
    {
        parent::boot();
        static::addGlobalScope("order", function (\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->orderBy("tbltld_categories.display_order")->orderBy("tbltld_categories.id");
        });
    }
    public function topLevelDomains()
    {
        return $this->belongsToMany("WHMCS\\Domain\\TopLevel", "tbltld_category_pivot", "category_id", "tld_id")->withTimestamps();
    }
    public function scopeTldsIn($query, array $tlds = array())
    {
        return $query->whereHas("topLevelDomains", function ($subQuery) use($tlds) {
            $subQuery->whereIn("tld", $tlds);
        });
    }
}

?>