<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\ApplicationLink\Storage;

class Whmcs implements \OAuth2\Storage\AccessTokenInterface, \OAuth2\Storage\AuthorizationCodeInterface, \OAuth2\Storage\ClientCredentialsInterface, \OAuth2\OpenID\Storage\AuthorizationCodeInterface, \OAuth2\Storage\PublicKeyInterface, \OAuth2\Storage\ScopeInterface, \OAuth2\OpenID\Storage\UserClaimsInterface
{
    public function checkClientCredentials($client_id, $client_secret = NULL)
    {
        $isValid = false;
        $rawSecret = null;
        $client = \WHMCS\ApplicationLink\Client::where("identifier", "=", $client_id)->first();
        if ($client) {
            $hasher = new \WHMCS\Security\Hash\Password();
            $rawSecret = $client->decryptedSecret;
            $isValid = $hasher->assertBinarySameness($client_secret, $rawSecret);
        }
        return $isValid;
    }
    public function isPublicClient($client_id)
    {
        $client = \WHMCS\ApplicationLink\Client::where("identifier", "=", $client_id)->first();
        if (!$client) {
            return false;
        }
        return empty($client->secret);
    }
    public function getClientDetails($client_id)
    {
        $client = \WHMCS\ApplicationLink\Client::where("identifier", "=", $client_id)->first();
        if ($client) {
            return $client->toArray();
        }
        return false;
    }
    public function setClientDetails($client_id, $client_secret, $redirect_uri = "", $grant_types = "", $scope = "", $user_id = "", $service_id = 0, $keypair_id = 0, $name = "", $description = "", $logoUri = "")
    {
        if (!$this->scopeExists($scope)) {
            throw new \WHMCS\Exception\OAuth2\NonExistentScope("Invalid scope in '" . $scope . "'");
        }
        $client = \WHMCS\ApplicationLink\Client::where("identifier", "=", $client_id)->first();
        if (!$client) {
            $client = new \WHMCS\ApplicationLink\Client();
            $client->identifier = $client_id;
        }
        $client->secret = $client_secret;
        $client->userId = $user_id;
        $client->serviceId = $service_id;
        $client->rsaKeyPairId = $keypair_id;
        $client->name = $name;
        $client->description = $description;
        $client->logoUri = $logoUri;
        if (!is_array($redirect_uri)) {
            $redirect_uri = explode(" ", $redirect_uri);
        }
        if ($client->exists) {
            $existRedirectUri = $client->redirectUri;
            $sameRedirectUri = array_intersect($existRedirectUri, $redirect_uri);
            $newRedirectUri = array_diff($redirect_uri, $sameRedirectUri);
            $redirect_uri = $sameRedirectUri + $newRedirectUri;
        }
        $client->redirectUri = $redirect_uri;
        if (empty($grant_types)) {
            $grant_types = array();
        } else {
            if (is_string($grant_types)) {
                $grant_types = trim($grant_types);
                $grant_types = explode(" ", $grant_types);
            }
        }
        if ($client->exists) {
            $existGrants = $client->grantTypes;
            $sameGrants = array_intersect($existGrants, $grant_types);
            $newGrants = array_diff($grant_types, $sameGrants);
            $grant_types = $sameGrants + $newGrants;
        }
        $client->grantTypes = $grant_types;
        $status = $client->save();
        $existScopeIds = array();
        if ($client->exists) {
            $existScopes = $client->scopes();
            foreach ($existScopes as $existingScope) {
                $existScopeIds[] = $existingScope->id;
            }
            $client->scopes()->detach();
        }
        $scopeCollection = \WHMCS\ApplicationLink\Scope::all(array("id", "scope"));
        $requestedScopes = explode(" ", $scope);
        $requestedScopesIds = array();
        foreach ($scopeCollection as $canonicalScope) {
            if (in_array($canonicalScope->scope, $requestedScopes)) {
                $requestedScopesIds[] = $canonicalScope->id;
            }
        }
        $sameScopes = array_intersect($existScopeIds, $requestedScopesIds);
        $newScopes = array_diff($requestedScopesIds, $sameScopes);
        $assignScopes = $sameScopes + $newScopes;
        if (count($assignScopes)) {
            $status = $client->scopes()->attach($assignScopes);
        }
        return (bool) $status;
    }
    public function checkRestrictedGrantType($client_id, $grant_type)
    {
        $client = \WHMCS\ApplicationLink\Client::where("identifier", "=", $client_id)->first();
        if ($client) {
            $grants = $client->grantTypes;
            if (empty($grants)) {
                return true;
            }
            return in_array($grant_type, $grants);
        }
        return false;
    }
    public function getAccessToken($access_token)
    {
        $accessToken = \WHMCS\ApplicationLink\AccessToken::where("access_token", "=", $access_token)->first();
        if ($accessToken) {
            return $accessToken->toArray();
        }
        return null;
    }
    public function setAccessToken($access_token, $client_id, $user_id, $expires, $scope = NULL)
    {
        $accessToken = new \WHMCS\ApplicationLink\AccessToken();
        $accessToken->accessToken = $access_token;
        $accessToken->clientId = $client_id;
        $accessToken->userId = $user_id;
        $accessToken->expires = \WHMCS\Carbon::createFromTimestamp($expires);
        $accessToken->save();
        $accessToken = \WHMCS\ApplicationLink\AccessToken::find($accessToken->id);
        $scopeIds = array();
        if ($scope instanceof \WHMCS\ApplicationLink\Scope) {
            $scopeIds[] = $scope->id;
        } else {
            if (is_numeric($scope)) {
                $scopeIds[] = $scope;
            } else {
                $scopeNames = explode(" ", $scope);
                foreach ($scopeNames as $name) {
                    $scopeIds[] = \WHMCS\ApplicationLink\Scope::where("scope", "=", $name)->first()->id;
                }
            }
        }
        $accessToken->scopes()->attach($scopeIds);
        return true;
    }
    public function scopeExists($scope)
    {
        $scopes = explode(" ", $scope);
        $scopes = array_filter($scopes);
        $storedScopes = \WHMCS\ApplicationLink\Scope::whereIn("scope", $scopes)->count();
        return count($scopes) == $storedScopes;
    }
    public function getDefaultScope($client_id = NULL)
    {
        return "profile";
    }
    public function getClientScope($client_id)
    {
        $client = \WHMCS\ApplicationLink\Client::where("identifier", "=", $client_id)->first();
        if (!$client) {
            return false;
        }
        $scopes = $client->scope;
        if (!$scopes) {
            return null;
        }
        return $scopes;
    }
    public function getAuthorizationCode($code)
    {
        if (!empty($code)) {
            $authCode = \WHMCS\ApplicationLink\AuthorizationCode::where("authorization_code", "=", $code)->first();
            if ($authCode) {
                return $authCode->toArray();
            }
        }
        return $code;
    }
    public function setAuthorizationCode($code, $client_id, $user_id, $redirect_uri, $expires, $scope = NULL, $id_token = NULL)
    {
        $exists = true;
        $authCode = \WHMCS\ApplicationLink\AuthorizationCode::where("authorization_code", "=", $code)->first();
        if (!$authCode) {
            $exists = false;
            $authCode = new \WHMCS\ApplicationLink\AuthorizationCode();
            $authCode->authorizationCode = $code;
        }
        $authCode->clientId = $client_id;
        $authCode->userId = $user_id;
        $authCode->redirectUri = $redirect_uri;
        $authCode->expires = \WHMCS\Carbon::createFromTimestamp($expires);
        if ($id_token) {
            $authCode->idToken = $id_token;
        }
        $status = $authCode->save();
        $existScopeIds = array();
        if ($exists) {
            $existScopes = $authCode->scopes();
            foreach ($existScopes as $existingScope) {
                $existScopeIds[] = $existingScope->id;
            }
            $authCode->scopes()->detach();
        }
        $scopeCollection = \WHMCS\ApplicationLink\Scope::all(array("id", "scope"));
        $requestedScopes = explode(" ", $scope);
        $requestedScopesIds = array();
        foreach ($scopeCollection as $canonicalScope) {
            if (in_array($canonicalScope->scope, $requestedScopes)) {
                $requestedScopesIds[] = $canonicalScope->id;
            }
        }
        $sameScopes = array_intersect($existScopeIds, $requestedScopesIds);
        $newScopes = array_diff($requestedScopesIds, $sameScopes);
        $assignScopes = $sameScopes + $newScopes;
        if (count($assignScopes)) {
            $status = $authCode->scopes()->attach($assignScopes);
        }
        return $status;
    }
    public function expireAuthorizationCode($code)
    {
        if ($code) {
            $authCodes = \WHMCS\ApplicationLink\AuthorizationCode::where("authorization_code", "=", $code)->get();
            foreach ($authCodes as $authCode) {
                $authCode->delete();
            }
        }
    }
    public function setUserAuthorizedScopes($clientId, $user, \Illuminate\Database\Eloquent\Collection $scopes)
    {
        $userAuthorization = \WHMCS\ApplicationLink\Scope\UserAuthorization::whereUserId($user->uuid)->whereClientId($clientId)->first();
        if (!$userAuthorization) {
            $userAuthorization = new \WHMCS\ApplicationLink\Scope\UserAuthorization();
            $userAuthorization->clientId = $clientId;
            $userAuthorization->userId = $user->uuid;
            $userAuthorization->save();
        } else {
            $userAuthorization->scopes()->detach();
        }
        $toAttach = array();
        foreach ($scopes as $scope) {
            $toAttach[] = $scope->id;
        }
        if ($toAttach) {
            $userAuthorization->scopes()->attach($toAttach);
        }
        return $this;
    }
    public function getUserAuthorizedScopes($clientId, $user)
    {
        $userAuthorization = \WHMCS\ApplicationLink\Scope\UserAuthorization::whereUserId($user->uuid)->whereClientId($clientId)->first();
        if ($userAuthorization) {
            return $userAuthorization->scopes;
        }
        return new \Illuminate\Database\Eloquent\Collection();
    }
    public function getPublicKey($client_id = NULL)
    {
        $client = \WHMCS\ApplicationLink\Client::where("identifier", "=", $client_id)->with("rsaKeyPair")->first();
        if (!$client) {
            return null;
        }
        $keypair = $client->rsaKeyPair;
        if (!$keypair) {
            return null;
        }
        return $keypair->publicKey;
    }
    public function getPrivateKey($client_id = NULL)
    {
        $client = \WHMCS\ApplicationLink\Client::where("identifier", "=", $client_id)->with("rsaKeyPair")->first();
        if (!$client) {
            return null;
        }
        $keypair = $client->rsaKeyPair;
        if (!$keypair) {
            return null;
        }
        return $keypair->decryptedPrivateKey;
    }
    public function getKeyDetails($client_id = NULL)
    {
        $client = \WHMCS\ApplicationLink\Client::where("identifier", "=", $client_id)->with("rsaKeyPair")->first();
        if (!$client) {
            return "foo";
        }
        $keypair = $client->rsaKeyPair;
        if (!$keypair) {
            return null;
        }
        $details = $keypair->toArray();
        $details["privateKey"] = $keypair->decryptedPrivateKey;
        return $details;
    }
    public function getEncryptionAlgorithm($client_id = NULL)
    {
        $client = \WHMCS\ApplicationLink\Client::where("identifier", "=", $client_id)->with("rsaKeyPair")->first();
        if ($client) {
            $keypair = $client->rsaKeyPair;
            if ($keypair && $keypair->alogrithm) {
                return $keypair->alogrithm;
            }
        }
        return "RS256";
    }
    public function getUserClaims($user_id, $scope)
    {
        $user = \WHMCS\User\Client::findUuid($user_id);
        $requestedClaims = explode(" ", trim($scope));
        $claims = new \WHMCS\ApplicationLink\OpenID\Claim\ClaimFactory($user, $requestedClaims);
        return $claims->toArray();
    }
}

?>