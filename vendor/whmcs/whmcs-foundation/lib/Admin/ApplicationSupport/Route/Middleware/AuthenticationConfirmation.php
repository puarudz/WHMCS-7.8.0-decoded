<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\ApplicationSupport\Route\Middleware;

class AuthenticationConfirmation extends Authentication
{
    public function process(\Psr\Http\Message\ServerRequestInterface $request, \Interop\Http\ServerMiddleware\DelegateInterface $delegate)
    {
        $request = parent::_process($request, $delegate);
        $user = $request->getAttribute("authenticatedUser");
        if ($request->request()->has("authconfirm")) {
            $result = $this->checkUserConfirmation($request, $user);
            if ($result instanceof \Psr\Http\Message\ResponseInterface) {
                return $result;
            }
            return $delegate->process($request);
        }
        if ($this->isPreviousConfirmationStale()) {
            return $this->authenticationConfirmation($request);
        }
        return $delegate->process($request);
    }
    public function authenticationConfirmation(\WHMCS\Http\Message\ServerRequest $request)
    {
        $previousPost = $request->request()->all();
        if (!is_array($previousPost)) {
            $previousPost = array();
        }
        foreach (array("confirmpw", "token", "authconfirm") as $key) {
            unset($previousPost[$key]);
        }
        $repostFields = array();
        $fillData = function ($key, $value, &$fields, $path = "") use(&$fillData) {
            if (is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                    $fillData($key, $subValue, $fields, $path . "[" . $subKey . "]");
                }
            } else {
                $fields[$key . $path] = $value;
            }
        };
        foreach ($previousPost as $key => $value) {
            $fillData($key, $value, $repostFields);
        }
        return (new \WHMCS\Admin\ApplicationSupport\View\Html\Smarty\TemplateBody("authconfirm"))->setTitle("Admin Authentication")->setTemplateVariables(array("incorrect" => $request->request()->has("authconfirm"), "post_fields" => $repostFields));
    }
    protected function checkUserConfirmation(\WHMCS\Http\Message\ServerRequest $request, \WHMCS\User\Admin $user)
    {
        $userPassword = $request->request()->get("confirmpw");
        $auth = $this->getAdminAuth();
        if ($auth->getAdminID() == $user->id && $auth->comparePassword($userPassword)) {
            @\WHMCS\Session::start();
            \WHMCS\Session::set("AuthConfirmationTimestamp", \WHMCS\Carbon::now()->getTimestamp());
            return $request;
        }
        return $this->authenticationConfirmation($request);
    }
    protected function isPreviousConfirmationStale()
    {
        $isStale = true;
        $authConfirmationTimestamp = \WHMCS\Session::get("AuthConfirmationTimestamp");
        if (!empty($authConfirmationTimestamp) && is_numeric($authConfirmationTimestamp)) {
            $seconds = \WHMCS\Carbon::createFromTimestamp($authConfirmationTimestamp)->diffInSeconds(\WHMCS\Carbon::now());
            $isStale = 30 * 60 <= $seconds;
        }
        return $isStale;
    }
}

?>