<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Authentication\Remote\Providers\Twitter;

final class TwitterOauth extends \WHMCS\Authentication\Remote\Providers\AbstractRemoteAuthProvider
{
    protected $description = "Allow customers to register and sign in using their Twitter accounts.";
    protected $configurationDescription = "Twitter requires that you create an application and retrieve the consumer key and secret.";
    private $twitterClient = NULL;
    const NAME = "twitter_oauth";
    const FRIENDLY_NAME = "Twitter";
    private function getTwitterClient(array $oauthTokens = array(), $forceCreate = false)
    {
        if (!$this->twitterClient || $forceCreate) {
            if (empty($this->config["ConsumerKey"]) || empty($this->config["ConsumerSecret"])) {
                throw new \WHMCS\Exception\Authentication\Remote\RemoteAuthConfigException("Twitter Sign In configuration is incomplete.");
            }
            $oauthToken = !empty($oauthTokens["oauth_token"]) ? $oauthTokens["oauth_token"] : null;
            $oauthTokenSecret = !empty($oauthTokens["oauth_token_secret"]) ? $oauthTokens["oauth_token_secret"] : null;
            $this->twitterClient = new \Abraham\TwitterOAuth\TwitterOAuth($this->config["ConsumerKey"], $this->config["ConsumerSecret"], $oauthToken, $oauthTokenSecret);
        }
        return $this->twitterClient;
    }
    public function getConfigurationFields()
    {
        return array("Enabled", "ConsumerKey", "ConsumerSecret");
    }
    public function getEnabled()
    {
        return !empty($this->config["Enabled"]);
    }
    public function setEnabled($value)
    {
        $this->config["Enabled"] = (bool) $value;
    }
    public function parseMetadata($metadata)
    {
        $savedData = json_decode($metadata, true);
        $metadata = $savedData["metadata"];
        return new \WHMCS\Authentication\Remote\AuthUserMetadata($metadata["name"], $metadata["email"], $metadata["screen_name"], $this::FRIENDLY_NAME);
    }
    public function getHtmlScriptCode($htmlTarget)
    {
        $redirectUrl = $_SERVER["REQUEST_URI"];
        $context = $this->retrieveContext();
        if ($context) {
            $regFormData = $this->getRegistrationFormData($context);
            foreach ($regFormData as &$value) {
                $value = str_replace("\"", "\\\"", $value);
            }
            unset($value);
        } else {
            $regFormData = array();
        }
        $redirectUrl = urlencode($redirectUrl);
        $loginResult = \WHMCS\Session::getAndDelete("twitter_oauth_login_result");
        $routePath = routePath("auth-provider-twitter_oauth-authorize");
        $targetRegister = static::HTML_TARGET_REGISTER;
        $targetConnect = static::HTML_TARGET_CONNECT;
        $displayName = static::FRIENDLY_NAME;
        $targetLogin = static::HTML_TARGET_LOGIN;
        $html = "<script>\n    window.onerror = function(e){\n        WHMCS.authn.provider.displayError();\n    };\n\n    jQuery(document).ready(function() {\n        jQuery(\".btn-twitter\").click(function(e) {\n            e.preventDefault();\n\n            var failIfExists = 0;\n            if (\"" . $htmlTarget . "\" === \"" . $targetRegister . "\"\n               || \"" . $htmlTarget . "\" === \"" . $targetConnect . "\"\n            ) {\n                failIfExists = 1;\n            }\n            \n            WHMCS.authn.provider.preLinkInit(function () {\n                jQuery.ajax({\n                    url: \"" . $routePath . "\",\n                    method: \"POST\",\n                    dataType: \"json\",\n                    data: {\n                        redirect_url: \"" . $redirectUrl . "\",\n                        fail_if_exists: failIfExists\n                    }\n                }).done(function(data) {\n                    window.location = data.url;\n                }).error(function(data) {\n                    WHMCS.authn.provider.displayError();\n                });\n            });\n        });\n\n        if (\"" . $loginResult . "\") {\n            WHMCS.authn.provider.preLinkInit(function () {\n                var data = {\n                    \"result\": \"" . $loginResult . "\",\n                    \"remote_account\": {\n                        \"email\": \"" . $regFormData["email"] . "\",\n                        \"firstname\": \"" . $regFormData["firstname"] . "\",\n                        \"lastname\": \"" . $regFormData["lastname"] . "\"\n                    }\n                };\n\n                var context = {\n                    htmlTarget: \"" . $htmlTarget . "\",\n                    targetLogin: \"" . $targetLogin . "\",\n                    targetRegister: \"" . $targetRegister . "\"\n                };\n\n                var provider = {\n                    \"name\": \"" . $displayName . "\",\n                    \"icon\":  \"<i class=\\\"fab fa-twitter\\\"></i> \"\n                };\n\n                setTimeout(function() {\n                    WHMCS.authn.provider.displaySuccess(data, context, provider);\n                }, 1000);\n\n            });\n        }\n    });\n</script>";
        return $html;
    }
    public function getHtmlButton($htmlTarget)
    {
        if ($htmlTarget === self::HTML_TARGET_LOGIN) {
            $caption = \Lang::trans("remoteAuthn.signInWith", array(":provider" => "Twitter"));
        } else {
            if ($htmlTarget === self::HTML_TARGET_CONNECT) {
                $caption = \Lang::trans("remoteAuthn.connectWith", array(":provider" => "Twitter"));
            } else {
                $caption = \Lang::trans("remoteAuthn.signUpWith", array(":provider" => "Twitter"));
            }
        }
        return "<button class=\"btn btn-social btn-twitter\" type=\"button\">\n            <i class=\"fab fa-twitter\"></i>\n            " . $caption . "\n        </button>";
    }
    private function checkIsEnabled()
    {
        if (!$this->getEnabled()) {
            throw new \WHMCS\Exception\Authentication\Remote\RemoteAuthConfigException("Remote authentication not available via \"" . self::FRIENDLY_NAME . "\"");
        }
    }
    public function authorizeSignin(\WHMCS\Http\Message\ServerRequest $request)
    {
        $this->checkIsEnabled();
        $twitterClient = $this->getTwitterClient();
        try {
            $requestToken = $twitterClient->oauth("oauth/request_token", array("oauth_callback" => fqdnRoutePath("auth-provider-twitter_oauth-callback")));
            if ($twitterClient->getLastHttpCode() !== 200) {
                throw new \WHMCS\Exception\Authentication\Remote\RemoteAuthProcessException("Failed to obtain Twitter OAuth request token");
            }
            $this->saveContext(array("preAuth" => array("oauth_token" => $requestToken["oauth_token"], "oauth_token_secret" => $requestToken["oauth_token_secret"]), "redirectUrl" => $request->get("redirect_url"), "failIfExists" => $request->get("fail_if_exists")));
            $url = $twitterClient->url("oauth/authenticate", array("oauth_token" => $requestToken["oauth_token"]));
            return new \WHMCS\Http\Message\JsonResponse(array("url" => $url));
        } catch (\Exception $e) {
            $message = "";
            if ($e instanceof \Abraham\TwitterOAuth\TwitterOAuthException) {
                $messageData = json_decode($e->getMessage(), true);
                if (!is_null($messageData) && is_array($messageData["errors"])) {
                    foreach ($messageData["errors"] as $error) {
                        if ((int) $error["code"] == 32) {
                            $message = " Please verify your Twitter Consumer Key / Consumer Secret settings.";
                        }
                    }
                }
            }
            $message .= " Error: " . $e->getMessage();
            logActivity("Failed to perform Twitter Sign In authorization." . $message);
            return new \WHMCS\Http\Message\JsonResponse("Failed to pre-authorize", \Symfony\Component\HttpFoundation\Response::HTTP_PRECONDITION_FAILED);
        }
    }
    public function signinCallback(\WHMCS\Http\Message\ServerRequest $request)
    {
        try {
            $context = $this->retrieveContext();
            if (!is_array($context) || !array_key_exists("preAuth", $context)) {
                throw new \WHMCS\Exception\Authentication\Remote\RemoteAuthProcessException("Invalid authorization context");
            }
            if (!empty($context["redirectUrl"])) {
                $redirectUrl = urldecode($context["redirectUrl"]);
            } else {
                $redirectUrl = \WHMCS\Utility\Environment\WebHelper::getBaseUrl() . "/clientarea.php";
            }
            if ($request->has("denied")) {
                \WHMCS\Session::set("twitter_oauth_login_result", static::LOGIN_RESULT_USER_NOT_AUTHORIZED);
                return new \Zend\Diactoros\Response\RedirectResponse($redirectUrl);
            }
            if (!$request->has("oauth_token") || $request->get("oauth_token") !== $context["preAuth"]["oauth_token"]) {
                throw new \WHMCS\Exception\Authentication\Remote\RemoteAuthProcessException("Invalid authorization token in callback request: " . var_export($request->get("oauth_token"), true));
            }
            $twitterClient = $this->getTwitterClient($context["preAuth"]);
            $accessToken = $twitterClient->oauth("oauth/access_token", array("oauth_verifier" => $request->get("oauth_verifier")));
            if ($twitterClient->getLastHttpCode() !== 200) {
                throw new \WHMCS\Exception\Authentication\Remote\RemoteAuthProcessException("Failed to obtain Twitter OAuth access token");
            }
            $twitterClient->setOauthToken($accessToken["oauth_token"], $accessToken["oauth_token_secret"]);
            $metadata = (array) $twitterClient->get("account/verify_credentials", array("include_email" => "true"));
            if ($twitterClient->getLastHttpCode() !== 200) {
                throw new \WHMCS\Exception\Authentication\Remote\RemoteAuthProcessException("Failed to obtain Twitter OAuth client credentials");
            }
            $loginResult = $this->processRemoteUserId($metadata["id_str"], array("accessToken" => $accessToken, "metadata" => $metadata), $context["failIfExists"]);
            if (!in_array($loginResult, array(static::LOGIN_RESULT_LOGGED_IN, static::LOGIN_RESULT_2FA_NEEDED))) {
                \WHMCS\Session::set("twitter_oauth_login_result", $loginResult);
            }
        } catch (\Exception $e) {
            logActivity("Twitter Sign In callback failed: " . $e->getMessage());
            \WHMCS\Session::set("twitter_oauth_login_result", static::LOGIN_RESULT_GENERAL_ERROR);
        }
        if (empty($redirectUrl)) {
            $redirectUrl = \WHMCS\Utility\Environment\WebHelper::getBaseUrl() . "/clientarea.php";
        }
        return new \Zend\Diactoros\Response\RedirectResponse($redirectUrl);
    }
    public function linkAccount($context)
    {
        if (!is_array($context) || !array_key_exists("metadata", $context)) {
            return false;
        }
        $remoteUserId = $context["metadata"]["id_str"];
        if (!$remoteUserId) {
            return false;
        }
        return $this->linkLoggedInUser($remoteUserId, $context);
    }
    public function getRegistrationFormData($context)
    {
        $formData = array();
        if (!is_array($context) || !array_key_exists("metadata", $context)) {
            return $formData;
        }
        $metadata = $context["metadata"];
        if (!empty($metadata["email"])) {
            $formData["email"] = $metadata["email"];
        }
        if (!empty($metadata["name"])) {
            $nameParts = explode(" ", $metadata["name"], 2);
            $formData["firstname"] = $nameParts[0];
            if (count($nameParts) === 2) {
                $formData["lastname"] = $nameParts[1];
            }
        }
        return $formData;
    }
    public function verifyConfiguration()
    {
        if (!$this->config["ConsumerKey"] || !$this->config["ConsumerSecret"]) {
            throw new \RuntimeException("Settings cannot be empty");
        }
        try {
            $twitterClient = $this->getTwitterClient(array(), true);
            $twitterClient->oauth("oauth/request_token", array("oauth_callback" => fqdnRoutePath("auth-provider-twitter_oauth-callback")));
        } catch (\Exception $e) {
            throw new \WHMCS\Exception\Authentication\Remote\RemoteAuthConfigException("Verification for current settings failed validation");
        }
        if (!$twitterClient || $twitterClient->getLastHttpCode() !== 200) {
            throw new \WHMCS\Exception\Authentication\Remote\RemoteAuthConfigException("Verification for current settings failed validation");
        }
    }
    public function getRemoteAccountName($context)
    {
        if (!is_array($context) || !array_key_exists("metadata", $context)) {
            return "";
        }
        $metadata = $context["metadata"];
        return !empty($metadata["email"]) ? $metadata["email"] : $metadata["name"];
    }
}

?>