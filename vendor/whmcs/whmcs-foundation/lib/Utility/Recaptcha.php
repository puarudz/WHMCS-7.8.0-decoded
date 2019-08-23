<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Utility;

class Recaptcha
{
    private $enabled = false;
    private $siteKey = "";
    private $secret = "";
    private $isInvisible = false;
    const CAPTCHA_INVISIBLE = "invisible";
    const CAPTCHA_RECAPTCHA = "recaptcha";
    public function __construct(Captcha $captcha)
    {
        $isEnabled = false;
        $isRecaptchaEnabled = in_array(\WHMCS\Config\Setting::getValue("CaptchaType"), array(self::CAPTCHA_RECAPTCHA, self::CAPTCHA_INVISIBLE));
        if ($isRecaptchaEnabled && $captcha->isEnabled()) {
            $siteKey = (string) \WHMCS\Config\Setting::getValue("ReCAPTCHAPublicKey");
            $secret = (string) \WHMCS\Config\Setting::getValue("ReCAPTCHAPrivateKey");
            if ($siteKey && $secret) {
                $isInvisible = $captcha->getCaptchaType() === self::CAPTCHA_INVISIBLE;
                $this->setSiteKey($siteKey)->setSecret($secret)->setIsInvisible($isInvisible);
            }
            if ($siteKey && $secret) {
                $isEnabled = true;
            }
        }
        $this->setEnabled($isEnabled);
    }
    public function validate($recaptchaToken = "")
    {
        if (empty($recaptchaToken)) {
            throw new \RuntimeException("Please complete the captcha to continue");
        }
        $tokenDigest = sha1($recaptchaToken);
        $validatedTokens = \WHMCS\Session::get("validatedRecaptchaTokens") ?: array();
        if (in_array($tokenDigest, $validatedTokens)) {
            return true;
        }
        $result = $this->verify($recaptchaToken);
        if (!$result["success"]) {
            if (isset($result["error-codes"]) && is_array($result["error-codes"])) {
                $error = implode(",", $result["error-codes"]);
            } else {
                $error = "Unknown error";
            }
            throw new \RuntimeException("Recaptcha verification failed: " . $error);
        }
        $validatedTokens[] = $tokenDigest;
        \WHMCS\Session::set("validatedRecaptchaTokens", $validatedTokens);
        return true;
    }
    protected function verify($recaptchaToken)
    {
        $url = "https://www.google.com/recaptcha/api/siteverify";
        $data = array("secret" => $this->getSecret(), "remoteip" => Environment\CurrentUser::getIP(), "response" => $recaptchaToken);
        $options = array("CURLOPT_SSL_VERIFYHOST" => 2, "CURLOPT_SSL_VERIFYPEER" => 1);
        $response = curlCall($url, $data, $options);
        $result = json_decode($response, true);
        if (!$result || !is_array($result) || !isset($result["success"])) {
            throw new \RuntimeException("Unexpected recaptcha verification result: " . $response);
        }
        return $result;
    }
    public function isEnabled()
    {
        return $this->enabled;
    }
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
        return $this;
    }
    public function getSiteKey()
    {
        return $this->siteKey;
    }
    public function setSiteKey($siteKey)
    {
        $this->siteKey = $siteKey;
        return $this;
    }
    public function getSecret()
    {
        return $this->secret;
    }
    public function setSecret($secret)
    {
        $this->secret = $secret;
        return $this;
    }
    public function isInvisible()
    {
        return (bool) $this->isInvisible;
    }
    public function setIsInvisible($isInvisible)
    {
        $this->isInvisible = $isInvisible;
        return $this;
    }
}

?>