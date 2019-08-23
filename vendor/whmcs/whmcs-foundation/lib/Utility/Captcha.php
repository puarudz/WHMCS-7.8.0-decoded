<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Utility;

class Captcha
{
    public static $defaultFormSettings = NULL;
    private $enabled = false;
    private $forms = array();
    public $recaptcha = NULL;
    private $captchaType = "";
    const SETTING_CAPTCHA_FORMS = "CaptchaForms";
    const FORM_CHECKOUT_COMPLETION = "checkoutCompletion";
    const FORM_DOMAIN_CHECKER = "domainChecker";
    const FORM_REGISTRATION = "registration";
    const FORM_CONTACT_US = "contactUs";
    const FORM_SUBMIT_TICKET = "submitTicket";
    const FORM_LOGIN = "login";
    public function __construct()
    {
        $isEnabled = $this->isSystemEnabledRuntime();
        $this->captchaType = \WHMCS\Config\Setting::getValue("CaptchaType");
        $this->setEnabled($isEnabled);
        $storedForms = $this->getStoredFormSettings();
        $defaultForms = static::getDefaultFormSettings();
        $this->setForms(array_merge($defaultForms, $storedForms));
        $this->recaptcha = new Recaptcha($this);
        if (in_array($this->captchaType, array(Recaptcha::CAPTCHA_INVISIBLE, Recaptcha::CAPTCHA_RECAPTCHA)) && !$this->recaptcha->isEnabled()) {
            $this->captchaType = "";
        }
    }
    public function isSystemEnabledRuntime()
    {
        $setting = trim((string) \WHMCS\Config\Setting::getValue("CaptchaSetting"));
        if ($setting == "on") {
            return true;
        }
        $clientAreaLoggedIn = defined("CLIENTAREA") && (\WHMCS\Session::get("uid") || \WHMCS\Session::get("cid"));
        $adminAreaLoggedIn = defined("ADMINAREA") && \WHMCS\Session::get("adminid");
        $isLoggedIn = $clientAreaLoggedIn || $adminAreaLoggedIn;
        if (!$setting || $setting && $isLoggedIn) {
            return false;
        }
        return true;
    }
    public static function getDefaultFormSettings()
    {
        return static::$defaultFormSettings;
    }
    public function validateAppropriateCaptcha($form, \WHMCS\Validate $validate)
    {
        if ($this->isEnabledForForm($form)) {
            if ($this->isEnabled() && $this->recaptcha->isEnabled()) {
                try {
                    $this->recaptcha->validate((string) \App::getFromRequest("g-recaptcha-response"));
                } catch (\Exception $e) {
                    $validate->addError($e->getMessage());
                }
            } else {
                if ($this->isEnabled() && !$this->recaptcha->isEnabled()) {
                    $languageKey = "captchaverifyincorrect";
                    if (defined("ADMINAREA")) {
                        $languageKey = "The characters you entered didn't match the image shown." . " Please try again.";
                    }
                    $validate->validate("captcha", "code", $languageKey);
                }
            }
        }
    }
    public function getForms()
    {
        return $this->forms;
    }
    public function setForms($forms)
    {
        $this->forms = $forms;
        return $this;
    }
    public function isEnabledForForm($formName)
    {
        if ($this->isEnabled()) {
            $forms = $this->getForms();
            if (!array_key_exists($formName, $forms)) {
                return true;
            }
            return (bool) $forms[$formName];
        }
        return false;
    }
    public function getStoredFormSettings()
    {
        $data = \WHMCS\Config\Setting::getValue(static::SETTING_CAPTCHA_FORMS);
        $data = json_decode($data, true);
        if (!is_array($data)) {
            $data = array();
        }
        return $data;
    }
    public function setStoredFormSettings(array $data = array())
    {
        \WHMCS\Config\Setting::setValue(static::SETTING_CAPTCHA_FORMS, json_encode($data));
        return $this;
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
    public function __toString()
    {
        return $this->captchaType;
    }
    public function getCaptchaType()
    {
        return $this->captchaType;
    }
    public function getButtonClass($formName)
    {
        $class = "";
        if ($this->isEnabledForForm($formName)) {
            if ($this->recaptcha->isEnabled()) {
                $class = " btn-recaptcha";
            }
            if ($this->recaptcha->isInvisible()) {
                $class .= " btn-recaptcha-invisible";
            }
        }
        return $class;
    }
}

?>