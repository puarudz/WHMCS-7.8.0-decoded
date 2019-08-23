<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Support;

class Department extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblticketdepartments";
    public $timestamps = false;
    protected $columnMap = array("clientsOnly" => "clientsonly", "pipeRepliesOnly" => "piperepliesonly", "noAutoResponder" => "noautoresponder", "feedbackRequest" => "feedback_request");
    public function scopeEnforceUserVisibilityPermissions(\Illuminate\Database\Eloquent\Builder $query)
    {
        if (!\WHMCS\Session::get("uid")) {
            return $query->where("hidden", "")->where("clientsonly", "");
        }
        return $query->where("hidden", "");
    }
    public static function boot()
    {
        parent::boot();
        static::addGlobalScope("order", function (\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->orderBy("tblticketdepartments.order");
        });
        self::saved(function (\self $department) {
            if (\WHMCS\Config\Setting::getValue("EnableTranslations")) {
                $translation = \WHMCS\Language\DynamicTranslation::firstOrNew(array("related_type" => "ticket_department.{id}.description", "related_id" => $department->id, "language" => \WHMCS\Config\Setting::getValue("Language"), "input_type" => "text"));
                $translation->translation = $department->getRawAttribute("description") ?: "";
                $translation->save();
                $translation = \WHMCS\Language\DynamicTranslation::firstOrNew(array("related_type" => "ticket_department.{id}.name", "related_id" => $department->id, "language" => \WHMCS\Config\Setting::getValue("Language"), "input_type" => "text"));
                $translation->translation = $department->getRawAttribute("name") ?: "";
                $translation->save();
            }
        });
        self::deleted(function (\self $department) {
            if (\WHMCS\Config\Setting::getValue("EnableTranslations")) {
                \WHMCS\Language\DynamicTranslation::whereIn("related_type", array("ticket_department.{id}.description", "ticket_department.{id}.name"))->where("related_id", "=", $department->id)->delete();
            }
        });
    }
    public function getNameAttribute($name)
    {
        $translatedName = "";
        if (\WHMCS\Config\Setting::getValue("EnableTranslations")) {
            if (\WHMCS\Session::get("adminid") && !\WHMCS\Session::get("uid")) {
                $lang = \AdminLang::self();
            } else {
                $lang = \Lang::self();
            }
            $translatedName = $lang->trans("ticket_department." . $this->id . ".name", array(), "dynamicMessages");
        }
        return strlen($translatedName) && $translatedName != "ticket_department." . $this->id . ".name" ? $translatedName : $name;
    }
    public function getDescriptionAttribute($description)
    {
        $translatedDescription = "";
        if (\WHMCS\Config\Setting::getValue("EnableTranslations")) {
            $translatedDescription = \Lang::trans("ticket_department." . $this->id . ".description", array(), "dynamicMessages");
        }
        return strlen($translatedDescription) && $translatedDescription != "ticket_department." . $this->id . ".description" ? $translatedDescription : $description;
    }
    public function translatedNames()
    {
        return $this->hasMany("WHMCS\\Language\\DynamicTranslation", "related_id")->where("related_type", "=", "ticket_department.{id}.name")->select(array("language", "translation"));
    }
    public function translatedDescriptions()
    {
        return $this->hasMany("WHMCS\\Language\\DynamicTranslation", "related_id")->where("related_type", "=", "ticket_department.{id}.description")->select(array("language", "translation"));
    }
    public static function getDepartmentName($departmentId, $fallback = "", $language = NULL)
    {
        $name = \Lang::trans("ticket_department." . $departmentId . ".name", array(), "dynamicMessages", $language);
        if ($name == "ticket_department." . $departmentId . ".name") {
            if ($fallback) {
                return $fallback;
            }
            return self::find($departmentId, array("name"))->name;
        }
        return $name;
    }
    public static function getDepartmentDescription($departmentId, $fallback = "", $language = NULL)
    {
        $description = \Lang::trans("ticket_department." . $departmentId . ".description", array(), "dynamicMessages", $language);
        if ($description == "ticket_department." . $departmentId . ".description") {
            if ($fallback) {
                return $fallback;
            }
            return self::find($departmentId, array("description"))->description;
        }
        return $description;
    }
    public function tickets()
    {
        return $this->hasMany("WHMCS\\Support\\Ticket", "did");
    }
}

?>