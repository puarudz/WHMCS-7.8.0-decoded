<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

class Plesk_Manager_V1640 extends Plesk_Manager_V1635
{
    protected function _getWebspacesUsage($params)
    {
        $usage = array();
        $webspaces = Plesk_Registry::getInstance()->api->webspace_usage_get_by_name(array("domains" => $params["domains"]));
        foreach ($webspaces->xpath("//webspace/get/result") as $result) {
            try {
                $this->_checkErrors($result);
                $domainName = (string) $result->data->gen_info->name;
                $usage[$domainName]["diskusage"] = (double) $result->data->gen_info->real_size;
                $resourceUsage = (array) $result->data->xpath("resource-usage");
                $resourceUsage = reset($resourceUsage);
                foreach ($resourceUsage->resource as $resource) {
                    $name = (string) $resource->name;
                    if ("max_traffic" == $name) {
                        $usage[$domainName]["bwusage"] = (double) $resource->value;
                        break;
                    }
                }
                $usage[$domainName] = array_merge($usage[$domainName], $this->_getLimits($result->data->limits));
                foreach ($usage[$domainName] as $param => $value) {
                    $usage[$domainName][$param] = $usage[$domainName][$param] / (1024 * 1024);
                }
            } catch (Exception $e) {
                if (Plesk_Api::ERROR_OBJECT_NOT_FOUND != $e->getCode()) {
                    throw $e;
                }
            }
        }
        return $usage;
    }
}

?>