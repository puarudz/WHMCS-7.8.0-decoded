<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if (!defined("WHMCS")) {
    exit("This file cannot be accessed directly");
}
$credentialId = (int) $whmcs->getFromRequest("credentialId");
$clientIdentifier = $whmcs->getFromRequest("clientIdentifier");
$name = $whmcs->isInRequest("name") ? trim($whmcs->getFromRequest("name")) : NULL;
$description = $whmcs->isInRequest("description") ? $whmcs->getFromRequest("description") : NULL;
$logoUri = $whmcs->isInRequest("logoUri") ? $whmcs->getFromRequest("logoUri") : NULL;
$redirectUri = $whmcs->isInRequest("redirectUri") ? $whmcs->getFromRequest("redirectUri") : NULL;
$scope = $whmcs->isInRequest("scope") ? $whmcs->getFromRequest("scope") : NULL;
$grantType = $whmcs->isInRequest("grantType") ? $whmcs->getFromRequest("grantType") : NULL;
$serviceId = $whmcs->isInRequest("serviceId") ? (int) $whmcs->getFromRequest("serviceId") : NULL;
$resetSecret = (bool) $whmcs->getFromRequest("resetSecret");
if (!$credentialId && !$clientIdentifier) {
    $apiresults = array("result" => "error", "message" => "A Credential ID or Client Identifier is required.");
} else {
    if (0 < $credentialId) {
        $client = WHMCS\ApplicationLink\Client::find($credentialId);
    } else {
        $client = WHMCS\ApplicationLink\Client::whereIdentifier($clientIdentifier)->first();
    }
    if (is_null($client)) {
        $apiresults = array("result" => "error", "message" => 0 < $credentialId ? "Invalid Credential ID provided." : "Invalid Client Identifier provided.");
    } else {
        $credentialId = $client->id;
        $clientApiIdentifier = $client->identifier;
        $validGrantTypes = array("authorization_code", "single_sign_on");
        if (!is_null($grantType) && !in_array($grantType, $validGrantTypes)) {
            $apiresults = array("result" => "error", "message" => "The requested grant type \"" . $grantType . "\" is invalid.");
        } else {
            if (is_null($grantType)) {
                $grantType = $client->grantTypes[0];
            }
            if ($grantType == "authorization_code" && !is_null($name) && strlen(trim($name)) == 0) {
                $apiresults = array("result" => "error", "message" => "A name for the Client Credential is required.");
            } else {
                if ($grantType == "single_sign_on" && !is_null($serviceId) && !$serviceId) {
                    $apiresults = array("result" => "error", "message" => "A service ID is required for the single sign-on grant type.");
                } else {
                    if (!is_null($scope)) {
                        $validScopes = WHMCS\ApplicationLink\Scope::pluck("scope")->all();
                        $scopes = explode(" ", $scope);
                        foreach ($scopes as $scopeToValidate) {
                            if (!in_array($scopeToValidate, $validScopes)) {
                                $apiresults = array("result" => "error", "message" => "The requested scope \"" . $scopeToValidate . "\" is invalid.");
                                return NULL;
                            }
                        }
                    }
                    if (is_null($name)) {
                        $name = $client->name;
                    }
                    if (is_null($description)) {
                        $description = $client->description;
                    }
                    if (is_null($logoUri)) {
                        $logoUri = $client->logoUri;
                    }
                    if (is_null($redirectUri)) {
                        $redirectUri = $client->redirectUri;
                    }
                    if (is_null($scope)) {
                        $scope = $client->scope;
                    }
                    if (is_null($grantType)) {
                        $grantType = $client->grantType;
                    }
                    $userUuid = "";
                    if (is_null($serviceId)) {
                        $serviceId = $client->serviceId;
                        $userUuid = $client->uuid;
                    } else {
                        $userUuid = get_query_val("tblclients", "tblclients.uuid", array("tblhosting.id" => $serviceId), "", "", "", "tblhosting ON tblhosting.userid = tblclients.id");
                    }
                    if ($resetSecret) {
                        $secret = WHMCS\ApplicationLink\Client::generateSecret();
                    } else {
                        $secret = $client->decryptedSecret;
                    }
                    $rsaId = $client->rsaKeyPairId;
                    $server = DI::make("oauth2_server");
                    $storage = $server->getStorage("client_credentials");
                    $storage->setClientDetails($clientApiIdentifier, $secret, $redirectUri, $grantType, $scope, $userUuid, $serviceId, $rsaId, $name, $description, $logoUri);
                    $apiresults = array("result" => "success", "credentialId" => $client->id);
                    if ($resetSecret) {
                        $apiresults["newClientSecret"] = $secret;
                    }
                }
            }
        }
    }
}

?>