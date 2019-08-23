<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Product\Group;

class Feature extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblproduct_group_features";
    public static function boot()
    {
        parent::boot();
        Feature::saved(function (Feature $feature) {
            if (\WHMCS\Config\Setting::getValue("EnableTranslations")) {
                $translation = \WHMCS\Language\DynamicTranslation::firstOrNew(array("related_type" => "product_group_feature.{id}.feature", "related_id" => $feature->id, "language" => \WHMCS\Config\Setting::getValue("Language"), "input_type" => "text"));
                $translation->translation = $feature->feature ?: "";
                $translation->save();
            }
        });
        Feature::deleted(function (Feature $product) {
            if (\WHMCS\Config\Setting::getValue("EnableTranslations")) {
                \WHMCS\Language\DynamicTranslation::where("related_type", "=", "product_group_feature.{id}.feature")->where("related_id", "=", $product->id)->delete();
            }
        });
        static::addGlobalScope("order", function (\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->orderBy("tblproduct_group_features.order")->orderBy("tblproduct_group_features.id");
        });
    }
    public function productGroup()
    {
        return $this->belongsTo("WHMCS\\Product\\Group");
    }
    public function translatedFeatures()
    {
        return $this->hasMany("WHMCS\\Language\\DynamicTranslation", "related_id")->where("related_type", "=", "product_group_features.{id}.feature")->select(array("language", "translation"));
    }
    public function getFeatureAttribute($feature)
    {
        $translatedFeature = "";
        if (\WHMCS\Config\Setting::getValue("EnableTranslations")) {
            $translatedFeature = \Lang::trans("product_group_features." . $this->id . ".feature", array(), "dynamicMessages");
        }
        return strlen($translatedFeature) && $translatedFeature != "product_group_features." . $this->id . ".feature" ? $translatedFeature : $feature;
    }
}

?>