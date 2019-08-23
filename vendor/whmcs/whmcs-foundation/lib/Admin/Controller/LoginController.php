<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Controller;

class LoginController
{
    use \WHMCS\Application\Support\Controller\DelegationTrait;
    public function viewLoginForm(\WHMCS\Http\Message\ServerRequest $request)
    {
        if ($request->get("conntest")) {
            $response = $this->doConnectionTest();
        } else {
            ob_start();
            $response = $this->loginPhp($request);
            $content = ob_get_clean();
        }
        if ($response instanceof \Psr\Http\Message\ResponseInterface) {
            return $response;
        }
        return (new \WHMCS\Admin\ApplicationSupport\View\Html\ContentWrapper())->setBodyContent($content);
    }
    protected function doConnectionTest()
    {
        $domains = \WHMCS\License::LICENSE_API_HOSTS;
        $domainHtml = array();
        for ($i = 0; $i < 2; $i++) {
            $domain = array_shift($domains);
            $url = "https://" . $domain . "/1.0/test";
            $ip = gethostbyname($domain);
            $responseCode = $result = "";
            try {
                $ch = curlCall($url, array(), array(), true);
                $data = curl_exec($ch);
                $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if (curl_error($ch)) {
                    $result = "Curl Error: " . curl_error($ch);
                } else {
                    if (!$data) {
                        $result = "Empty Data Response - Please check CURL Installation";
                    } else {
                        $decoded = json_decode($data, true);
                        if (array_key_exists("status", $decoded) && $decoded["status"] == "ok") {
                            $result = "Connection Successful!";
                        } else {
                            $result = "Connection Failed!" . "<br /><br />Raw Output:<br />" . "<textarea rows=\"20\" cols=\"120\">" . $data . "</textarea>";
                        }
                    }
                }
                curl_close($ch);
            } catch (\Exception $e) {
                $result = $e->getMessage();
            }
            $domainHtml[] = "<div style=\"font-size:18px;\">\n    Testing Connection to '" . $url . "'...<br />\n    URL resolves to " . $ip . " ...<br />\n    Response Code: " . $responseCode . "<br />\n    " . $result . "<br />\n</div>\n<br/>";
        }
        return new \Zend\Diactoros\Response\HtmlResponse("<html><body>" . implode("", $domainHtml) . "</body></html>");
    }
    protected function loginPhp(\WHMCS\Http\Message\ServerRequest $request)
    {
        $adminPasswordResetDisabled = (bool) \WHMCS\Config\Setting::getValue("DisableAdminPWReset");
        $action = $request->get("action");
        $sub = $request->get("sub");
        $incorrect = $request->get("incorrect");
        $redirectUri = $request->get("redirect");
        $logout = $request->get("logout");
        $email = $request->get("email");
        $useBackupCode = $request->get("backupcode");
        $invalid = $request->get("invalid");
        $verificationToken = $request->get("verify");
        $result = select_query("tblconfiguration", "COUNT(*)", array("setting" => "License"));
        $data = mysql_fetch_array($result);
        if (!$data[0]) {
            insert_query("tblconfiguration", array("setting" => "License"));
        }
        $license = \DI::make("license");
        if (!$license->isUnlicensed()) {
            try {
                $licensing = \DI::make("license");
                $licensing->validate();
                if ($licensing->getStatus() != "Active") {
                    redir("status=" . $licensing->getStatus(), \WHMCS\Utility\Environment\WebHelper::getAdminBaseUrl() . "/licenseerror.php");
                }
            } catch (\WHMCS\Exception\Http\ConnectionError $e) {
                redir("status=noconnection", \WHMCS\Utility\Environment\WebHelper::getAdminBaseUrl() . "/licenseerror.php");
            } catch (\WHMCS\Exception $e) {
                \WHMCS\Session::setAndRelease("licenseCheckError", $e->getMessage());
                redir("", \WHMCS\Utility\Environment\WebHelper::getAdminBaseUrl() . "/licenseerror.php");
            }
            if (!$licensing->checkOwnedUpdates()) {
                redir("licenseerror=version", \WHMCS\Utility\Environment\WebHelper::getAdminBaseUrl() . "/licenseerror.php");
            }
        }
        if (\WHMCS\Session::get("adminid") && !\WHMCS\Session::get("2fabackupcodenew")) {
            return $this->redirectTo("admin-homepage", $request);
        }
        $whmcs = \App::self();
        $adminfolder = $whmcs->get_admin_folder_name();
        if (!$whmcs->in_ssl() && $whmcs->isSSLAvailable()) {
            $whmcs->redirectSystemURL($whmcs->get_admin_folder_name() . "/" . $whmcs->getCurrentFilename(false));
        }
        if ($action && $adminPasswordResetDisabled) {
            redir();
        }
        $templatevars = array("step" => "login", "displayTitle" => "Login", "infoMsg" => "", "successMsg" => "", "errorMsg" => "", "redirectUri" => $redirectUri);
        if (!$action) {
            if (\WHMCS\Session::get("2faverify")) {
                if (\WHMCS\Session::get("2fabackupcodenew")) {
                    $templatevars["infoMsg"] = "Backup Codes are valid once only. It will now be reset.";
                } else {
                    if ($incorrect) {
                        $templatevars["errorMsg"] = "<strong>Second factor invalid.</strong> Please try again.";
                    } else {
                        $templatevars["infoMsg"] = "Your second factor is required to complete the login.";
                    }
                }
            } else {
                if ($incorrect) {
                    $templatevars["errorMsg"] = "<strong>Login Failed.</strong> Please Try Again.";
                } else {
                    if ($invalid) {
                        $message = \WHMCS\Session::getAndDelete("LoginCaptcha");
                        if (!$message) {
                            $message = "Recaptcha verification failed";
                        }
                        $templatevars["errorMsg"] = $message;
                    } else {
                        if ($logout) {
                            $templatevars["displayTitle"] = "Logout";
                            $templatevars["successMsg"] = "You have been successfully logged out.";
                        }
                    }
                }
            }
            if (\WHMCS\Session::get("2fabackupcodenew")) {
                $templatevars["step"] = "twofabackupcode";
                $twofa = new \WHMCS\TwoFactorAuthentication();
                if ($twofa->setAdminID(\WHMCS\Session::get("2faadminid"))) {
                    $templatevars["successMsg"] = "Your New Backup Code is<br /><strong>" . $twofa->generateNewBackupCode() . "</strong>";
                } else {
                    $templatevars["errorMsg"] = "An error occurred. Please try again.";
                }
            } else {
                if (\WHMCS\Session::get("2faverify")) {
                    $twofa = new \WHMCS\TwoFactorAuthentication();
                    if ($twofa->setAdminID(\WHMCS\Session::get("2faadminid"))) {
                        if (!$twofa->isActiveAdmins() || !$twofa->isEnabled()) {
                            \WHMCS\Session::destroy();
                            redir();
                        }
                        if ($useBackupCode) {
                            $templatevars["step"] = "twofabackupcode";
                        } else {
                            $templatevars["step"] = "twofa";
                            $challenge = $twofa->moduleCall("challenge");
                            if ($challenge) {
                                $challenge = str_replace("</form>", "<input type=\"hidden\" name=\"redirect\" value=\"" . $redirectUri . "\"></form>", $challenge);
                                $templatevars["challengeHtml"] = $challenge;
                            } else {
                                $templatevars["errorMsg"] = "Bad 2 Factor Auth Module. Please contact support.";
                            }
                        }
                    } else {
                        $templateVars["errorMsg"] = "An error occurred. Please try again.";
                    }
                }
            }
        } else {
            if ($action == "reset") {
                $templatevars["step"] = "reset";
                $templatevars["displayTitle"] = "Reset Password";
                if ($verificationToken) {
                    $admin = \WHMCS\User\Admin::wherePasswordResetKey($verificationToken)->whereDisabled(0)->first();
                    if ($admin) {
                        if (\WHMCS\Carbon::now()->timestamp - \WHMCS\Carbon::createFromFormat("Y-m-d H:i:s", $admin->passwordResetExpiry)->timestamp <= 0) {
                            $hasher = new \WHMCS\Security\Hash\Password();
                            if ($hasher->verify($admin->passwordResetData . $admin->id . $admin->email, base64_decode($verificationToken))) {
                                if ($sub == "newPassword") {
                                    $auth = new \WHMCS\Auth();
                                    $auth->getInfobyID($admin->id);
                                    $newPassword = $request->get("password");
                                    try {
                                        $admin->passwordResetKey = "";
                                        $admin->passwordResetData = "";
                                        $admin->passwordResetExpiry = "0000-00-00 00:00:00";
                                        if ($auth->generateNewPasswordHashAndStore($newPassword) && $auth->generateNewPasswordHashAndStoreForApi(md5($newPassword))) {
                                            $admin->loginAttempts = 0;
                                            $templatevars["successMsg"] = "<strong>Success!</strong> Please login with your new password.";
                                            logActivity("Password Reset Completed for Admin Username " . $admin->username);
                                            $extraParams = array("firstname" => $admin->firstName, "username" => $admin->username);
                                            $mailer = \WHMCS\Mail\Entity\Admin::factoryByTemplate("Admin Password Reset Confirmation");
                                            $mailer->determineAdminRecipientsAndSender("", 0, $admin->id, false);
                                            foreach ($extraParams as $extraParam => $value) {
                                                $mailer->assign($extraParam, $value);
                                            }
                                            $mailer->send();
                                            $remoteIp = \WHMCS\Utility\Environment\CurrentUser::getIP();
                                            $date = fromMySQLDate(\WHMCS\Carbon::now()->toDateTimeString(), true);
                                            $hostname = gethostbyaddr($remoteIp);
                                            sendAdminNotification("system", "Admin Password Reset Completed", "                <p>This is a notification that an admin password reset has been performed by the following user.</p>\n<p>Username: " . $admin->username . "<br />Date/Time: " . $date . "<br />Hostname " . $hostname . "<br />IP Address: " . $remoteIp . "</p>");
                                        }
                                    } catch (\WHMCS\Exception\Mail\SendFailure $e) {
                                        $templatevars["errorMsg"] = "There was an error sending the confirmation email.";
                                    } catch (\WHMCS\Exception\Mail\SendHookAbort $e) {
                                    } catch (\Exception $e) {
                                        $templatevars["errorMsg"] = $e->getMessage();
                                    } finally {
                                        $admin->save();
                                    }
                                } else {
                                    $templatevars["step"] = "reset";
                                    $templatevars["verify"] = $verificationToken;
                                }
                            }
                        } else {
                            $admin->passwordResetExpiry = "0000-00-00 00:00:00";
                            $admin->passwordResetData = "";
                            $admin->passwordResetKey = "";
                            $admin->save();
                            logActivity("Expired Admin Password Reset Link Followed.");
                            $templatevars["errorMsg"] = "Expired Link Followed. Please try again.";
                        }
                    } else {
                        logActivity("Invalid Admin Password Reset Link Followed.");
                        $templatevars["errorMsg"] = "Invalid Link Followed. Please try again.";
                    }
                } else {
                    if ($sub == "send") {
                        $admin = \WHMCS\User\Admin::where("email", "=", $email)->orWhere("username", "=", $email)->first();
                        $captcha = new \WHMCS\Utility\Captcha();
                        $captchaFailed = null;
                        if ($captcha->isEnabled() && $captcha->isEnabledForForm(\WHMCS\Utility\Captcha::FORM_LOGIN)) {
                            try {
                                $validate = new \WHMCS\Validate();
                                $captcha->validateAppropriateCaptcha(\WHMCS\Utility\Captcha::FORM_LOGIN, $validate);
                                if ($validate->hasErrors()) {
                                    list($captchaFailed) = $validate->getErrors();
                                }
                            } catch (\Exception $e) {
                                $captchaFailed = $e->getMessage();
                            }
                        }
                        if (!is_null($captchaFailed)) {
                            $templatevars["errorMsg"] = $captchaFailed;
                        } else {
                            if ($admin && $admin->disabled == 1) {
                                $templatevars["errorMsg"] = "<strong>Administrator Disabled</strong>";
                            } else {
                                if (!$admin || $email != $admin->email && $email != $admin->username) {
                                    logActivity("Admin Password Reset Attempted for invalid Email: " . $email);
                                    $templatevars["errorMsg"] = "<strong>User or Email Address Not Found.</strong> Your IP has been logged.";
                                } else {
                                    $hasher = new \WHMCS\Security\Hash\Password();
                                    $randomString = genRandomVal(mt_rand(20, 40));
                                    $verificationToken = base64_encode($hasher->hash($randomString . $admin->id . $admin->email));
                                    $admin->passwordResetKey = $verificationToken;
                                    $admin->passwordResetData = $randomString;
                                    $admin->passwordResetExpiry = \WHMCS\Carbon::now()->addHours(2)->toDateTimeString();
                                    $admin->save();
                                    $url = \App::getSystemURL() . $adminfolder . "/login.php?action=reset&verify=" . $verificationToken;
                                    try {
                                        $extraParams = array("firstname" => $admin->firstName, "username" => $admin->lastName, "pw_reset_url" => $url);
                                        $mailer = \WHMCS\Mail\Entity\Admin::factoryByTemplate("Admin Password Reset Validation");
                                        $mailer->determineAdminRecipientsAndSender("", 0, $admin->id, false);
                                        foreach ($extraParams as $extraParam => $value) {
                                            $mailer->assign($extraParam, $value);
                                        }
                                        $mailer->send();
                                        $templatevars["errorMsg"] = "<strong>Success!</strong> Please check your email for the next step...";
                                        logActivity("Password Reset Initiated for Admin Username " . $admin->username);
                                    } catch (\WHMCS\Exception\Mail\SendFailure $e) {
                                        $templatevars["errorMsg"] = "There was an error sending the email. Please try again.";
                                    } catch (\WHMCS\Exception\Mail\SendHookAbort $e) {
                                        $templatevars["errorMsg"] = "This email cannot be sent. Please contact admin for support.";
                                    }
                                }
                            }
                        }
                    } else {
                        $templatevars["infoMsg"] = "Enter your email address below to begin the process...";
                    }
                }
            }
        }
        $templatevars["showSSLLink"] = \App::isSSLAvailable();
        $templatevars["showPasswordResetLink"] = (bool) (!$adminPasswordResetDisabled);
        $templatevars["languages"] = \WHMCS\Language\AdminLanguage::getLanguages();
        $assetHelper = \DI::make("asset");
        $templatevars["WEB_ROOT"] = $assetHelper->getWebRoot();
        $templatevars["BASE_PATH_CSS"] = $assetHelper->getCssPath();
        $templatevars["BASE_PATH_JS"] = $assetHelper->getJsPath();
        $templatevars["BASE_PATH_FONTS"] = $assetHelper->getFontsPath();
        $templatevars["BASE_PATH_IMG"] = $assetHelper->getImgPath();
        $smarty = new \WHMCS\Smarty(true);
        foreach ($templatevars as $key => $value) {
            $smarty->assign($key, $value);
        }
        $smarty->assign("captcha", new \WHMCS\Utility\Captcha());
        $smarty->assign("captchaForm", \WHMCS\Utility\Captcha::FORM_LOGIN);
        echo $smarty->fetch("login.tpl");
        return null;
    }
}

?>