<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Api\V1;

class Api
{
    protected $adminId = 0;
    protected $action = NULL;
    protected $params = array();
    protected $status = NULL;
    protected $response = array();
    protected $registerLocalVars = false;
    protected $request = NULL;
    protected $isAdminUserRequired = true;
    public function getIsAdminUserRequired()
    {
        return $this->isAdminUserRequired;
    }
    public function setIsAdminUserRequired($isAdminUserRequired)
    {
        $this->isAdminUserRequired = $isAdminUserRequired;
        return $this;
    }
    public function getRequest()
    {
        return $this->request;
    }
    public function setRequest($request)
    {
        $this->request = $request;
        return $this;
    }
    public function __construct()
    {
        if (\WHMCS\Session::get("adminid")) {
            $this->adminId = \WHMCS\Session::get("adminid");
        }
    }
    public function setAction($action)
    {
        $action = strtolower($action);
        if (!isValidforPath($action)) {
            throw new \WHMCS\Exception\Api\InvalidAction("Invalid API Command");
        }
        if (!file_exists(ROOTDIR . "/includes/api/" . $action . ".php")) {
            throw new \WHMCS\Exception\Api\ActionNotFound("API Function Not Found");
        }
        $this->action = $action;
        return $this;
    }
    protected function getAction()
    {
        return $this->action;
    }
    public function setAdminUser($user)
    {
        $adminId = 0;
        if (is_numeric($user)) {
            $admin = \Illuminate\Database\Capsule\Manager::table("tbladmins")->find($user, array("id"));
            if (!is_null($admin)) {
                $adminId = $admin->id;
            }
        } else {
            $admin = \Illuminate\Database\Capsule\Manager::table("tbladmins")->where("username", "=", $user)->first(array("id"));
            if (!is_null($admin)) {
                $adminId = $admin->id;
            }
        }
        if (!$adminId) {
            throw new \WHMCS\Exception\Api\InvalidUser("No matching admin user found");
        }
        $this->adminId = $adminId;
        return $this;
    }
    protected function getAdminUser()
    {
        if (!$this->adminId && $this->isAdminUserRequired) {
            throw new \WHMCS\Exception\Api\InvalidUser("An admin user is required");
        }
        return $this->adminId;
    }
    public function setParam($name, $value)
    {
        $this->params[$name] = $value;
        return $this;
    }
    public function setParams(array $params)
    {
        $params = \App::self()->clean_param_array($params);
        foreach ($params as $name => $value) {
            $this->setParam($name, $value);
        }
        return $this;
    }
    protected function getParams()
    {
        return $this->params;
    }
    protected function executeApiCall()
    {
        $whmcs = \App::self();
        $whmcs->replace_input($this->getParams());
        $_POSTbackup = $_POST;
        $_REQUESTbackup = $_REQUEST;
        if ($this->registerLocalVars) {
            $_POST = $_REQUEST = array();
            foreach ($this->params as $k => $v) {
                $_POST[$k] = $v;
                $_REQUEST[$k] = $_POST[$k];
                ${$k} = $_REQUEST[$k];
            }
        }
        $responsetype = null;
        $apiresults = array();
        $whmcs = \App::self();
        $whmcsAppConfig = $whmcs->getApplicationConfig();
        $templates_compiledir = $whmcsAppConfig["templates_compiledir"];
        $downloads_dir = $whmcsAppConfig["downloads_dir"];
        $attachments_dir = $whmcsAppConfig["attachments_dir"];
        $customadminpath = $whmcsAppConfig["customadminpath"];
        $licensing = \DI::make("license");
        global $characterSet;
        global $CONFIG;
        global $currency;
        $request = $this->getRequest();
        try {
            require ROOTDIR . "/includes/api/" . $this->getAction() . ".php";
        } finally {
            if ($this->registerLocalVars) {
                foreach ($this->params as $k => $v) {
                    unset($k);
                }
                $_POST = $_POSTbackup;
                $_REQUEST = $_REQUESTbackup;
            }
            $whmcs->reset_input();
        }
    }
    public function call($action = "")
    {
        $adminId = $this->getAdminUser();
        if ($action) {
            $this->setAction($action);
        }
        $currentAdminId = \WHMCS\Session::get("adminid");
        if ($adminId && $currentAdminId != $adminId) {
            \WHMCS\Session::set("adminid", $adminId);
        }
        try {
            $apiResults = $this->executeApiCall();
        } finally {
            if ($currentAdminId) {
                \WHMCS\Session::set("adminid", $currentAdminId);
            } else {
                \WHMCS\Session::delete("adminid");
            }
        }
    }
    public function get($key)
    {
        return isset($this->response[$key]) ? $this->response[$key] : null;
    }
    public function getResults()
    {
        return $this->response;
    }
    public function getRegisterLocalVars()
    {
        return $this->registerLocalVars;
    }
    public function setRegisterLocalVars($registerLocalVars)
    {
        $this->registerLocalVars = $registerLocalVars;
        return $this;
    }
}

?>