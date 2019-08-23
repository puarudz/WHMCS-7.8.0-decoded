<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\ApplicationLink\Server;

class Server extends \OAuth2\Server implements ApplicationLinkServerInterface
{
    public function getAccessToken(\OAuth2\HttpFoundationBridge\Request $request)
    {
        $accessToken = $request->request->get("access_token", "");
        if (!$accessToken) {
            $accessToken = $request->query->get("access_token", "");
        }
        $token = \WHMCS\ApplicationLink\AccessToken::where("access_token", "=", $accessToken)->first();
        return $token;
    }
    public function postAccessTokenResponseAction(\OAuth2\RequestInterface $request, \OAuth2\ResponseInterface $response)
    {
    }
    public function getModuleApplicationLinkServer(\OAuth2\HttpFoundationBridge\Request $request)
    {
        $module = null;
        $moduleName = $request->request->get("module", "");
        if (!$moduleName) {
            $moduleName = $request->query->get("module", "");
        }
        if ($moduleName) {
            $moduleType = $request->request->get("module_type", "");
            if (!$moduleType) {
                $moduleType = $request->query->get("module_type", "");
            }
            if (!$moduleType) {
                return null;
            }
            switch ($moduleType) {
                case "server":
                    $module = new \WHMCS\Module\Server();
                    break;
                default:
                    return null;
            }
            if ($module->load($moduleName)) {
                $class = ucfirst(strtolower($moduleName)) . "\\ApplicationLink\\Server";
                if (class_exists($class)) {
                    try {
                        $moduleAppLinkServer = new $class($this->getStorages(), $this->config, $this->getGrantTypes(), $this->getResponseTypes() ? $this->getResponseTypes() : array(), $this->getTokenType(), $this->getScopeUtil(), $this->getClientAssertionType());
                        $interface = "\\WHMCS\\ApplicationLink\\Server\\ApplicationLinkServerInterface";
                        if (!$moduleAppLinkServer instanceof $interface) {
                            throw new \WHMCS\Exception\Information(sprintf("%s must implement %s", $class, $interface));
                        }
                        return $moduleAppLinkServer;
                    } catch (\Exception $e) {
                        logActivity($e->getMessage());
                        return null;
                    }
                }
            }
        }
        return null;
    }
    public function handleSingleSignOnRequest(\OAuth2\RequestInterface $request, \OAuth2\ResponseInterface $response, $requestedScope = "")
    {
        $request->setMethod(\OAuth2\HttpFoundationBridge\Request::METHOD_POST);
        if (strpos($requestedScope, "clientarea:sso") === false) {
            $requestedScope .= " clientarea:sso";
        }
        if ($this->verifyResourceRequest($request, $response, $requestedScope)) {
            $token = $this->getAccessToken($request);
            $storage = $this->getStorage("client_credentials");
            $sso = new \WHMCS\ApplicationLink\GrantType\SingleSignOn($storage);
            if (!$storage->checkRestrictedGrantType($token->clientId, $sso->getQuerystringIdentifier())) {
                $response->setError(400, "invalid_token", sprintf("token's client must have %s grant.", $sso->getQuerystringIdentifier()));
                return $response;
            }
            $user = \WHMCS\User\Client::findUuid($token->userId);
            if (!$user->isAllowedToAuthenticate()) {
                $msg = "Single Sign-On authentication denied for \"Closed\" User ID: %s";
                logActivity(sprintf($msg, $user->id));
            } else {
                if (!$user->hasSingleSignOnPermission()) {
                    $msg = "Single Sign-On authentication denied per configuration for User ID: %s";
                    logActivity(sprintf($msg, $user->id));
                } else {
                    $authenticator = new \WHMCS\Auth();
                    if (!$authenticator->authenticateClientFromToken($token)) {
                        $msg = "Unable to authenticate with Single Sign-On token for User ID: %s";
                        logActivity(sprintf($msg, $user->id));
                    }
                }
            }
            $response = new SingleSignOn\RedirectResponse();
            $response->prepare($request);
            $response->setTargetUrlFromToken($token);
            $token->delete();
        }
        return $response;
    }
    public function getDiscoveryDocument()
    {
        $issuer = static::getIssuer();
        $doc = array("issuer" => $issuer, "authorization_endpoint" => $issuer . "/oauth/authorize.php", "token_endpoint" => $issuer . "/oauth/token.php", "userinfo_endpoint" => $issuer . "/oauth/userinfo.php", "jwks_uri" => $issuer . "/oauth/certs.php", "response_types_supported" => $this->getResponseTypes(), "subject_types_supported" => array("public"), "id_token_signing_alg_values_supported" => array("RS256"), "scopes_supported" => array("openid", "email", "profile"), "claims_supported" => array("iss", "aud", "exp", "sub"));
        return json_encode($doc);
    }
    public function getJwks()
    {
        $clientsWithCerts = \WHMCS\ApplicationLink\Client::where("user_id", "=", "")->where("service_id", "<", 1)->where("rsa_key_pair_id", ">", 0)->with("rsaKeyPair")->get();
        $jwks = array("keys" => array());
        foreach ($clientsWithCerts as $client) {
            $keypair = $client->rsaKeyPair;
            if (!$keypair) {
                continue;
            }
            $rsa = $keypair->publicRsa;
            $jwks["keys"][] = array("kty" => "RSA", "alg" => "RS256", "kid" => $keypair->identifier, "n" => $this->base64urlEncode($rsa->modulus->toBytes()), "e" => $this->base64urlEncode($rsa->exponent->toBytes()));
        }
        return $jwks;
    }
    public function base64urlEncode($data)
    {
        $data = base64_encode($data);
        $data = strtr($data, "+/", "-_");
        $data = rtrim($data, "=");
        return $data;
    }
    public function base64urlDecode($data)
    {
        $data = strtr($data, "-_", "+/");
        $data = str_pad($data, strlen($data) % 4, "=", STR_PAD_RIGHT);
        $data = base64_decode($data);
        return $data;
    }
    public static function getIssuer()
    {
        $issuer = \App::getSystemSSLURLOrFail();
        if (substr($issuer, -1) == "/") {
            $issuer = substr($issuer, 0, -1);
        }
        return $issuer;
    }
    protected function createDefaultIdTokenResponseType()
    {
        if (!isset($this->storages["user_claims"])) {
            throw new \LogicException("You must supply a storage object implementing OAuth2\\OpenID\\Storage\\UserClaimsInterface to use openid connect");
        }
        if (!isset($this->storages["public_key"])) {
            throw new \LogicException("You must supply a storage object implementing OAuth2\\Storage\\PublicKeyInterface to use openid connect");
        }
        $config = array_intersect_key($this->config, array_flip(explode(" ", "issuer id_lifetime")));
        return new \WHMCS\ApplicationLink\OpenID\ResponseType\IdToken($this->storages["user_claims"], $this->storages["public_key"], $config, new \OAuth2\Encryption\FirebaseJwt());
    }
    public function hasUserAuthorizedRequestedScopes(\WHMCS\User\Client $user)
    {
        $controller = $this->getAuthorizeController();
        $clientId = $controller->getClientId();
        if ($clientId) {
            $authorizedScopes = $this->getStorage("authorization_code")->getUserAuthorizedScopes($clientId, $user);
            if ($authorizedScopes->count()) {
                $requestedScopes = $this->getAuthorizeController()->getScope();
                if ($requestedScopes) {
                    $requestedScopes = explode(" ", $requestedScopes);
                    $requestedScopeCollection = \WHMCS\ApplicationLink\Scope::whereIn("scope", $requestedScopes)->get(array("id"));
                } else {
                    $requestedScopeCollection = new \Illuminate\Database\Eloquent\Collection();
                }
                foreach ($requestedScopeCollection as $scope) {
                    if (!$authorizedScopes->contains($scope->id)) {
                        return false;
                    }
                }
                return true;
            }
        }
        return false;
    }
    public function updateUserAuthorizedScopes(\WHMCS\User\Client $user, array $scopes = array())
    {
        $clientId = $this->getAuthorizeController()->getClientId();
        if ($scopes) {
            $authorizedScopeCollection = \WHMCS\ApplicationLink\Scope::whereIn("scope", $scopes)->get(array("id"));
        } else {
            $authorizedScopeCollection = new \Illuminate\Database\Eloquent\Collection();
        }
        $this->getStorage("authorization_code")->setUserAuthorizedScopes($clientId, $user, $authorizedScopeCollection);
        return $this;
    }
    public static function storeRequest(\OAuth2\HttpFoundationBridge\Request $request)
    {
        $validRequestKeys = array("client_id", "nonce", "redirect_uri", "response_type", "scope", "state");
        $requestData = array("post" => array(), "get" => array(), "headers" => array(), "method" => $request->getMethod());
        foreach ($validRequestKeys as $key) {
            $value = $request->request->get($key);
            if (!is_null($value)) {
                $requestData["post"][$key] = $value;
            } else {
                $value = $request->query->get($key);
                if (!is_null($value)) {
                    $requestData["get"][$key] = $value;
                }
            }
        }
        $requestData["headers"] = $request->headers->all();
        $requestHash = base64_encode(\phpseclib\Crypt\Random::string(8));
        $transientData = new \WHMCS\TransientData();
        $transientData->purgeExpired();
        $transientData->store($requestHash, json_encode($requestData), 60);
        return $requestHash;
    }
    public static function retrieveRequest($requestHash, $deleteAfterFetch = true)
    {
        $request = new \OAuth2\HttpFoundationBridge\Request();
        $transientData = new \WHMCS\TransientData();
        $requestData = $transientData->retrieve($requestHash);
        if ($requestData) {
            $requestData = json_decode($requestData, true);
            if (!empty($requestData["post"])) {
                $request->request->add($requestData["post"]);
            }
            if (!empty($requestData["get"])) {
                $request->query->add($requestData["get"]);
            }
            if (!empty($requestData["headers"])) {
                $request->headers->add($requestData["headers"]);
            }
            $request->setMethod($requestData["method"]);
            if ($deleteAfterFetch) {
                $transientData->delete($requestHash);
            }
        }
        return $request;
    }
}

?>