<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS;

class CustomField extends Model\AbstractModel
{
    protected $table = "tblcustomfields";
    protected $columnMap = array("relatedId" => "relid", "regularExpression" => "regexpr", "showOnOrderForm" => "showorder", "showOnInvoice" => "showinvoice");
    protected $commaSeparated = array("fieldOptions");
    protected $fillable = array("type", "relid", "fieldName", "fieldType");
    public static function boot()
    {
        parent::boot();
        CustomField::created(function (CustomField $customField) {
            if (Config\Setting::getValue("EnableTranslations")) {
                Language\DynamicTranslation::whereIn("related_type", array("custom_field.{id}.name", "custom_field.{id}.description"))->where("related_id", "=", 0)->update(array("related_id" => $customField->id));
            }
        });
        CustomField::saved(function (CustomField $customField) {
            if (Config\Setting::getValue("EnableTranslations")) {
                $translation = Language\DynamicTranslation::firstOrNew(array("related_type" => "custom_field.{id}.name", "related_id" => $customField->id, "language" => Config\Setting::getValue("Language"), "input_type" => "text"));
                $translation->translation = $customField->getRawAttribute("fieldName") ?: $customField->getRawAttribute("fieldname") ?: "";
                $translation->save();
                $translation = Language\DynamicTranslation::firstOrNew(array("related_type" => "custom_field.{id}.description", "related_id" => $customField->id, "language" => Config\Setting::getValue("Language"), "input_type" => "text"));
                $translation->translation = $customField->getRawAttribute("description") ?: "";
                $translation->save();
            }
        });
        CustomField::deleted(function (CustomField $customField) {
            if (Config\Setting::getValue("EnableTranslations")) {
                Language\DynamicTranslation::whereIn("related_type", array("custom_field.{id}.name", "custom_field.{id}.description"))->where("related_id", "=", $customField->id)->delete();
            }
            CustomField\CustomFieldValue::where("fieldid", "=", $customField->id)->delete();
        });
        static::addGlobalScope("order", function (\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->orderBy("tblcustomfields.sortorder")->orderBy("tblcustomfields.id");
        });
    }
    public function scopeClientFields(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where("type", "=", "client");
    }
    public function scopeProductFields(\Illuminate\Database\Eloquent\Builder $query, $productId)
    {
        return $query->where("type", "=", "product")->where("relid", "=", $productId);
    }
    public function scopeSupportFields(\Illuminate\Database\Eloquent\Builder $query, $departmentId)
    {
        return $query->where("type", "=", "support")->where("relid", "=", $departmentId);
    }
    public function scopeAddonFields(\Illuminate\Database\Eloquent\Builder $query, $addonId)
    {
        return $query->where("type", "=", "addon")->where("relid", "=", $addonId);
    }
    public function product()
    {
        return $this->hasOne("WHMCS\\Product\\Product", "id", "relid");
    }
    public function addon()
    {
        return $this->hasOne("WHMCS\\Product\\Addon", "id", "relid");
    }
    public function getFieldNameAttribute($fieldName)
    {
        $translatedFieldName = "";
        if (Config\Setting::getValue("EnableTranslations")) {
            $translatedFieldName = \Lang::trans("custom_field." . $this->id . ".name", array(), "dynamicMessages");
        }
        return strlen($translatedFieldName) && $translatedFieldName != "custom_field." . $this->id . ".name" ? $translatedFieldName : $fieldName;
    }
    public function getDescriptionAttribute($description)
    {
        $translatedDescription = "";
        if (Config\Setting::getValue("EnableTranslations")) {
            $translatedDescription = \Lang::trans("custom_field." . $this->id . ".description", array(), "dynamicMessages");
        }
        return strlen($translatedDescription) && $translatedDescription != "custom_field." . $this->id . ".description" ? $translatedDescription : $description;
    }
    public function customFieldValues()
    {
        return $this->hasMany("WHMCS\\CustomField\\CustomFieldValue", "fieldid");
    }
    public static function getFieldName($fieldId, $fallback = "", $language = NULL)
    {
        $fieldName = \Lang::trans("custom_field." . $fieldId . ".name", array(), "dynamicMessages", $language);
        if ($fieldName == "custom_field." . $fieldId . ".name") {
            if ($fallback) {
                return $fallback;
            }
            return CustomField::find($fieldId, array("fieldname"))->fieldName;
        }
        return $fieldName;
    }
    public static function getDescription($fieldId, $fallback = "", $language = NULL)
    {
        $description = \Lang::trans("custom_field." . $fieldId . ".description", array(), "dynamicMessages", $language);
        if ($description == "custom_field." . $fieldId . ".description") {
            if ($fallback) {
                return $fallback;
            }
            return CustomField::find($fieldId, array("description"))->description;
        }
        return $description;
    }
}

?>