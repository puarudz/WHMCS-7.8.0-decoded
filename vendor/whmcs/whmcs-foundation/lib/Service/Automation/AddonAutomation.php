<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Service\Automation;

class AddonAutomation
{
    protected $action = "";
    protected $addon = NULL;
    protected $aliasActions = array("CancelAccount" => "TerminateAccount", "Fraud" => "TerminateAccount");
    protected $error = "";
    protected $supportedActions = array("CreateAccount" => "AddonActivation", "SuspendAccount" => "AddonSuspended", "UnsuspendAccount" => "AddonUnsuspended", "TerminateAccount" => "AddonTerminated", "CancelAccount" => "AddonCancelled", "Fraud" => "AddonFraud", "Renew" => "", "ChangePassword" => "", "LoginLink" => "", "ChangePackage" => "", "CustomFunction" => "", "ClientArea" => "");
    public static function factory($addon)
    {
        $self = new self();
        if ($addon instanceof \WHMCS\Service\Addon) {
            $self->addon = $addon;
        } else {
            $self->addon = \WHMCS\Service\Addon::findOrFail($addon);
        }
        return $self;
    }
    protected function setAction($action)
    {
        $this->action = $action;
    }
    public function getAction()
    {
        return $this->action;
    }
    public function getError()
    {
        return $this->error;
    }
    protected function addError($error)
    {
        $this->error = $error;
    }
    public function runAction($action, $extra = "")
    {
        if (!array_key_exists($action, $this->supportedActions)) {
            throw new \WHMCS\Exception\Module\NotServicable("Invalid Action");
        }
        $this->setAction($action == "CustomFunction" ? $extra : $action);
        if (!function_exists("ModuleCallFunction")) {
            require_once ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "modulefunctions.php";
        }
        switch ($action) {
            case "CustomFunction":
            case "SuspendAccount":
                $variables = array($this->addon->serviceId, $extra, $this->addon->id);
                break;
            default:
                $variables = array($this->addon->serviceId, $this->addon->id);
        }
        $result = call_user_func_array("Server" . $action, $variables);
        switch ($result) {
            case "success":
                break;
            default:
                $this->addError($result);
                return false;
        }
        $this->runHook();
        return true;
    }
    protected function runHook()
    {
        if ($this->supportedActions[$this->getAction()]) {
            run_hook($this->supportedActions[$this->getAction()], array("id" => $this->addon->id, "userid" => $this->addon->clientId, "serviceid" => $this->addon->serviceId, "addonid" => $this->addon->addonId));
        }
    }
}

?>