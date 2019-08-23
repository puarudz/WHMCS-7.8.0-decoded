<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Search;

class Client implements SearchInterface
{
    public function search($searchTerm = NULL)
    {
        if (is_array($searchTerm)) {
            $clientId = isset($searchTerm["clientId"]) ? $searchTerm["clientId"] : null;
            $searchTerm = isset($searchTerm["searchTerm"]) ? $searchTerm["searchTerm"] : "";
        } else {
            $clientId = null;
        }
        $data = array();
        if (!is_null($searchTerm)) {
            $data = $this->fuzzySearch($searchTerm, $clientId);
        }
        return $data;
    }
    public function fuzzySearch($searchTerm, $clientId = NULL)
    {
        $searchResults = array();
        $matchingClients = \WHMCS\Database\Capsule::table("tblclients");
        if ($searchTerm) {
            $matchingClients->whereRaw("CONCAT(firstname, ' ', lastname) LIKE '%" . $searchTerm . "%'")->orWhere("email", "LIKE", "%" . $searchTerm . "%")->orWhere("companyname", "LIKE", "%" . $searchTerm . "%");
            if (is_numeric($searchTerm)) {
                $matchingClients->orWhere("id", "=", (int) $searchTerm)->orWhere("id", "LIKE", "%" . (int) $searchTerm . "%");
            }
        } else {
            $matchingClients->where("status", "Active")->limit(30);
        }
        if ($clientId && !$searchTerm) {
            static $clientCount = NULL;
            if (!$clientCount) {
                $clientCount = \WHMCS\Database\Capsule::table("tblclients")->count("id");
            }
            $offsetStart = 15;
            if (15 < $clientId && 30 < $clientCount) {
                if ($clientCount < $clientId + 15) {
                    $offsetStart = 30 - ($clientCount - $clientId);
                }
                $matchingClients->offset($clientId - $offsetStart);
            }
        }
        $matchingClients->orderBy("status");
        foreach ($matchingClients->get() as $client) {
            $status = "active";
            if ($client->status != "Active") {
                $status = "inactive";
            }
            $searchResults[] = array("id" => $client->id, "name" => \WHMCS\Input\Sanitize::decode($client->firstname . " " . $client->lastname), "companyname" => \WHMCS\Input\Sanitize::decode($client->companyname), "email" => \WHMCS\Input\Sanitize::decode($client->email), "status" => $status, "active" => $client->status != "Active");
        }
        if (count($searchResults) < 1 && $searchTerm) {
            $searchResults[] = array("id" => 0 - abs(crc32($searchTerm)), "name" => \AdminLang::trans("global.norecordsfound"), "companyname" => "", "email" => \AdminLang::trans("global.searchTerm", array(":searchTerm" => $searchTerm)));
        } else {
            if (count($searchResults) < 1) {
                $searchResults[] = array("id" => -1, "name" => \AdminLang::trans("global.noClientsExist"), "companyname" => "", "email" => "");
            }
        }
        return $searchResults;
    }
}

?>