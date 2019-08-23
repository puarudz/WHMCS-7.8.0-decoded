<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Download;

class Download extends \WHMCS\Model\AbstractModel
{
    protected $table = "tbldownloads";
    protected $columnMap = array("downloadCategoryId" => "category", "timesDownloaded" => "downloads", "fileLocation" => "location", "clientDownloadOnly" => "clientsonly", "isHidden" => "hidden", "isProductDownload" => "productdownload");
    protected $booleans = array("clientDownloadOnly", "isHidden", "isProductDownload");
    public function asLink()
    {
        return \WHMCS\Config\Setting::getValue("SystemURL") . "/dl.php?type=d&amp;id=" . $this->id;
    }
    public function downloadCategory()
    {
        return $this->belongsTo("WHMCS\\Download\\Category", "category");
    }
    public function products()
    {
        return $this->belongsToMany("WHMCS\\Product\\Product", "tblproduct_downloads");
    }
    public function scopeConsiderProductDownloads(\Illuminate\Database\Eloquent\Builder $query)
    {
        if (!\WHMCS\Config\Setting::getValue("DownloadsIncludeProductLinked")) {
            $query = $query->where("productDownload", false);
        }
        return $query;
    }
    public function scopeTopDownloads(\Illuminate\Database\Eloquent\Builder $query, $count = 5)
    {
        $query = $this->scopeConsiderProductDownloads($query);
        return $query->whereHas("downloadCategory", function (\Illuminate\Database\Eloquent\Builder $subQuery) {
            $subQuery->where("hidden", false);
        })->where("hidden", false)->orderBy("downloads", "desc")->limit($count);
    }
    public static function boot()
    {
        parent::boot();
        Download::saved(function (Download $download) {
            if (\WHMCS\Config\Setting::getValue("EnableTranslations")) {
                $translation = \WHMCS\Language\DynamicTranslation::firstOrNew(array("related_type" => "download.{id}.description", "related_id" => $download->id, "language" => \WHMCS\Config\Setting::getValue("Language"), "input_type" => "textarea"));
                $translation->translation = $download->getRawAttribute("description");
                $translation->save();
                $translation = \WHMCS\Language\DynamicTranslation::firstOrNew(array("related_type" => "download.{id}.title", "related_id" => $download->id, "language" => \WHMCS\Config\Setting::getValue("Language"), "input_type" => "text"));
                $translation->translation = $download->getRawAttribute("title");
                $translation->save();
            }
        });
        Download::deleted(function (Download $download) {
            if (\WHMCS\Config\Setting::getValue("EnableTranslations")) {
                \WHMCS\Language\DynamicTranslation::whereIn("related_type", array("download.{id}.description", "download.{id}.title"))->where("related_id", "=", $download->id)->delete();
            }
        });
        Download::deleting(function (Download $download) {
            if ($download->fileLocation) {
                \Storage::downloads()->deleteAllowNotPresent($download->fileLocation);
            }
        });
    }
    public function translatedNames()
    {
        return $this->hasMany("WHMCS\\Language\\DynamicTranslation", "related_id")->where("related_type", "=", "product.{id}.name")->select(array("language", "translation"));
    }
    public function translatedDescriptions()
    {
        return $this->hasMany("WHMCS\\Language\\DynamicTranslation", "related_id")->where("related_type", "=", "product.{id}.description")->select(array("language", "translation"));
    }
    public function scopeInCategory(\Illuminate\Database\Eloquent\Builder $query, $catId)
    {
        return $query->where("category", "=", $catId);
    }
    public function scopeVisible(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where("hidden", "=", "0");
    }
    public function scopeCategoryVisible(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->whereIn("category", Category::visible()->pluck("id")->toArray());
    }
    public function scopeSearch(\Illuminate\Database\Eloquent\Builder $query, $search)
    {
        return $query->where(function (\Illuminate\Database\Eloquent\Builder $query) use($search) {
            $searchPattern = "%" . $search . "%";
            return $query->orWhere("title", "like", $searchPattern)->orWhere("description", "like", $searchPattern);
        });
    }
}

?>