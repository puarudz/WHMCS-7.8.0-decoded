<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Product;

class Addon extends \WHMCS\Model\AbstractModel
{
    protected $table = "tbladdons";
    protected $columnMap = array("applyTax" => "tax", "showOnOrderForm" => "showorder", "welcomeEmailTemplateId" => "welcomeemail", "autoLinkCriteria" => "autolinkby", "isHidden" => "hidden");
    protected $booleans = array("applyTax", "showOnOrderForm", "suspendProduct", "isHidden", "retired");
    protected $commaSeparated = array("packages", "downloads");
    protected $casts = array("autolinkby" => "array");
    public static function boot()
    {
        parent::boot();
        static::addGlobalScope("ordered", function (\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->orderBy("tbladdons.weight")->orderBy("tbladdons.name");
        });
        Addon::saved(function (Addon $addon) {
            if (\WHMCS\Config\Setting::getValue("EnableTranslations")) {
                $translation = \WHMCS\Language\DynamicTranslation::firstOrNew(array("related_type" => "product_addon.{id}.description", "related_id" => $addon->id, "language" => \WHMCS\Config\Setting::getValue("Language"), "input_type" => "textarea"));
                $translation->translation = $addon->getRawAttribute("description") ?: "";
                $translation->save();
                $translation = \WHMCS\Language\DynamicTranslation::firstOrNew(array("related_type" => "product_addon.{id}.name", "related_id" => $addon->id, "language" => \WHMCS\Config\Setting::getValue("Language"), "input_type" => "text"));
                $translation->translation = $addon->getRawAttribute("name") ?: "";
                $translation->save();
            }
        });
        Addon::deleted(function (Addon $addon) {
            if (\WHMCS\Config\Setting::getValue("EnableTranslations")) {
                \WHMCS\Language\DynamicTranslation::whereIn("related_type", array("product_addon.{id}.description", "product_addon.{id}.name"))->where("related_id", "=", $addon->id)->delete();
            }
        });
    }
    public function scopeShowOnOrderForm(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where("showorder", "=", 1);
    }
    public function scopeIsHidden(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where("hidden", "=", 1);
    }
    public function scopeIsNotHidden(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where("hidden", "=", 0);
    }
    public function scopeIsRetired(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where("retired", "=", 1);
    }
    public function scopeIsNotRetired(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where("retired", "=", 0);
    }
    public function scopeAvailableOnOrderForm(\Illuminate\Database\Eloquent\Builder $query, array $addons = array())
    {
        $query->where(function (\Illuminate\Database\Eloquent\Builder $query) {
            $query->where("showorder", 1)->where("retired", 0);
            if (defined("CLIENTAREA")) {
                $query->where("hidden", 0);
            }
        });
        if (0 < count($addons)) {
            $query->orWhere(function (\Illuminate\Database\Eloquent\Builder $query) use($addons) {
                $query->where("showorder", 1)->where("retired", 0)->whereIn("id", $addons);
            });
        }
        return $query;
    }
    public function scopeSorted($query)
    {
        return $query->orderBy("weight");
    }
    public function welcomeEmailTemplate()
    {
        return $this->hasOne("WHMCS\\Mail\\Template", "id", "welcomeemail");
    }
    public function getNameAttribute($name)
    {
        $translatedName = "";
        if (\WHMCS\Config\Setting::getValue("EnableTranslations")) {
            $translatedName = \Lang::trans("product_addon." . $this->id . ".name", array(), "dynamicMessages");
        }
        return strlen($translatedName) && $translatedName != "product_addon." . $this->id . ".name" ? $translatedName : $name;
    }
    public function getDescriptionAttribute($description)
    {
        $translatedDescription = "";
        if (\WHMCS\Config\Setting::getValue("EnableTranslations")) {
            $translatedDescription = \Lang::trans("product_addon." . $this->id . ".description", array(), "dynamicMessages");
        }
        return strlen($translatedDescription) && $translatedDescription != "product_addon." . $this->id . ".description" ? $translatedDescription : $description;
    }
    public function customFields()
    {
        return $this->hasMany("WHMCS\\CustomField", "relid")->where("type", "=", "addon")->orderBy("sortorder");
    }
    public function serviceAddons()
    {
        return $this->hasMany("WHMCS\\Service\\Addon", "addonid");
    }
    public function moduleConfiguration()
    {
        return $this->hasMany("WHMCS\\Config\\Module\\ModuleConfiguration", "entity_id")->where("entity_type", "=", "addon");
    }
    public function translatedNames()
    {
        return $this->hasMany("WHMCS\\Language\\DynamicTranslation", "related_id")->where("related_type", "=", "product_addon.{id}.name")->select(array("language", "translation"));
    }
    public function translatedDescriptions()
    {
        return $this->hasMany("WHMCS\\Language\\DynamicTranslation", "related_id")->where("related_type", "=", "product_addon.{id}.description")->select(array("language", "translation"));
    }
    public static function getAddonName($addonId, $fallback = "", $language = NULL)
    {
        $name = \Lang::trans("product_addon." . $addonId . ".name", array(), "dynamicMessages", $language);
        if ($name == "product_addon." . $addonId . ".name") {
            if ($fallback) {
                return $fallback;
            }
            return Addon::find($addonId, array("name"))->name;
        }
        return $name;
    }
    public static function getAddonDescription($addonId, $fallback = "", $language = NULL)
    {
        $description = \Lang::trans("product_addon." . $addonId . ".description", array(), "dynamicMessages", $language);
        if ($description == "product_addon." . $addonId . ".description") {
            if ($fallback) {
                return $fallback;
            }
            return Product::find($addonId, array("description"))->description;
        }
        return $description;
    }
    public function pricing($currency = NULL)
    {
        if (is_null($this->pricingCache)) {
            $this->pricingCache = new Pricing($this, $currency);
        }
        return $this->pricingCache;
    }
    public function isFree()
    {
        return $this->billingCycle == "free";
    }
    public function isOneTime()
    {
        return $this->billingCycle == "onetime";
    }
    public function scopeMarketConnect(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where("module", "marketconnect");
    }
    public function getProductKeyAttribute($value)
    {
        return $this->moduleConfiguration()->where("setting_name", "configoption1")->first()->value;
    }
    public function isMarketConnectAddon()
    {
        return $this->module == "marketconnect";
    }
    public function getServiceKeyAttribute($value)
    {
        $productKey = $this->productKey;
        $parts = explode("_", $productKey, 2);
        return !empty($parts[0]) ? $parts[0] : null;
    }
    public function isValidForUpgrade(Addon $addon)
    {
        if ($this->isMarketConnectAddon() && !empty($addon->serviceKey) && $this->serviceKey == $addon->serviceKey) {
            return true;
        }
        return false;
    }
    public function isVisibleOnOrderForm(array $addonIds = array())
    {
        $inClientArea = defined("CLIENTAREA");
        $inAdminArea = defined("ADMINAREA");
        if (!$this->retired && $this->showOnOrderForm || $inAdminArea || $inClientArea && (!$this->isHidden || !in_array($this->id, $addonIds))) {
            return true;
        }
        return false;
    }
    public static function getAddonDropdownValues($currentAddonId = 0)
    {
        $addonCollection = self::all();
        $dropdownOptions = array();
        foreach ($addonCollection as $addon) {
            if ($addon->retired && $currentAddonId != $addon->id) {
                continue;
            }
            $dropdownOptions[$addon->id] = $addon->name;
        }
        return $dropdownOptions;
    }
}

?>