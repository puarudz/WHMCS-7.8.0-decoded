<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Authentication\Remote\Providers\Google;

final class GoogleSignin extends \WHMCS\Authentication\Remote\Providers\AbstractRemoteAuthProvider
{
    protected $description = "Allow customers to register and sign in using their Google accounts.";
    protected $configurationDescription = "Google requires you to create an application and retrieve a client ID and secret.";
    const NAME = "google_signin";
    const FRIENDLY_NAME = "Google";
    public function getConfigurationFields()
    {
        return array("Enabled", "ClientId", "ClientSecret");
    }
    public function getEnabled()
    {
        return !empty($this->config["Enabled"]);
    }
    public function setEnabled($value)
    {
        $this->config["Enabled"] = (bool) $value;
    }
    private function getClientId()
    {
        $this->checkIsEnabled();
        return $this->config["ClientId"];
    }
    public function parseMetadata($metadata)
    {
        $metadata = json_decode($metadata, true);
        return new \WHMCS\Authentication\Remote\AuthUserMetadata($metadata["name"], $metadata["email"], $metadata["email"], $this::FRIENDLY_NAME);
    }
    public function getHtmlScriptCode($htmlTarget)
    {
        if (in_array($htmlTarget, array(static::HTML_TARGET_LOGIN, static::HTML_TARGET_REGISTER))) {
            $redirectUrl = \WHMCS\Session::get("loginurlredirect") ?: \WHMCS\Utility\Environment\WebHelper::getBaseUrl() . "/clientarea.php";
        } else {
            if ($htmlTarget === static::HTML_TARGET_CONNECT) {
                $redirectUrl = \WHMCS\Utility\Environment\WebHelper::getBaseUrl() . "/clientarea.php?action=security";
            } else {
                if ($htmlTarget === static::HTML_TARGET_CHECKOUT) {
                    $redirectUrl = \WHMCS\Utility\Environment\WebHelper::getBaseUrl() . "/cart.php?a=checkout";
                } else {
                    throw new \WHMCS\Exception\Authentication\Remote\RemoteAuthConfigException("Invalid auth provider HTML target: " . $htmlTarget);
                }
            }
        }
        $redirectUrl = urlencode($redirectUrl);
        $appId = $this->getClientId();
        $originUrl = \App::getSystemURL(false);
        $routePath = routePath("auth-provider-google_signin-finalize");
        $targetRegister = static::HTML_TARGET_REGISTER;
        $targetConnect = static::HTML_TARGET_CONNECT;
        $displayName = static::FRIENDLY_NAME;
        $targetLogin = static::HTML_TARGET_LOGIN;
        $csrfToken = generate_token("plain");
        $html = "<script>\n    window.onerror = function(e){\n        WHMCS.authn.provider.displayError();\n    };\n\n    var googleUser = {};\n    var startGoogleApp = function() {\n        gapi.load('auth2', function() {\n            gapi.auth2.init({\n                client_id: '" . $appId . "',\n                cookiepolicy: '" . $originUrl . "'\n            }).then(function(response) {\n                jQuery('.btn-google').each(function (i, el) {\n                    response.attachClickHandler(el, {},\n                        function (googleUser) {\n                            onSignIn(googleUser);\n                        }\n                    );\n                });\n            }, function(reason) {\n                if (reason.error == 'idpiframe_initialization_failed') {\n                    jQuery('.btn-google').click(function(e) {\n                        WHMCS.authn.provider.displayError('Google Sign-In', 'init_failed', reason.details);\n                    });\n                } else {\n                    jQuery('.btn-google').click(function(e) {\n                        WHMCS.authn.provider.displayError('Google Sign-In');\n                    });\n                }\n            });\n        });\n    };\n\n    function onSignIn(googleUser) {\n        WHMCS.authn.provider.preLinkInit();\n\n        var failIfExists = 0;\n        if (\"" . $htmlTarget . "\" === \"" . $targetRegister . "\"\n           || \"" . $htmlTarget . "\" === \"" . $targetConnect . "\"\n        ) {\n            failIfExists = 1;\n        }\n        \n        var context = {\n            htmlTarget: \"" . $htmlTarget . "\",\n            targetLogin: \"" . $targetLogin . "\",\n            targetRegister: \"" . $targetRegister . "\",\n            redirectUrl: \"" . $redirectUrl . "\"\n        };\n        var config = {\n            url: \"" . $routePath . "\",\n            method: \"POST\",\n            dataType: \"json\",\n            data: {\n                id_token: googleUser.getAuthResponse().id_token,\n                fail_if_exists: failIfExists,\n                token: \"" . $csrfToken . "\"\n            }\n        };\n        var provider = {\n            \"name\": \"" . $displayName . "\",\n            \"icon\":  \"<i class=\\\"fab fa-google\\\"></i> \"\n        };\n\n        var providerDone = function () { gapi.auth2.getAuthInstance().signOut(); };\n        var providerError = function () { gapi.auth2.getAuthInstance().signOut(); };\n\n        WHMCS.authn.provider.signIn(config, context, provider, providerDone, providerError);\n    }\n\n</script>\n<script src=\"https://apis.google.com/js/platform.js?onload=startGoogleApp\" async defer></script>";
        return $html;
    }
    public function getHtmlButton($htmlTarget)
    {
        static $i = 0;
        $i++;
        if ($htmlTarget === self::HTML_TARGET_LOGIN) {
            $caption = \Lang::trans("remoteAuthn.signInWith", array(":provider" => "Google"));
        } else {
            if ($htmlTarget === self::HTML_TARGET_CONNECT) {
                $caption = \Lang::trans("remoteAuthn.connectWith", array(":provider" => "Google"));
            } else {
                $caption = \Lang::trans("remoteAuthn.signUpWith", array(":provider" => "Google"));
            }
        }
        return "\n            <button id=\"btnGoogleSignin" . $i . "\" class=\"btn btn-social btn-google\" type=\"button\">\n                <i class=\"fab fa-google\"></i>\n                " . $caption . "\n            </button>";
    }
    private function checkIsEnabled()
    {
        if (!$this->getEnabled()) {
            throw new \WHMCS\Exception\Authentication\Remote\RemoteAuthConfigException("Remote authentication not available via \"" . self::FRIENDLY_NAME . "\"");
        }
    }
    public function linkAccount($context)
    {
        $payload = $context;
        if (!is_array($payload)) {
            return false;
        }
        $expires = \WHMCS\Carbon::createFromTimestampUTC($payload["exp"]);
        if (!$expires->isFuture()) {
            return false;
        }
        $remoteUserId = $payload["sub"];
        if (empty($remoteUserId)) {
            return false;
        }
        return $this->linkLoggedInUser($remoteUserId, $context);
    }
    public function finalizeSignin(\WHMCS\Http\Message\ServerRequest $request)
    {
        check_token();
        try {
            $this->checkIsEnabled();
            if ($request->has("id_token")) {
                $jwt = new \Firebase\JWT\JWT();
                $jwt::$leeway = 3;
                $client = new \Google_Client(array("client_id" => $this->getClientId(), "jwt" => $jwt));
                $token = $request->get("id_token");
                $payload = $client->verifyIdToken($token);
                if (empty($payload) || empty($payload["sub"])) {
                    return new \WHMCS\Http\Message\JsonResponse("Invalid token", \Symfony\Component\HttpFoundation\Response::HTTP_FORBIDDEN);
                }
                $loginResult = $this->processRemoteUserId($payload["sub"], $payload, $request->get("fail_if_exists"));
                return new \WHMCS\Http\Message\JsonResponse(array("result" => $loginResult, "remote_account" => $this->getRegistrationFormData($payload)));
            }
            return new \WHMCS\Http\Message\JsonResponse("Invalid token", \Symfony\Component\HttpFoundation\Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            $possibleCause = "";
            if ($e instanceof \Firebase\JWT\BeforeValidException || $e instanceof \Firebase\JWT\ExpiredException) {
                $possibleCause = " Please make sure that the system clock is set properly (current system time is " . date("c") . ").";
            }
            logActivity(sprintf("Remote account linking via %s has failed.%s Error: %s", static::FRIENDLY_NAME, $possibleCause, $e->getMessage()));
            return new \WHMCS\Http\Message\JsonResponse("Could not finalize sign-in", \Symfony\Component\HttpFoundation\Response::HTTP_BAD_REQUEST);
        }
    }
    public function getRegistrationFormData($context)
    {
        $fieldMap = array("email" => "email", "given_name" => "firstname", "family_name" => "lastname");
        $formData = array();
        foreach ($fieldMap as $contextField => $regFormField) {
            if (isset($context[$contextField]) && $context[$contextField] !== ".") {
                $formData[$regFormField] = $context[$contextField];
            }
        }
        return $formData;
    }
    public function verifyConfiguration()
    {
        if (!$this->config["ClientId"] || !$this->config["ClientSecret"]) {
            throw new \WHMCS\Exception\Authentication\Remote\RemoteAuthConfigException("Settings cannot be empty");
        }
        $guzzle = new \GuzzleHttp\Client();
        $parts = parse_url(\App::getSystemURL(false));
        $origin = $parts["scheme"] . "://" . $parts["host"];
        $params = array("action" => "checkOrigin", "origin" => $origin, "client_id" => $this->getClientId());
        $url = "https://accounts.google.com/o/oauth2/iframerpc?" . http_build_query($params);
        try {
            $result = $guzzle->get($url, array("headers" => array("X-Requested-With" => "XmlHttpRequest")));
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            throw new \RuntimeException("Connection to provider failed: " . $e->getMessage());
        }
        if ($result->getStatusCode() != 200) {
            throw new \WHMCS\Exception\Authentication\Remote\RemoteAuthConfigException("Settings resulted in invalid response code");
        }
        $response = json_decode($result->getBody(), true);
        if (!is_array($response) || empty($response["valid"])) {
            $msg = "Verification for current settings failed validation";
            if (is_array($response)) {
                $msg .= "Response: " . json_encode($response);
            }
            throw new \WHMCS\Exception\Authentication\Remote\RemoteAuthConfigException($msg);
        }
    }
    public function getRemoteAccountName($context)
    {
        return !empty($context["email"]) ? $context["email"] : $context["given_name"] . " " . $context["family_name"];
    }
}

?>