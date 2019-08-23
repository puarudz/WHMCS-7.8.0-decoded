<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Authorization\Rbac;

class AccessList implements \WHMCS\Authorization\Contracts\PermissionInterface
{
    use PermissionTrait {
        setData as _setData;
    }
    public function __construct(array $rbacs = array())
    {
        $data = array();
        if (1 < count($rbacs)) {
            $data = $this->mergePermissions($rbacs);
        } else {
            if (!empty($rbacs)) {
                $accessList = array_shift($rbacs);
                if ($accessList instanceof \WHMCS\Authorization\Contracts\PermissionInterface) {
                    $data = $accessList->listAll();
                } else {
                    if (is_array($accessList)) {
                        $data = $accessList;
                    }
                }
            }
        }
        $this->_setData($data);
    }
    protected function mergePermissions(array $permissionsToMerge)
    {
        $data = array();
        foreach ($permissionsToMerge as $permissions) {
            if ($permissions instanceof \WHMCS\Authorization\Contracts\PermissionInterface) {
                $list = $permissions->listAll();
            } else {
                $list = is_array($permissions) ? $permissions : array();
            }
            foreach ($list as $key => $value) {
                if (array_key_exists($key, $data) && $value) {
                    $data[$key] = $value;
                } else {
                    $data[$key] = $value;
                }
            }
        }
        return $data;
    }
    public function toJson($options = 0)
    {
        return json_encode($this->getData(), $options);
    }
    public function __toString()
    {
        return $this->toJson();
    }
    public function setData(array $data = array())
    {
        throw new \LogicException("Instances of " . "WHMCS\\Authorization\\Rbac\\AccessList" . " are a read-only resources");
    }
}

?>