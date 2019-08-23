<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Registrar\GoDaddy;

class Shopper
{
    public static function findShopperId($userId, array $params)
    {
        static $foundIds = array();
        $returnValue = null;
        if (!array_key_exists($userId, $foundIds)) {
            $foundIds[$userId] = array();
        }
        $domainId = 0;
        $domainName = "";
        if (array_key_exists("domainid", $params) && $params["domainid"]) {
            $domainId = $params["domainid"];
        }
        if ($domainId && array_key_exists($domainId, $foundIds[$userId])) {
            $returnValue = $foundIds[$userId][$domainId];
        }
        if (!$returnValue && $domainId) {
            $returnValue = \WHMCS\Domain\Extra::where("domain_id", $domainId)->where("name", "GoDaddy Shopper")->value("value");
            if (array_key_exists("domainname", $params) && $params["domainname"]) {
                $domainName = $params["domainname"];
            }
            if (!$returnValue && $domainName) {
                try {
                    $response = Client::factory($params["apiKey"], $params["apiSecret"], $params["TestMode"])->get("domains/" . $domainName);
                    $response = json_decode($response, true);
                    $returnValue = $response["subaccountId"];
                    $extra = \WHMCS\Domain\Extra::firstOrNew(array("domain_id" => $domainId, "name" => "GoDaddy Shopper"));
                    $extra->value = $returnValue;
                    $extra->save();
                    $foundIds[$userId][$domainId] = $returnValue;
                } catch (\Exception $e) {
                }
            }
        }
        if (!$returnValue && array_key_exists("default", $foundIds[$userId]) && $foundIds[$userId]["default"]) {
            $returnValue = $foundIds[$userId]["default"];
        }
        if (!$returnValue) {
            $ids = \WHMCS\Database\Capsule::table("tbldomains")->leftJoin("tbldomains_extra", function ($join) {
                $join->on("tbldomains_extra.domain_id", "=", "tbldomains.id")->where("tbldomains_extra.name", "=", "GoDaddy Shopper");
            })->where("tbldomains.registrar", "godaddy")->where("tbldomains.userid", $userId)->whereNotNull("tbldomains_extra.name")->pluck("value", "tbldomains.id");
            foreach ($ids as $domainId => $id) {
                if ($id && is_numeric($id)) {
                    $returnValue = $id;
                    $foundIds[$userId][$domainId] = $returnValue;
                    $foundIds[$userId]["default"] = $returnValue;
                    break;
                }
            }
            if (!$returnValue) {
                $domains = \WHMCS\Database\Capsule::table("tbldomains")->where("registrar", "godaddy")->where("userid", $userId)->get();
                foreach ($domains as $domain) {
                    try {
                        $response = Client::factory($params["apiKey"], $params["apiSecret"], $params["TestMode"])->get("domains/" . $domain->domain);
                        $response = json_decode($response, true);
                        $returnValue = $response["subaccountId"];
                        $extra = \WHMCS\Domain\Extra::firstOrNew(array("domain_id" => $domain->id, "name" => "GoDaddy Shopper"));
                        $extra->value = $returnValue;
                        $extra->save();
                        $foundIds[$userId][$domain->id] = $returnValue;
                        $foundIds[$userId]["default"] = $returnValue;
                        break;
                    } catch (\Exception $e) {
                    }
                }
            }
        }
        return $returnValue;
    }
    public static function create(array $params)
    {
        $response = Client::factory($params["apiKey"], $params["apiSecret"], $params["TestMode"])->post("shoppers/subaccount", array("json" => array("email" => $params["email"], "externalId" => (int) $params["userid"], "marketId" => "en-US", "nameFirst" => $params["firstname"], "nameLast" => $params["lastname"], "password" => generateFriendlyPassword(16))));
        $response = json_decode($response, true);
        \WHMCS\Database\Capsule::table("tbldomains_extra")->insert(array("domain_id" => $params["domainid"], "name" => "GoDaddy Shopper", "value" => $response["shopperId"]));
        return $response["shopperId"];
    }
}

?>