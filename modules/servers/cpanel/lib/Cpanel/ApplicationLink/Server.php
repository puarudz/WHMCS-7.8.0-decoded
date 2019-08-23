<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Cpanel\ApplicationLink;

class Server extends \WHMCS\ApplicationLink\Server\Server
{
    public function handleTokenRequest(\OAuth2\RequestInterface $request, \OAuth2\ResponseInterface $response = NULL)
    {
        list($request, $response) = $this->preProcessHandleTokenRequest($request, $response);
        if (!$response->isOk()) {
            return $response;
        }
        parent::handleTokenRequest($request, $response);
        $response = $this->postProcessHandleTokenRequest($response);
        return $response;
    }
    protected function preProcessHandleTokenRequest(\OAuth2\HttpFoundationBridge\Request $request, \OAuth2\HttpFoundationBridge\Response $response = NULL)
    {
        $this->addGrantType(new \WHMCS\ApplicationLink\GrantType\SingleSignOn($this->getStorage("client_credentials")), "client_credentials");
        $scope = $request->query->get("scope", "");
        $clientId = $request->request->get("subscriber_unique_id", "");
        $secret = $request->request->get("token", "");
        $request->request->add(array("grant_type" => "single_sign_on", "client_id" => $clientId, "client_secret" => $secret, "scope" => $scope));
        return array($request, $response);
    }
    protected function postProcessHandleTokenRequest(\OAuth2\ResponseInterface $response)
    {
        $data = json_decode($response->getContent(), true);
        if (!$data["access_token"]) {
            return $response;
        }
        $site = \App::getSystemURL();
        $endpoint = "singlesignon";
        $token = \WHMCS\ApplicationLink\AccessToken::where("access_token", "=", $data["access_token"])->first();
        if (!$this->getScopeUtil()->checkScope("clientarea:sso", $token->scope)) {
            $endpoint = "resource";
        }
        $data["redirect_url"] = sprintf("%soauth/%s.php?module_type=server&module=cpanel&access_token=%s", $site, $endpoint, $data["access_token"]);
        $response->setData($data);
        $response->setStatusCode(\OAuth2\HttpFoundationBridge\Response::HTTP_OK);
        return $response;
    }
    public function postAccessTokenResponseAction(\OAuth2\RequestInterface $request, \OAuth2\ResponseInterface $response)
    {
        $data = json_decode($response->getContent(), true);
        if (!empty($data["attempt"])) {
            $attempt = (int) $data["attempt"];
            if ($attempt < 4) {
            }
        }
    }
}

?>