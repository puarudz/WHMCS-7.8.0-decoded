<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCSProjectManagement;

class Permission
{
    protected $permissions = array("Create New Projects", "View All Projects", "13" => "View Only Assigned Projects", "2" => "Edit Project Details", "3" => "Update Status", "4" => "Create Tasks", "5" => "Edit Tasks", "6" => "Delete Tasks", "7" => "Bill Tasks", "8" => "Associate Tickets", "9" => "Post Messages", "10" => "View Reports", "11" => "Delete Messages", "12" => "Delete Projects", "View Recent Activity");
    protected function isMasterAdmin($roleId = 0, $adminId = 0)
    {
        static $masterAdmins = array();
        if (!$roleId) {
            $roleId = \WHMCS\User\Admin::findOrNew($adminId ?: \WHMCS\Session::get("adminid"))->roleId;
        }
        if (!$roleId) {
            return false;
        }
        if (!array_key_exists($roleId, $masterAdmins)) {
            $masterAdmins[$roleId] = \WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "project_management")->where("setting", "masteradmin" . $roleId)->value("value") == "on";
        }
        return $masterAdmins[$roleId];
    }
    public function check($permissionName)
    {
        static $roleId = 0;
        static $permissions = NULL;
        if (!$permissions) {
            $permissions = safe_unserialize(\WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "project_management")->where("setting", "perms")->value("value"));
        }
        if (!$roleId) {
            $roleId = \WHMCS\User\Admin::findOrNew(\WHMCS\Session::get("adminid"))->roleId;
        }
        if (!$roleId) {
            return false;
        }
        $reversedPermissions = array_flip($this->permissions);
        if ($this->isMasterAdmin() || $permissions[$reversedPermissions[$permissionName]][$roleId]) {
            return true;
        }
        return false;
    }
    public static function getPermissionList()
    {
        $permission = new self();
        return $permission->permissions;
    }
}

?>