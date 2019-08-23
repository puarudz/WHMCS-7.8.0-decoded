<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Setup\Authorization\Api\View;

class RoleHelper
{
    public function getTableData($roles)
    {
        $tableData = array();
        $catalogActions = \WHMCS\Api\V1\Catalog::get()->getActions();
        foreach ($roles as $role) {
            $actionNames = $allowedActions = array();
            foreach ($role->listAll() as $action => $allowed) {
                if (array_key_exists($action, $catalogActions)) {
                    $actionNames[] = $catalogActions[$action]["name"];
                }
            }
            if (empty($actionNames)) {
                $allowedActions[] = \AdminLang::trans("global.none");
            } else {
                $allowedActions = $actionNames;
            }
            $tableData[] = array("btnExpand" => "<i class=\"fas fa-caret-right text-muted\" aria-hidden=\"true\"></i>", "name" => $role->role, "description" => $role->description, "btnGroup" => $this->getActionBtnGroup($role->id), "allowedActions" => $allowedActions);
        }
        return $tableData;
    }
    protected function getActionBtnGroup($id)
    {
        $btnDelete = sprintf("<div class=\"btn btn-default btn-sm\" data-toggle=\"confirmation\"\n                    id=\"btnRoleDeleteId%d\"\n                    data-btn-ok-label=\"%s\"\n                    data-btn-ok-icon=\"fas fa-trash-alt\"\n                    data-btn-ok-class=\"btn-success\"\n                    data-btn-cancel-label=\"%s\"\n                    data-btn-cancel-icon=\"fas fa-ban\"\n                    data-btn-cancel-class=\"btn-default\"\n                    data-title=\"%s\"\n                    data-content=\"%s\"\n                    data-popout=\"true\"\n                    data-placement=\"left\"\n                    data-container=\"#btnRoleConf%d\"\n                    data-target-url=\"%s/%d\"\n                    >%s</div>", $id, \AdminLang::trans("global.delete"), \AdminLang::trans("global.cancel"), \AdminLang::trans("global.areYouSure"), \AdminLang::trans("global.deleteconfirmitem"), $id, routePath("admin-setup-authz-api-roles-delete"), $id, "<i class=\"fas fa-trash-alt\"></i>");
        $btnEdit = sprintf("<a href=\"%s/%d\"\n               data-modal-title=\"Role Management\"\n               data-modal-size=\"modal-lg\"\n               data-modal-class=\"modal-manage-api-role\"\n               data-btn-submit-id=\"btnUpdateApiRole\"\n               data-datatable-reload-success=\"tblApiRoles\"\n               data-btn-submit-label=\"%s\"\n               onclick=\"return false;\"\n               class=\"btn btn-default btn-sm open-modal\"><i class=\"fas fa-pencil-alt\"></i></a>", routePath("admin-setup-authz-api-roles-manage"), $id, \AdminLang::trans("global.save"));
        return sprintf("<div id=\"btnRoleConf%d\"></div>\n            <div class=\"btn-group pull-right\" id=\"btnRoleGroupId%d\">\n            %s\n            %s\n            </div>", $id, $id, $btnEdit, $btnDelete);
    }
}

?>