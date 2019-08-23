<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version723release1 extends IncrementalVersion
{
    protected $updateActions = array("addGrTld");
    protected function addGrTld()
    {
        $tld = \WHMCS\Database\Capsule::table("tbltlds")->where("tld", "=", "gr")->first();
        if ($tld) {
            $tldId = $tld->id;
        } else {
            $tldId = \WHMCS\Database\Capsule::table("tbltlds")->insertGetId(array("tld" => "gr", "created_at" => \WHMCS\Carbon::now(), "updated_at" => \WHMCS\Carbon::now()));
        }
        $ccTldCatId = \WHMCS\Database\Capsule::table("tbltld_categories")->where("category", "=", "ccTLD")->value("id");
        $pivot = \WHMCS\Database\Capsule::table("tbltld_category_pivot")->where("tld_id", "=", $tldId)->where("category_id", "=", $ccTldCatId)->first();
        if (!$pivot) {
            \WHMCS\Database\Capsule::table("tbltld_category_pivot")->insert(array("tld_id" => $tldId, "category_id" => $ccTldCatId, "created_at" => \WHMCS\Carbon::now(), "updated_at" => \WHMCS\Carbon::now()));
        }
    }
}

?>