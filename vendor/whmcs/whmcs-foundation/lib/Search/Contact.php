<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Search;

class Contact implements SearchInterface
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
        if (!is_null($searchTerm) && $clientId) {
            $client = \WHMCS\User\Client::find($clientId);
            if ($client) {
                $data = $this->fuzzySearch($searchTerm, $client);
            }
        }
        return $data;
    }
    public function fuzzySearch($searchTerm, \WHMCS\User\Client $client)
    {
        $searchResults = array();
        $matchingContacts = $client->contacts();
        if ($searchTerm) {
            $matchingContacts->whereRaw("CONCAT(firstname, ' ', lastname) LIKE '%" . $searchTerm . "%'")->orWhere("email", "LIKE", "%" . $searchTerm . "%")->orWhere("companyname", "LIKE", "%" . $searchTerm . "%");
            if (is_numeric($searchTerm)) {
                $matchingContacts->orWhere("id", "=", (int) $searchTerm)->orWhere("id", "LIKE", "%" . (int) $searchTerm . "%");
            }
        } else {
            $matchingContacts->limit(30);
        }
        foreach ($matchingContacts->get() as $contact) {
            $searchResults[] = array("id" => $contact->id, "name" => \WHMCS\Input\Sanitize::decode($contact->fullName), "companyname" => \WHMCS\Input\Sanitize::decode($contact->companyname), "email" => \WHMCS\Input\Sanitize::decode($contact->email));
        }
        return $searchResults;
    }
}

?>