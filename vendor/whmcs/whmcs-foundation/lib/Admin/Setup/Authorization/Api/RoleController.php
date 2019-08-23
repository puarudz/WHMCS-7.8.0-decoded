<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Setup\Authorization\Api;

class RoleController
{
    public function listRoles(\WHMCS\Http\Message\ServerRequest $request)
    {
        $roles = \WHMCS\Api\Authorization\ApiRole::where("id", ">", 0)->orderBy("role", "asc")->get();
        $helper = new View\RoleHelper();
        return new \WHMCS\Http\Message\JsonResponse(array("data" => $helper->getTableData($roles)));
    }
    public function manage(\WHMCS\Http\Message\ServerRequest $request)
    {
        $role = \WHMCS\Api\Authorization\ApiRole::findOrNew($request->get("roleId", 0));
        $csrfToken = generate_token("plain");
        $htmlPartial = view("authorization.partials.api-role-detail", array("apiCatalog" => \WHMCS\Api\V1\Catalog::get(), "role" => $role, "csrfToken" => $csrfToken));
        return new \WHMCS\Http\Message\JsonResponse(array("body" => $htmlPartial));
    }
    public function get(\WHMCS\Http\Message\ServerRequest $request)
    {
        $id = $request->get("roleId", 0);
        $role = \WHMCS\Api\Authorization\ApiRole::find($id);
        if (!$role) {
            return new \WHMCS\Http\Message\JsonResponse(array("error" => "Role Not Found"));
        }
        $data["data"] = $role->toArray();
        return new \WHMCS\Http\Message\JsonResponse($data);
    }
    public function create(\WHMCS\Http\Message\ServerRequest $request)
    {
        $roleId = $request->get("roleId", 0);
        if ($roleId) {
            return $this->update($request);
        }
        $statusCode = 200;
        $roleName = trim($request->get("roleName", ""));
        if (!$roleName) {
            return new \WHMCS\Http\Message\JsonResponse(array("status" => "error", "errorMsg" => "Role name cannot be empty."));
        }
        if (\WHMCS\Api\Authorization\ApiRole::where("role", "=", $roleName)->count()) {
            return new \WHMCS\Http\Message\JsonResponse(array("status" => "error", "errorMsg" => "A role with that name already exists. A role name must be unique."));
        }
        $roleDesc = $request->get("roleDescription", "");
        $requestedAllow = $request->get("allow", array());
        $catalogActions = \WHMCS\Api\V1\Catalog::get()->getActions();
        $role = new \WHMCS\Api\Authorization\ApiRole(array("role" => $roleName, "description" => $roleDesc));
        $allowed = array();
        foreach ($catalogActions as $action => $details) {
            if (array_key_exists($action, $requestedAllow)) {
                array_push($allowed, $action);
            }
        }
        $role->allow($allowed);
        if ($role->save()) {
            $msg = sprintf("Created role \"%d%s\" with access to \"%s\"", $role->id, $role->role ? ": " . $role->role : "", implode(", ", array_keys(array_filter($role->listAll()))));
            logActivity($msg);
            $data = array("status" => "success", "data" => $role->toArray(), "dismiss" => true);
        } else {
            $statusCode = 500;
            $data = array("status" => "error", "errorMsg" => "unknown error");
        }
        return new \WHMCS\Http\Message\JsonResponse($data, $statusCode);
    }
    public function delete(\WHMCS\Http\Message\ServerRequest $request)
    {
        $statusCode = 200;
        $roleId = $request->get("roleId", 0);
        $role = \WHMCS\Api\Authorization\ApiRole::find($roleId);
        if ($role) {
            \WHMCS\Authentication\Device::purgeRoleFromAllDevices($role);
            $msg = sprintf("Deleted role \"%d%s\" with access to \"\"", $roleId, $role->role ? ": " . $role->role : "", implode(", ", array_keys(array_filter($role->listAll()))));
            if ($role->delete()) {
                logActivity($msg);
                $data = array("status" => "success", "data" => $role->toArray());
            } else {
                $statusCode = 500;
                $data = array("status" => "error", "errorMessage" => "Failed to delete role: unknown error");
            }
        } else {
            $statusCode = 400;
            $data = array("status" => "error", "errorMessage" => "Failed to delete role: Unknown role id");
        }
        return new \WHMCS\Http\Message\JsonResponse($data, $statusCode);
    }
    public function update(\WHMCS\Http\Message\ServerRequest $request)
    {
        $statusCode = 200;
        $roleId = $request->get("roleId", 0);
        $role = \WHMCS\Api\Authorization\ApiRole::find($roleId);
        $logMsgs = array();
        if ($role) {
            $newName = trim($request->get("roleName", ""));
            if (!$newName) {
                return new \WHMCS\Http\Message\JsonResponse(array("status" => "error", "errorMsg" => "Role name cannot be empty."));
            }
            if ($newName != $role->role) {
                if (\WHMCS\Api\Authorization\ApiRole::where("role", "=", $newName)->count()) {
                    return new \WHMCS\Http\Message\JsonResponse(array("status" => "error", "errorMsg" => "A role with that name already exists. A role name must be unique."));
                }
                $logMsgs[] = sprintf("Role %d%s name changed from \"%s\" to \"%s\"", $roleId, $role->role ? ": " . $role->role : "", $role->role, $newName);
                $role->role = $newName;
            }
            $newDescription = trim($request->get("roleDescription", ""));
            if ($newDescription != $role->description) {
                $logMsgs[] = sprintf("Role %d%s description changed to \"%s\"", $roleId, $role->role ? ": " . $role->role : "", $newDescription);
                $role->description = $newDescription;
            }
            $requestedAllow = $request->get("allow", array());
            $catalogActions = \WHMCS\Api\V1\Catalog::get()->getActions();
            $allowed = array();
            foreach ($catalogActions as $action => $details) {
                if (array_key_exists($action, $requestedAllow) && $action !== "setconfigurationvalue") {
                    array_push($allowed, $action);
                }
            }
            $previousList = array_keys(array_filter($role->listAll()));
            $nowDenied = array_diff($previousList, $allowed);
            if ($nowDenied) {
                $logMsgs[] = sprintf("Role %d update - permissions revoked: \"%s\"", $roleId, implode(", ", $nowDenied));
            }
            $nowAllowed = array_diff($allowed, $previousList);
            if ($nowAllowed) {
                $logMsgs[] = sprintf("Role %d update - permissions granted: \"%s\"", $roleId, implode(", ", $nowAllowed));
            }
            $role->setData(array());
            $role->allow($allowed);
            if ($role->save()) {
                foreach ($logMsgs as $msg) {
                    logActivity($msg);
                }
                $data = array("status" => "success", "data" => $role->toArray(), "dismiss" => true);
            } else {
                $statusCode = 500;
                $data = array("status" => "error", "errorMsg" => "unknown error");
            }
        } else {
            $statusCode = 400;
            $data = array("status" => "error", "errorMsg" => "Unknown roled id " . (int) $roleId);
        }
        return new \WHMCS\Http\Message\JsonResponse($data, $statusCode);
    }
    public function selectOptions(\WHMCS\Http\Message\ServerRequest $request)
    {
        $roles = \WHMCS\Api\Authorization\ApiRole::all();
        $selectOptions = array();
        foreach ($roles as $role) {
            $selectOptions[] = sprintf("<option value=\"%d\">%s</option>", $role->id, $role->role);
        }
        if (empty($selectOptions)) {
            $selectOptions[] = sprintf("<option value=\"\" disabled>%s</option>", \AdminLang::trans("apirole.noRolesDefined"));
        }
        return new \WHMCS\Http\Message\JsonResponse(array("data" => $selectOptions));
    }
}

?>