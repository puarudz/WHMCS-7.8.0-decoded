<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

class Plesk_Manager_V1630 extends Plesk_Manager_V1000
{
    protected function _getResellerPlans()
    {
        $result = Plesk_Registry::getInstance()->api->resellerPlan_get();
        $resellerPlans = array();
        foreach ($result->xpath("//reseller-plan/get/result") as $result) {
            $resellerPlans[] = new Plesk_Object_ResellerPlan((int) $result->id, (string) $result->name);
        }
        return $resellerPlans;
    }
    protected function _getAccountInfo($params, $panelExternalId = NULL)
    {
        $accountInfo = array();
        if (is_null($panelExternalId)) {
            $this->createTableForAccountStorage();
            $account = WHMCS\Database\Capsule::table("mod_pleskaccounts")->where("userid", $params["clientsdetails"]["userid"])->where("usertype", $params["type"])->first();
            $panelExternalId = is_null($account) ? "" : $account->panelexternalid;
        }
        if ("" != $panelExternalId) {
            $requestParams = array("externalId" => $panelExternalId);
            switch ($params["type"]) {
                case Plesk_Object_Customer::TYPE_CLIENT:
                    try {
                        $result = Plesk_Registry::getInstance()->api->customer_get_by_external_id($requestParams);
                        if (isset($result->customer->get->result->id)) {
                            $accountInfo["id"] = (int) $result->customer->get->result->id;
                        }
                        if (isset($result->customer->get->result->data->gen_info->login)) {
                            $accountInfo["login"] = (string) $result->customer->get->result->data->gen_info->login;
                        }
                    } catch (Exception $e) {
                        if (Plesk_Api::ERROR_OBJECT_NOT_FOUND != $e->getCode()) {
                            throw $e;
                        }
                        throw new Exception(Plesk_Registry::getInstance()->translator->translate("ERROR_CUSTOMER_WITH_EXTERNAL_ID_NOT_FOUND_IN_PANEL", array("EXTERNAL_ID" => $panelExternalId)), Plesk_Api::ERROR_OBJECT_NOT_FOUND);
                    }
                    break;
                case Plesk_Object_Customer::TYPE_RESELLER:
                    try {
                        $result = Plesk_Registry::getInstance()->api->reseller_get_by_external_id($requestParams);
                        if (isset($result->reseller->get->result->id)) {
                            $accountInfo["id"] = (int) $result->reseller->get->result->id;
                        }
                        if (isset($result->reseller->get->result->data->_obfuscated_67656E2D696E666F_->login)) {
                            $accountInfo["login"] = (string) $result->reseller->get->result->data->_obfuscated_67656E2D696E666F_->login;
                        }
                    } catch (Exception $e) {
                        if (Plesk_Api::ERROR_OBJECT_NOT_FOUND != $e->getCode()) {
                            throw $e;
                        }
                        throw new Exception(Plesk_Registry::getInstance()->translator->translate("ERROR_RESELLER_WITH_EXTERNAL_ID_NOT_FOUND_IN_PANEL", array("EXTERNAL_ID" => $panelExternalId)), Plesk_Api::ERROR_OBJECT_NOT_FOUND);
                    }
                    break;
            }
            return $accountInfo;
        }
        $accountsArray = array();
        $productsOnServer = WHMCS\Database\Capsule::table("tblhosting")->where("server", $params["serverid"])->where("userid", $params["clientsdetails"]["userid"])->get();
        if ($productsOnServer) {
            foreach ($productsOnServer as $product) {
                $accountsArray[] = $product->username;
            }
        }
        $addonsOnServer = $addons = WHMCS\Service\Addon::with(array("customFieldValues", "customFieldValues.customField" => function ($query) {
            $query->where("fieldname", "=", "username");
        }))->where("server", $params["serverid"])->where("userid", $params["clientsdetails"]["userid"])->get();
        if ($addonsOnServer) {
            foreach ($addonsOnServer as $addon) {
                $pleskUsername = $addon->customFieldValue["username"];
                if ($pleskUsername) {
                    $accountsArray[] = $pleskUsername;
                }
            }
        }
        foreach ($accountsArray as $username) {
            $requestParams = array("login" => $username);
            switch ($params["type"]) {
                case Plesk_Object_Customer::TYPE_CLIENT:
                    try {
                        $result = Plesk_Registry::getInstance()->api->customer_get_by_login($requestParams);
                        if (isset($result->customer->get->result->id)) {
                            $accountInfo["id"] = (int) $result->customer->get->result->id;
                        }
                        if (isset($result->customer->get->result->data->gen_info->login)) {
                            $accountInfo["login"] = (string) $result->customer->get->result->data->gen_info->login;
                        }
                    } catch (Exception $e) {
                        if (Plesk_Api::ERROR_OBJECT_NOT_FOUND != $e->getCode()) {
                            throw $e;
                        }
                    }
                    break;
                case Plesk_Object_Customer::TYPE_RESELLER:
                    try {
                        $result = Plesk_Registry::getInstance()->api->reseller_get_by_login($requestParams);
                        if (isset($result->reseller->get->result->id)) {
                            $accountInfo["id"] = (int) $result->reseller->get->result->id;
                        }
                        if (isset($result->reseller->get->result->data->_obfuscated_67656E2D696E666F_->login)) {
                            $accountInfo["login"] = (string) $result->reseller->get->result->data->_obfuscated_67656E2D696E666F_->login;
                        }
                    } catch (Exception $e) {
                        if (Plesk_Api::ERROR_OBJECT_NOT_FOUND != $e->getCode()) {
                            throw $e;
                        }
                    }
                    break;
            }
            if (!empty($accountInfo)) {
                break;
            }
        }
        if (empty($accountInfo)) {
            throw new Exception(Plesk_Registry::getInstance()->translator->translate("ERROR_CUSTOMER_WITH_EMAIL_NOT_FOUND_IN_PANEL", array("EMAIL" => $params["clientsdetails"]["email"])), Plesk_Api::ERROR_OBJECT_NOT_FOUND);
        }
        return $accountInfo;
    }
    protected function _getAddAccountParams($params)
    {
        $result = parent::_getAddAccountParams($params);
        $result["externalId"] = $this->_getCustomerExternalId($params);
        return $result;
    }
    protected function _addAccount($params)
    {
        $accountId = NULL;
        $requestParams = $this->_getAddAccountParams($params);
        switch ($params["type"]) {
            case Plesk_Object_Customer::TYPE_CLIENT:
                $result = Plesk_Registry::getInstance()->api->customer_add($requestParams);
                $accountId = (int) $result->customer->add->result->id;
                break;
            case Plesk_Object_Customer::TYPE_RESELLER:
                $requestParams = array_merge($requestParams, array("planName" => $params["configoption2"]));
                $result = Plesk_Registry::getInstance()->api->reseller_add($requestParams);
                $accountId = (int) $result->reseller->add->result->id;
                break;
        }
        return $accountId;
    }
    protected function _addWebspace($params)
    {
        $this->_checkRestrictions($params);
        $requestParams = array("domain" => $params["domain"], "ownerId" => $params["ownerId"], "username" => $params["username"], "password" => $params["password"], "status" => Plesk_Object_Webspace::STATUS_ACTIVE, "htype" => Plesk_Object_Webspace::TYPE_VRT_HST, "planName" => $params["configoption1"], "ipv4Address" => $params["ipv4Address"], "ipv6Address" => $params["ipv6Address"]);
        Plesk_Registry::getInstance()->api->webspace_add($requestParams);
    }
    protected function _setResellerStatus($params)
    {
        $accountInfo = $this->_getAccountInfo($params);
        if (!isset($accountInfo["id"])) {
            return NULL;
        }
        Plesk_Registry::getInstance()->api->reseller_set_status(array("status" => $params["status"], "id" => $accountInfo["id"]));
    }
    protected function _deleteReseller($params)
    {
        $accountInfo = $this->_getAccountInfo($params);
        if (!isset($accountInfo["id"])) {
            return NULL;
        }
        Plesk_Registry::getInstance()->api->reseller_del(array("id" => $accountInfo["id"]));
    }
    protected function _setAccountPassword($params)
    {
        $accountInfo = $this->_getAccountInfo($params);
        if (!isset($accountInfo["id"])) {
            return NULL;
        }
        if (isset($accountInfo["login"]) && $accountInfo["login"] != $params["username"]) {
            return NULL;
        }
        $requestParams = array("id" => $accountInfo["id"], "accountPassword" => $params["password"]);
        switch ($params["type"]) {
            case Plesk_Object_Customer::TYPE_CLIENT:
                Plesk_Registry::getInstance()->api->customer_set_password($requestParams);
                break;
            case Plesk_Object_Customer::TYPE_RESELLER:
                Plesk_Registry::getInstance()->api->reseller_set_password($requestParams);
                break;
        }
    }
    protected function _deleteWebspace($params)
    {
        Plesk_Registry::getInstance()->api->webspace_del(array("domain" => $params["domain"]));
        $accountInfo = $this->_getAccountInfo($params);
        if (!isset($accountInfo["id"])) {
            return NULL;
        }
        $webspaces = $this->_getWebspacesByOwnerId($accountInfo["id"]);
        if (!isset($webspaces->id)) {
            Plesk_Registry::getInstance()->api->customer_del(array("id" => $accountInfo["id"]));
        }
    }
    protected function _switchSubscription($params)
    {
        switch ($params["type"]) {
            case Plesk_Object_Customer::TYPE_CLIENT:
                $result = Plesk_Registry::getInstance()->api->service_plan_get_by_name(array("name" => $params["configoption1"]));
                $servicePlanResult = reset($result->xpath("//service-plan/get/result"));
                Plesk_Registry::getInstance()->api->switch_subscription(array("domain" => $params["domain"], "planGuid" => (string) $servicePlanResult->guid));
                break;
            case Plesk_Object_Customer::TYPE_RESELLER:
                $result = Plesk_Registry::getInstance()->api->reseller_plan_get_by_name(array("name" => $params["configoption2"]));
                $resellerPlanResult = reset($result->xpath("//reseller-plan/get/result"));
                $accountInfo = $this->_getAccountInfo($params);
                if (!isset($accountInfo["id"])) {
                    return NULL;
                }
                Plesk_Registry::getInstance()->api->switch_reseller_plan(array("id" => $accountInfo["id"], "planGuid" => (string) $resellerPlanResult->guid));
                break;
        }
    }
    protected function _processAddons($params)
    {
        $result = Plesk_Registry::getInstance()->api->webspace_subscriptions_get_by_name(array("domain" => $params["domain"]));
        $planGuids = array();
        foreach ($result->xpath("//webspace/get/result/data/subscriptions/subscription/plan/plan-guid") as $guid) {
            $planGuids[] = (string) $guid;
        }
        $webspaceId = (int) $result->webspace->get->result->id;
        $exludedPlanGuids = array();
        $servicePlan = Plesk_Registry::getInstance()->api->service_plan_get_by_guid(array("planGuids" => $planGuids));
        foreach ($servicePlan->xpath("//service-plan/get/result") as $result) {
            try {
                $this->_checkErrors($result);
                $exludedPlanGuids[] = (string) $result->guid;
            } catch (Exception $e) {
                if (Plesk_Api::ERROR_OBJECT_NOT_FOUND != $e->getCode()) {
                    throw $e;
                }
            }
        }
        $addons = array();
        $addonGuids = array_diff($planGuids, $exludedPlanGuids);
        if (!empty($addonGuids)) {
            $addon = Plesk_Registry::getInstance()->api->service_plan_addon_get_by_guid(array("addonGuids" => $addonGuids));
            foreach ($addon->xpath("//service-plan-addon/get/result") as $result) {
                try {
                    $this->_checkErrors($result);
                    $addons[(string) $result->guid] = (string) $result->name;
                } catch (Exception $e) {
                    if (Plesk_Api::ERROR_OBJECT_NOT_FOUND != $e->getCode()) {
                        throw $e;
                    }
                }
            }
        }
        $addonsToRemove = array();
        $addonsFromRequest = array();
        foreach ($params["configoptions"] as $addonTitle => $value) {
            if ("0" == $value) {
                continue;
            }
            if (0 !== strpos($addonTitle, Plesk_Object_Addon::ADDON_PREFIX)) {
                continue;
            }
            $pleskAddonTitle = substr_replace($addonTitle, "", 0, strlen(Plesk_Object_Addon::ADDON_PREFIX));
            $addonsFromRequest[] = "1" == $value ? $pleskAddonTitle : $value;
        }
        foreach ($addons as $guid => $addonName) {
            if (!in_array($addonName, $addonsFromRequest)) {
                $addonsToRemove[$guid] = $addonName;
            }
        }
        $addonsToAdd = array_diff($addonsFromRequest, array_values($addons));
        foreach ($addonsToRemove as $guid => $addon) {
            Plesk_Registry::getInstance()->api->webspace_remove_subscription(array("planGuid" => $guid, "id" => $webspaceId));
        }
        foreach ($addonsToAdd as $addonName) {
            $addon = Plesk_Registry::getInstance()->api->service_plan_addon_get_by_name(array("name" => $addonName));
            foreach ($addon->xpath("//service-plan-addon/get/result/guid") as $guid) {
                Plesk_Registry::getInstance()->api->webspace_add_subscription(array("planGuid" => (string) $guid, "id" => $webspaceId));
            }
        }
    }
    protected function _getWebspacesUsage($params)
    {
        $usage = array();
        $data = Plesk_Registry::getInstance()->api->webspace_usage_get_by_name(array("domains" => $params["domains"]));
        foreach ($data->xpath("//webspace/get/result") as $result) {
            try {
                $this->_checkErrors($result);
                $domainName = (string) $result->data->gen_info->name;
                $usage[$domainName]["diskusage"] = (double) $result->data->gen_info->real_size;
                $usage[$domainName]["bwusage"] = (double) $result->data->stat->traffic;
                $usage[$domainName] = array_merge($usage[$domainName], $this->_getLimits($result->data->limits));
            } catch (Exception $e) {
                if (Plesk_Api::ERROR_OBJECT_NOT_FOUND != $e->getCode()) {
                    throw $e;
                }
            }
        }
        foreach ($data->xpath("//site/get/result") as $result) {
            try {
                $parentDomainName = (string) reset($result->xpath("filter-id"));
                $usage[$parentDomainName]["bwusage"] += (double) $result->data->stat->traffic;
            } catch (Exception $e) {
                if (Plesk_Api::ERROR_OBJECT_NOT_FOUND != $e->getCode()) {
                    throw $e;
                }
            }
        }
        foreach ($usage as $domainName => $domainUsage) {
            foreach ($domainUsage as $param => $value) {
                $usage[$domainName][$param] = $usage[$domainName][$param] / (1024 * 1024);
            }
        }
        return $usage;
    }
    protected function _getResellersUsage($params)
    {
        $usage = array();
        $data = Plesk_Registry::getInstance()->api->reseller_get_usage_by_login(array("logins" => $params["usernames"]));
        foreach ($data->xpath("//reseller/get/result") as $result) {
            try {
                $this->_checkErrors($result);
                $login = (string) $result->data->_obfuscated_67656E2D696E666F_->login;
                $usage[$login]["diskusage"] = (double) $result->data->stat->_obfuscated_6469736B2D7370616365_;
                $usage[$login]["bwusage"] = (double) $result->data->stat->traffic;
                $usage[$login] = array_merge($usage[$login], $this->_getLimits($result->data->limits));
            } catch (Exception $e) {
                if (Plesk_Api::ERROR_OBJECT_NOT_FOUND != $e->getCode()) {
                    throw $e;
                }
            }
        }
        foreach ($usage as $login => $loginUsage) {
            foreach ($loginUsage as $param => $value) {
                $usage[$login][$param] = $usage[$login][$param] / (1024 * 1024);
            }
        }
        return $usage;
    }
    protected function _addIpToIpPool($accountId, $params)
    {
    }
    protected function _getWebspacesByOwnerId($ownerId)
    {
        $result = Plesk_Registry::getInstance()->api->webspaces_get_by_owner_id(array("ownerId" => $ownerId));
        return $result->webspace->get->result;
    }
    protected function _getCustomerExternalId($params)
    {
        return Plesk_Object_Customer::getCustomerExternalId($params);
    }
    protected function _changeSubscriptionIp($params)
    {
        $webspace = Plesk_Registry::getInstance()->api->webspace_get_by_name(array("domain" => $params["domain"]));
        $ipDedicatedList = $this->_getIpList(Plesk_Object_Ip::DEDICATED);
        $oldIp[Plesk_Object_Ip::IPV4] = (string) $webspace->webspace->get->result->data->hosting->vrt_hst->ip_address;
        $ipv4Address = isset($oldIp[Plesk_Object_Ip::IPV4]) ? $oldIp[Plesk_Object_Ip::IPV4] : "";
        if ($params["configoption3"] == "IPv4 none; IPv6 shared" || $params["configoption3"] == "IPv4 none; IPv6 dedicated") {
            $ipv4Address = "";
        }
        if (!empty($params["ipv4Address"])) {
            if (isset($oldIp[Plesk_Object_Ip::IPV4]) && $oldIp[Plesk_Object_Ip::IPV4] != $params["ipv4Address"] && (!in_array($oldIp[Plesk_Object_Ip::IPV4], $ipDedicatedList) || !in_array($params["ipv4Address"], $ipDedicatedList))) {
                $ipv4Address = $params["ipv4Address"];
            } else {
                if (!isset($oldIp[Plesk_Object_Ip::IPV4])) {
                    $ipv4Address = $params["ipv4Address"];
                }
            }
        }
        if (!empty($ipv4Address)) {
            Plesk_Registry::getInstance()->api->webspace_set_ip(array("domain" => $params["domain"], "ipv4Address" => $ipv4Address));
        }
    }
    protected function _getLimits(SimpleXMLElement $limits)
    {
        $result = array();
        foreach ($limits->limit as $limit) {
            $name = (string) $limit->name;
            switch ($name) {
                case "disk_space":
                    $result["disklimit"] = (double) $limit->value;
                    break;
                case "max_traffic":
                    $result["bwlimit"] = (double) $limit->value;
                    break;
                default:
                    break;
            }
        }
        return $result;
    }
    protected function _getServicePlans()
    {
        $result = Plesk_Registry::getInstance()->api->service_plan_get();
        $plans = array();
        foreach ($result->xpath("//service-plan/get/result") as $plan) {
            $plans[] = (string) $plan->name;
        }
        return $plans;
    }
    protected function _generateCSR($params)
    {
        $accountInfo = $this->_getAccountInfo($params);
        if (!isset($accountInfo["id"])) {
            return "";
        }
        if (isset($accountInfo["login"]) && $accountInfo["login"] != $params["username"]) {
            return "";
        }
        return Plesk_Registry::getInstance()->api->certificate_generate($params["certificateInfo"]);
    }
    protected function _installSsl($params)
    {
        $accountInfo = $this->_getAccountInfo($params);
        if (!isset($accountInfo["id"])) {
            return "";
        }
        if (isset($accountInfo["login"]) && $accountInfo["login"] != $params["username"]) {
            return "";
        }
        return Plesk_Registry::getInstance()->api->certificate_install($params);
    }
    protected function _getMxRecords($params)
    {
        $accountInfo = $this->_getAccountInfo($params);
        if (!isset($accountInfo["id"])) {
            return "";
        }
        if (isset($accountInfo["login"]) && $accountInfo["login"] != $params["username"]) {
            return "";
        }
        $webSpace = Plesk_Registry::getInstance()->api->webspace_get_by_name(array("domain" => $params["domain"]));
        $siteId = (string) $webSpace->webspace->get->result->id;
        $records = Plesk_Registry::getInstance()->api->dns_record_retrieve(array("siteId" => (int) $siteId));
        $mxRecords = array();
        foreach ($records->dns->get_rec->result as $dnsRecord) {
            if (strtolower($dnsRecord->data->type->__toString()) !== "mx") {
                continue;
            }
            $mxData = (array) $dnsRecord->data;
            $mxRecords[] = array("id" => (int) $dnsRecord->id, "mx" => $mxData["value"], "priority" => $mxData["opt"]);
        }
        return array("mxRecords" => $mxRecords);
    }
    protected function _deleteMxRecords($params)
    {
        $accountInfo = $this->_getAccountInfo($params);
        if (!isset($accountInfo["id"])) {
            return NULL;
        }
        if (isset($accountInfo["login"]) && $accountInfo["login"] != $params["username"]) {
            return NULL;
        }
        $dnsToRemove = array();
        foreach ($params["mxRecords"] as $record) {
            $dnsToRemove[] = $record["id"];
        }
        Plesk_Registry::getInstance()->api->dns_record_delete(array("dnsRecords" => $dnsToRemove));
    }
    protected function _addMxRecords($params)
    {
        $accountInfo = $this->_getAccountInfo($params);
        if (!isset($accountInfo["id"])) {
            return NULL;
        }
        if (isset($accountInfo["login"]) && $accountInfo["login"] != $params["username"]) {
            return NULL;
        }
        $webSpace = Plesk_Registry::getInstance()->api->webspace_get_by_name(array("domain" => $params["domain"]));
        $siteId = (string) $webSpace->webspace->get->result->id;
        $params["pleskSiteId"] = $siteId;
        Plesk_Registry::getInstance()->api->mx_record_create($params);
    }
    protected function _listAccounts(array $params)
    {
        $data = Plesk_Registry::getInstance()->api->webspace_get_all(array());
        $response = array();
        foreach ($data->xpath("//webspace/get/result") as $webSpace) {
            $webSpaceData = $webSpace->data->gen_info;
            $planData = $webSpace->data->subscriptions;
            $planData = (array) $planData->subscription->plan;
            $planGuid = $planData["plan-guid"];
            $webSpaceDataArray = (array) $webSpaceData;
            $ownerId = $webSpaceDataArray["owner-id"];
            try {
                $ownerData = Plesk_Registry::getInstance()->api->customer_get_by_id(array("id" => $ownerId));
                list($ownerData) = $ownerData->xpath("//customer/get/result");
            } catch (Exception $e) {
                if ($e->getMessage() == "Client does not exist") {
                    $ownerData = Plesk_Registry::getInstance()->api->reseller_get_by_id(array("id" => $ownerId));
                    list($ownerData) = $ownerData->xpath("//reseller/get/result");
                } else {
                    throw $e;
                }
            }
            $username = $ownerData->xpath("//login")[0]->__toString();
            try {
                $servicePlan = Plesk_Registry::getInstance()->api->service_plan_get_by_guid(array("planGuids" => array($planGuid)));
                $planName = $servicePlan->xpath("//service-plan/get/result/name")[0]->__toString();
            } catch (Exception $e) {
                continue;
            }
            $status = WHMCS\Service\Status::ACTIVE;
            if ((int) $webSpaceDataArray["status"]) {
                $status = WHMCS\Service\Status::SUSPENDED;
            }
            $response[] = array("name" => $username, "email" => $ownerData->xpath("//email")[0]->__toString(), "username" => $username, "domain" => $webSpaceDataArray["name"], "uniqueIdentifier" => $webSpaceDataArray["name"], "product" => $planName, "primaryip" => $webSpaceDataArray["dns_ip_address"], "created" => $webSpaceDataArray["cr_date"] . " 00:00:00", "status" => $status);
        }
        return $response;
    }
    protected function _getCustomers(array $params)
    {
        $data = Plesk_Registry::getInstance()->api->customer_get();
        $data = $data->xpath("//result");
        return $data;
    }
    protected function _getCustomersByOwner(array $params)
    {
        $data = Plesk_Registry::getInstance()->api->customer_get_by_owner(array("ownerId" => $params["ownerId"]));
        $data = $data->xpath("//result");
        return $data;
    }
    protected function _getResellers(array $params)
    {
        $data = Plesk_Registry::getInstance()->api->reseller_get();
        $data = $data->xpath("//result");
        return $data;
    }
    protected function _getResellerByLogin(array $params)
    {
        $data = Plesk_Registry::getInstance()->api->reseller_get_by_login(array("login" => $params["username"]));
        return (array) $data->reseller->get->result;
    }
    protected function _getServerData(array $params)
    {
        $data = Plesk_Registry::getInstance()->api->get_server_info();
        return $data->server->get->result;
    }
}

?>