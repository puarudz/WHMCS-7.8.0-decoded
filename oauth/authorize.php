<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

$requestSuperGlobal = $_REQUEST;
$post = $_POST;
$get = $_GET;
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . "init.php";
$_REQUEST = $requestSuperGlobal;
$_POST = $post;
$_GET = $get;
$tmpRequest = OAuth2\HttpFoundationBridge\Request::createFromGlobals();
$isLoginDeclinedRequest = $tmpRequest->request->get("login_declined");
$isLogoutRequest = $tmpRequest->request->get("logout");
$isLoginRedirectPostLogout = $tmpRequest->query->get("request_hash");
$userHasAuthorized = $tmpRequest->request->get("userAuthorization") == "yes";
$userAuthzRequestPostLogoutLogin = NULL;
$isReturnToAppRequest = $tmpRequest->request->get("return_to_app");
$passedRequestHash = $tmpRequest->request->get("request_hash", $tmpRequest->query->get("request_hash"));
if ($passedRequestHash) {
    $origRequest = WHMCS\ApplicationLink\Server\Server::retrieveRequest($passedRequestHash, false);
    $tmpRequest->query->add($origRequest->query->all());
    $tmpRequest->request->add($origRequest->request->all());
    $tmpRequest->headers->add($origRequest->headers->all());
    $tmpRequest->setMethod($origRequest->getMethod());
}
$request = $tmpRequest;
if ($isLogoutRequest) {
    WHMCS\Session::delete("uid");
    WHMCS\Session::delete("cid");
    WHMCS\Session::delete("upw");
    WHMCS\Cookie::delete("User");
    $response = new Symfony\Component\HttpFoundation\RedirectResponse(sprintf("%s/oauth/authorize.php?request_hash=%s", WHMCS\ApplicationLink\Server\Server::getIssuer(), $passedRequestHash));
    $response->send();
    exit;
}
$requestHash = NULL;
if ($isLoginRedirectPostLogout) {
    $requestHash = $passedRequestHash;
} else {
    if ($isReturnToAppRequest) {
        $requestHash = $passedRequestHash;
        $isLoginDeclinedRequest = true;
    }
}
$response = new OAuth2\HttpFoundationBridge\Response();
$response->prepare($request);
$server = DI::make("oauth2_server");
$issuer = WHMCS\ApplicationLink\Server\Server::getIssuer();
$server->setConfig("issuer", $issuer);
$previouslyAuthorized = false;
$user = NULL;
if (!$server->validateAuthorizeRequest($request, $response)) {
    $response->send();
    exit;
}
gracefulCoreRequiredFileInclude("/includes/clientareafunctions.php");
$ca = new WHMCS\ClientArea();
$ca->setPageTitle("OAuth Authorization");
$ca->initPage();
$client = WHMCS\ApplicationLink\Client::whereIdentifier($request->query->get("client_id", $request->request->get("client_id", $request->query->get("client_id"))))->first();
$ca->assign("appName", $client->name);
$ca->assign("appLogo", $client->logoUri);
$ca->assign("issuerurl", $issuer . "/");
if (!$isLoginDeclinedRequest) {
    if (!$requestHash) {
        $requestHash = $server::storeRequest($request);
    }
    $ca->assign("request_hash", $requestHash);
    if (!$ca->isLoggedIn()) {
        $_SESSION["loginurlredirect"] = html_entity_decode($_SERVER["REQUEST_URI"]);
        $ca->assign("requestedAction", "Login");
        $ca->assign("incorrect", (bool) $whmcs->get_req_var("incorrect"));
        if (WHMCS\Session::get("2faverifyc")) {
            $twofa = new WHMCS\TwoFactorAuthentication();
            if ($twofa->setClientID(WHMCS\Session::get("2faclientid"))) {
                if (!$twofa->isActiveClients() || !$twofa->isEnabled()) {
                    WHMCS\Session::destroy();
                    redir();
                }
                $challenge = $twofa->moduleCall("challenge");
                if ($challenge) {
                    $ca->assign("challenge", $challenge);
                } else {
                    $ca->assign("error", Lang::trans("oauth.badTwoFactorAuthModule"));
                }
            } else {
                $ca->assign("error", Lang::trans("errorButTryAgain"));
            }
            $ca->assign("content", $ca->getSingleTplOutput("oauth/login-twofactorauth"));
        } else {
            $ca->assign("content", $ca->getSingleTplOutput("oauth/login"));
        }
        $ca->setTemplate("oauth/layout");
        $ca->disableHeaderFooterOutput();
        $ca->output();
    } else {
        $user = $ca->getClient();
        $previouslyAuthorized = $server->hasUserAuthorizedRequestedScopes($user);
        if (!$previouslyAuthorized) {
            if (!$request->request->get("userAuthorization")) {
                $ca->assign("requestedPermissions", array(Lang::trans("oauth.permAccessNameAndEmail")));
                $ca->assign("requestedAuthorizations", array("openid", "profile", "email"));
                $ca->assign("requestedAction", "Authorize App");
                $ca->assign("content", $ca->getSingleTplOutput("oauth/authorize"));
                $ca->setTemplate("oauth/layout");
                $ca->disableHeaderFooterOutput();
                $ca->output();
            } else {
                if ($userHasAuthorized) {
                    $officialScopes = array("openid", "profile", "email");
                    $authorizedScopes = array();
                    foreach ($request->request->get("authz") as $authz) {
                        if (in_array($authz, $officialScopes)) {
                            $authorizedScopes[] = $authz;
                        }
                    }
                    $server->updateUserAuthorizedScopes($user, $authorizedScopes);
                    $previouslyAuthorized = $server->hasUserAuthorizedRequestedScopes($user);
                } else {
                    $previouslyAuthorized = false;
                    $server->updateUserAuthorizedScopes($user, array());
                }
            }
        }
    }
}
$userUuid = 0;
if ($user) {
    $userUuid = $user->uuid;
}
if ($user instanceof WHMCS\User\Client && !$user->isAllowedToAuthenticate()) {
    $msg = "OAuth authorization request denied due to unexpected active " . "login session for \"Closed\" User ID: %s";
    logActivity(sprintf($msg, $user->id));
    $response->setError(OAuth2\HttpFoundationBridge\Response::HTTP_UNAUTHORIZED, "Invalid authentication", "Cannot process authorization request for associated \"Closed\" user account.");
} else {
    $server->handleAuthorizeRequest($request, $response, $previouslyAuthorized, $userUuid);
}
Log::debug("oauth/authorize", array("request" => array("headers" => $request->server->getHeaders(), "request" => $request->request->all(), "query" => $request->query->all()), "response" => array("body" => $response->getContent())));
$response->send();

?>