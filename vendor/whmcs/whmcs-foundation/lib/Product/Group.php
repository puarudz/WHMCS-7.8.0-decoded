<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Product;

class Group extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblproductgroups";
    protected $columnMap = array("orderFormTemplate" => "orderfrmtpl", "disabledPaymentGateways" => "disabledgateways", "isHidden" => "hidden", "displayOrder" => "order");
    protected $booleans = array("isHidden");
    protected $commaSeparated = array("disabledPaymentGateways");
    public static function boot()
    {
        parent::boot();
        Group::created(function (Group $group) {
            if (\WHMCS\Config\Setting::getValue("EnableTranslations")) {
                \WHMCS\Language\DynamicTranslation::whereIn("related_type", array("product_group.{id}.headline", "product_group.{id}.name", "product_group.{id}.tagline"))->where("related_id", "=", 0)->update(array("related_id" => $group->id));
            }
        });
        Group::saved(function (Group $group) {
            if (\WHMCS\Config\Setting::getValue("EnableTranslations")) {
                $translation = \WHMCS\Language\DynamicTranslation::firstOrNew(array("related_type" => "product_group.{id}.headline", "related_id" => $group->id, "language" => \WHMCS\Config\Setting::getValue("Language"), "input_type" => "text"));
                $translation->translation = $group->getRawAttribute("headline") ?: "";
                $translation->save();
                $translation = \WHMCS\Language\DynamicTranslation::firstOrNew(array("related_type" => "product_group.{id}.name", "related_id" => $group->id, "language" => \WHMCS\Config\Setting::getValue("Language"), "input_type" => "text"));
                $translation->translation = $group->getRawAttribute("name") ?: "";
                $translation->save();
                $translation = \WHMCS\Language\DynamicTranslation::firstOrNew(array("related_type" => "product_group.{id}.tagline", "related_id" => $group->id, "language" => \WHMCS\Config\Setting::getValue("Language"), "input_type" => "text"));
                $translation->translation = $group->getRawAttribute("tagline") ?: "";
                $translation->save();
            }
        });
        Group::deleted(function (Group $group) {
            if (\WHMCS\Config\Setting::getValue("EnableTranslations")) {
                \WHMCS\Language\DynamicTranslation::whereIn("related_type", array("product_group.{id}.headline", "product_group.{id}.name", "product_group.{id}.tagline"))->where("related_id", "=", $group->id)->delete();
            }
        });
        static::addGlobalScope("order", function (\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->orderBy("tblproductgroups.order")->orderBy("tblproductgroups.id");
        });
    }
    public function products()
    {
        return $this->hasMany("WHMCS\\Product\\Product", "gid");
    }
    public function features()
    {
        return $this->hasMany("WHMCS\\Product\\Group\\Feature", "product_group_id")->orderBy("order");
    }
    public function scopeNotHidden($query)
    {
        return $query->where("hidden", "0")->orWhere("hidden", "");
    }
    public function scopeSorted($query)
    {
        return $query->orderBy("order");
    }
    public function orderFormTemplate()
    {
        return $this->orderFormTemplate == "" ? \WHMCS\View\Template\OrderForm::getDefault() : \WHMCS\View\Template\OrderForm::find($this->orderFormTemplate);
    }
    public function getAvailableBillingCycles()
    {
        switch ($this->paymentType) {
            case "free":
                return array("free");
            case "onetime":
                return array("onetime");
            case "recurring":
                $validCycles = array();
                $productPricing = new Pricing();
                $productPricing->loadPricing("product", $this->id);
                return $productPricing->getAvailableBillingCycles();
        }
        return array();
    }
    public function translatedNames()
    {
        return $this->hasMany("WHMCS\\Language\\DynamicTranslation", "related_id")->where("related_type", "=", "product_group.{id}.name")->select(array("language", "translation"));
    }
    public function translatedHeadlines()
    {
        return $this->hasMany("WHMCS\\Language\\DynamicTranslation", "related_id")->where("related_type", "=", "product_group.{id}.headline")->select(array("language", "translation"));
    }
    public function translatedTaglines()
    {
        return $this->hasMany("WHMCS\\Language\\DynamicTranslation", "related_id")->where("related_type", "=", "product_group.{id}.tagline")->select(array("language", "translation"));
    }
    public function getNameAttribute($name)
    {
        $translatedName = "";
        if (\WHMCS\Config\Setting::getValue("EnableTranslations")) {
            $translatedName = \Lang::trans("product_group." . $this->id . ".name", array(), "dynamicMessages");
        }
        return strlen($translatedName) && $translatedName != "product_group." . $this->id . ".name" ? $translatedName : $name;
    }
    public function getHeadlineAttribute($headline)
    {
        $translatedHeadline = "";
        if (\WHMCS\Config\Setting::getValue("EnableTranslations")) {
            $translatedHeadline = \Lang::trans("product_group." . $this->id . ".headline", array(), "dynamicMessages");
        }
        return strlen($translatedHeadline) && $translatedHeadline != "product_group." . $this->id . ".headline" ? $translatedHeadline : $headline;
    }
    public function getTaglineAttribute($tagline)
    {
        $translatedTagline = "";
        if (\WHMCS\Config\Setting::getValue("EnableTranslations")) {
            $translatedTagline = \Lang::trans("product_group." . $this->id . ".tagline", array(), "dynamicMessages");
        }
        return strlen($translatedTagline) && $translatedTagline != "product_group." . $this->id . ".tagline" ? $translatedTagline : $tagline;
    }
    public static function getGroupName($groupId, $fallback = "", $language = NULL)
    {
        $name = \Lang::trans("product_group." . $groupId . ".name", array(), "dynamicMessages", $language);
        if ($name == "product_group." . $groupId . ".name") {
            if ($fallback) {
                return $fallback;
            }
            return Group::find($groupId, array("name"))->name;
        }
        return $name;
    }
    public static function getHeadline($groupId, $fallback = "", $language = NULL)
    {
        $headline = \Lang::trans("product_group." . $groupId . ".headline", array(), "dynamicMessages", $language);
        if ($headline == "product_group." . $groupId . ".headline") {
            if ($fallback) {
                return $fallback;
            }
            return Group::find($groupId, array("headline"))->headline;
        }
        return $headline;
    }
    public static function getTagline($groupId, $fallback = "", $language = NULL)
    {
        $tagline = \Lang::trans("product_group." . $groupId . ".tagline", array(), "dynamicMessages", $language);
        if ($tagline == "product_group." . $groupId . ".tagline") {
            if ($fallback) {
                return $fallback;
            }
            return Group::find($groupId, array("tagline"))->tagline;
        }
        return $tagline;
    }
}

?>