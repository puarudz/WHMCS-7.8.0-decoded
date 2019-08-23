<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\ClientArea;

class PasswordResetController
{
    private function initView()
    {
        $view = new \WHMCS\ClientArea();
        $view->setPageTitle(\Lang::trans("pwreset"));
        $view->addOutputHookFunction("ClientAreaPagePasswordReset");
        $view->assign("showingLoginPage", true);
        $view->setTemplate("password-reset-container");
        return $view;
    }
    private function setInnerTemplate($template, \WHMCS\ClientArea $view)
    {
        $template = preg_replace("/[^a-z\\d\\-]+/", "", $template);
        $view->assign("innerTemplate", $template);
    }
    private function getUserFromKey($key = NULL)
    {
        if (!$key) {
            $key = $this->getStoredKey();
        }
        if (!$key) {
            return null;
        }
        $client = \WHMCS\User\Client::where("pwresetkey", $key)->first();
        $contact = null;
        if ($client) {
            return $client;
        }
        $contact = \WHMCS\User\Client\Contact::where("pwresetkey", $key)->first();
        return $contact;
    }
    private function validateUser($user)
    {
        if (!$user) {
            throw new \WHMCS\Exception\Authentication\PasswordResetFailure(\Lang::trans("pwresetkeyinvalid"));
        }
        if (!$user->passwordResetKeyExpiryDate || $user->passwordResetKeyExpiryDate->isPast()) {
            throw new \WHMCS\Exception\Authentication\PasswordResetFailure(\Lang::trans("pwresetkeyexpired"));
        }
    }
    private function storeKey($key)
    {
        \WHMCS\Session::set("pw_reset_key", $key);
    }
    private function getStoredKey()
    {
        return \WHMCS\Session::get("pw_reset_key");
    }
    private function deleteKey()
    {
        \WHMCS\Session::delete("pw_reset_key");
    }
    private function validateUserSecurityAnswer($user, $answer)
    {
        $this->validateUser($user);
        if ($user->securityQuestion) {
            if (!$answer || !hash_equals($user->securityQuestionAnswer, $answer)) {
                throw new \WHMCS\Exception\Authentication\PasswordResetFailure(\Lang::trans("pwresetsecurityquestionincorrect"));
            }
        } else {
            if ($answer) {
                throw new \WHMCS\Exception\Authentication\PasswordResetFailure(\Lang::trans("pwresetsecurityquestionincorrect"));
            }
        }
    }
    public function emailPrompt(\WHMCS\Http\Message\ServerRequest $request)
    {
        $view = $this->initView();
        $captcha = new \WHMCS\Utility\Captcha();
        $templateData["captcha"] = $captcha;
        $templateData["captchaForm"] = \WHMCS\Utility\Captcha::FORM_LOGIN;
        $view->setTemplateVariables($templateData);
        $attributes = $request->getAttributes();
        if (isset($attributes["extraVars"])) {
            $view->setTemplateVariables($attributes["extraVars"]);
        }
        $this->setInnerTemplate("email-prompt", $view);
        return $view;
    }
    public function validateEmail(\WHMCS\Http\Message\ServerRequest $request)
    {
        check_token();
        try {
            $captcha = new \WHMCS\Utility\Captcha();
            if ($captcha->isEnabled() && !\WHMCS\Session::get("CaptchaComplete")) {
                $validate = new \WHMCS\Validate();
                $captcha->validateAppropriateCaptcha(\WHMCS\Utility\Captcha::FORM_LOGIN, $validate);
                if ($validate->hasErrors()) {
                    throw new \WHMCS\Exception\Authentication\PasswordResetFailure(implode("\n", array($validate->getHTMLErrorOutput())));
                }
                \WHMCS\Session::set("CaptchaComplete", true);
            }
            $email = trim($request->get("email"));
            if (empty($email)) {
                throw new \WHMCS\Exception\Authentication\PasswordResetFailure(\Lang::trans("pwresetemailrequired"));
            }
            (new \WHMCS\Authentication\PasswordReset())->sendPasswordResetEmail($email);
            $view = $this->initView();
            $view->setTemplateVariables(array("successTitle" => \Lang::trans("pwresetrequested"), "successMessage" => \Lang::trans("pwresetcheckemail")));
            return $view;
        } catch (\WHMCS\Exception\Authentication\PasswordResetFailure $e) {
            return $this->emailPrompt($request->withAttribute("extraVars", array("errorMessage" => $e->getMessage())));
        }
    }
    public function useKey(\WHMCS\Http\Message\ServerRequest $request)
    {
        $key = trim($request->get("key"));
        $routeName = "password-reset-email-prompt";
        if (!empty($key)) {
            try {
                $user = $this->getUserFromKey($key);
                $this->validateUser($user);
                $this->storeKey($key);
                if ($user->securityQuestion) {
                    $routeName = "password-reset-security-prompt";
                } else {
                    $routeName = "password-reset-change-prompt";
                }
            } catch (\WHMCS\Exception\Authentication\PasswordResetFailure $e) {
                $this->deleteKey();
            }
        }
        return new \Zend\Diactoros\Response\RedirectResponse(routePath($routeName));
    }
    public function securityPrompt(\WHMCS\Http\Message\ServerRequest $request)
    {
        $user = $this->getUserFromKey();
        $view = $this->initView();
        try {
            $this->validateUser($user);
        } catch (\WHMCS\Exception\Authentication\PasswordResetFailure $e) {
            return $view->assign("errorMessage", $e->getMessage());
        }
        if (!$user->securityQuestion) {
            return new \Zend\Diactoros\Response\RedirectResponse("password-reset-change-prompt");
        }
        $view->assign("securityQuestion", $user->securityQuestion->question);
        $this->setInnerTemplate("security-prompt", $view);
        return $view;
    }
    public function securityValidate(\WHMCS\Http\Message\ServerRequest $request)
    {
        check_token();
        $view = $this->initView();
        $user = $this->getUserFromKey();
        $this->setInnerTemplate("security-prompt", $view);
        if ($user && $user->securityQuestion) {
            $view->assign("securityQuestion", $user->securityQuestion->question);
        }
        $answer = $request->get("answer");
        try {
            $this->validateUserSecurityAnswer($user, $answer);
        } catch (\WHMCS\Exception\Authentication\PasswordResetFailure $e) {
            return $view->assign("errorMessage", $e->getMessage());
        }
        $view->assign("securityAnswer", $answer);
        $this->setInnerTemplate("change-prompt", $view);
        return $view;
    }
    public function changePrompt(\WHMCS\Http\Message\ServerRequest $request)
    {
        $view = $this->initView();
        $user = $this->getUserFromKey();
        try {
            $this->validateUserSecurityAnswer($user, "");
        } catch (\WHMCS\Exception\Authentication\PasswordResetFailure $e) {
            return $view->assign("errorMessage", $e->getMessage());
        }
        $this->setInnerTemplate("change-prompt", $view);
        return $view;
    }
    public function changePerform(\WHMCS\Http\Message\ServerRequest $request)
    {
        check_token();
        $view = $this->initView();
        $user = $this->getUserFromKey();
        try {
            $this->validateUserSecurityAnswer($user, $request->get("answer"));
        } catch (\WHMCS\Exception\Authentication\PasswordResetFailure $e) {
            return $view->assign("errorMessage", $e->getMessage());
        }
        $validate = new \WHMCS\Validate();
        if ($validate->validate("required", "newpw", "ordererrorpassword") && $validate->validate("pwstrength", "newpw", "pwstrengthfail") && $validate->validate("required", "confirmpw", "clientareaerrorpasswordconfirm")) {
            $validate->validate("match_value", "newpw", "clientareaerrorpasswordnotmatch", "confirmpw");
        }
        $newPassword = $request->get("newpw", "");
        if ($newPassword !== "" && !$validate->hasErrors()) {
            (new \WHMCS\Authentication\PasswordReset())->changeUserPassword($user, $newPassword);
            $this->deleteKey();
            \WHMCS\Session::delete("CaptchaComplete");
            if (!function_exists("validateClientLogin")) {
                require_once ROOTDIR . "/includes/clientfunctions.php";
            }
            validateClientLogin($user->email, $newPassword);
            $view->setTemplateVariables(array("successTitle" => \Lang::trans("pwresetvalidationsuccess"), "successMessage" => sprintf(\Lang::trans("pwresetsuccessdesc"), "<a href=\"clientarea.php\">", "</a>")));
            return $view;
        }
        $this->setInnerTemplate("change-prompt", $view);
        $view->assign("securityAnswer", $request->get("answer"));
        $view->assign("errorMessage", $validate->getHTMLErrorOutput());
        return $view;
    }
}

?>