<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Authentication\Remote\Management\Client;

class Controller
{
    public function getLinks(\WHMCS\Http\Message\ServerRequest $request)
    {
        try {
            $responseData["data"] = array();
            $remoteAuth = \DI::make("remoteAuth");
            if (count($remoteAuth->getEnabledProviders()) === 0) {
                return new \WHMCS\Http\Message\JsonResponse($responseData);
            }
            $userRemoteAccountLinks = null;
            $clientId = (int) \WHMCS\Session::get("uid");
            $contactId = (int) \WHMCS\Session::get("cid");
            $requestContactId = $request->get("cid");
            if ($requestContactId) {
                if ($contactId && $requestContactId != $contactId) {
                    if (!function_exists("checkContactPermission")) {
                        include_once ROOTDIR . "/includes/clientfunctions.php";
                    }
                    $hasPermissionToView = checkContactPermission("contacts", true);
                } else {
                    $hasPermissionToView = true;
                }
                if ($hasPermissionToView) {
                    $contact = \WHMCS\User\Client\Contact::find($requestContactId);
                    if ($contact && $contact->client->id === $clientId) {
                        $userRemoteAccountLinks = $contact->remoteAccountLinks;
                    }
                }
            } else {
                if ($contactId) {
                    $contact = \WHMCS\User\Client\Contact::find($contactId);
                    if ($contact && $contact->client->id === $clientId) {
                        $userRemoteAccountLinks = $contact->remoteAccountLinks;
                    }
                } else {
                    if ($clientId) {
                        $client = \WHMCS\User\Client::find($clientId);
                        if ($client) {
                            $userRemoteAccountLinks = $client->remoteAccountLinks;
                        }
                    }
                }
            }
            if (!$userRemoteAccountLinks) {
                return new \WHMCS\Http\Message\JsonResponse($responseData);
            }
            $linkedAccounts = (new ViewHelper())->getTableData($userRemoteAccountLinks);
            $responseData["data"] = $linkedAccounts;
            return new \WHMCS\Http\Message\JsonResponse($responseData);
        } catch (\Exception $e) {
            return new \WHMCS\Http\Message\JsonResponse(array("data" => $e->getMessage()), 400);
        }
    }
    public function delete(\WHMCS\Http\Message\ServerRequest $request)
    {
        $authnId = $request->getAttribute("authnid");
        try {
            $query = \WHMCS\Authentication\Remote\AccountLink::where("client_id", "=", (int) \WHMCS\Session::get("uid"))->where("id", "=", $authnId);
            $accountLink = $query->firstOrFail();
            $contactId = (int) \WHMCS\Session::get("cid");
            if ($contactId && $contactId != $accountLink->contactId) {
                $contactOwner = \WHMCS\User\Client\Contact::firstOrNew(array("id" => $contactId))->client;
                if (!$contactOwner || $contactOwner->id != $accountLink->clientId) {
                    return new \WHMCS\Http\Message\JsonResponse(array("status" => "error", "message" => "Improper link reference for contact " . $contactId), 403);
                }
                if (!function_exists("checkContactPermission")) {
                    include_once ROOTDIR . "/includes/clientfunctions.php";
                }
                if (!$accountLink->contactId || !checkContactPermission("contacts", true)) {
                    return new \WHMCS\Http\Message\JsonResponse(array("status" => "error", "message" => "You do not have permission to modify this link"), 403);
                }
            }
        } catch (\Exception $e) {
            return new \WHMCS\Http\Message\JsonResponse(array("status" => "error", "message" => "failed to load Remote Authentication User ID: " . $authnId), 400);
        }
        $accountLink->delete();
        \DI::make("remoteAuth")->logAccountLinkDeletion($accountLink);
        return new \WHMCS\Http\Message\JsonResponse(array("status" => "success", "message" => "Remote Auth User removed."), 200);
    }
}

?>