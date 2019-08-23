<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Notification\Events;

class Service
{
    const DISPLAY_NAME = "Service";
    public function getEvents()
    {
        return array("provisioned" => array("label" => "Provisioned", "hook" => array("AfterModuleCreate", "AfterModuleCreateFailed")), "suspended" => array("label" => "Suspended", "hook" => array("AfterModuleSuspend", "AfterModuleSuspendFailed")), "unsuspended" => array("label" => "Unsuspended", "hook" => array("AfterModuleUnsuspend", "AfterModuleUnsuspendFailed")), "terminated" => array("label" => "Terminated", "hook" => array("AfterModuleTerminate", "AfterModuleTerminateFailed")), "cancellation_request" => array("label" => "Cancellation Request Submitted", "hook" => "CancellationRequest"));
    }
    public function getConditions()
    {
        return array("product" => array("FriendlyName" => "Product/Service", "Type" => "dropdown", "Options" => function () {
            $options = array();
            foreach (\WHMCS\Product\Group::with("products")->sorted()->get() as $group) {
                foreach ($group->products()->sorted()->pluck("name", "id") as $pid => $name) {
                    $options[$pid] = $group->name . " - " . $name;
                }
            }
            return $options;
        }, "GetDisplayValue" => function ($value) {
            try {
                $product = \WHMCS\Product\Product::findOrFail($value);
                return $product->productGroup->name . " - " . $product->name;
            } catch (\Exception $e) {
                return "Product not found";
            }
        }), "action_state" => array("FriendlyName" => "Action State", "Type" => "dropdown", "Options" => array("Successful" => "Successful", "Failed" => "Failed")), "client_group" => array("FriendlyName" => "Client Group", "Type" => "dropdown", "Options" => function () {
            return \WHMCS\Database\Capsule::table("tblclientgroups")->orderBy("groupname")->pluck("groupname", "id");
        }));
    }
    public function evaluateConditions($event, $conditions, $hookParameters)
    {
        $serviceId = isset($hookParameters["params"]["serviceid"]) ? $hookParameters["params"]["serviceid"] : "";
        if ($conditions["product"]) {
            $productId = \WHMCS\Service\Service::find($serviceId)->packageid;
            if ($productId != $conditions["product"]) {
                return false;
            }
        }
        if ($conditions["action_state"]) {
            if ($conditions["action_state"] == "Failed" && !isset($hookParameters["failureResponseMessage"])) {
                return false;
            }
            if ($conditions["action_state"] == "Successful" && isset($hookParameters["failureResponseMessage"])) {
                return false;
            }
        }
        if ($conditions["client_group"]) {
            $userId = isset($hookParameters["params"]["userid"]) ? $hookParameters["params"]["userid"] : "";
            $clientGroup = 0 < $userId ? \WHMCS\User\Client::find($userId)->groupId : "";
            if ($conditions["client_group"] != $clientGroup) {
                return false;
            }
        }
        return true;
    }
    public function buildNotification($event, $hookParameters)
    {
        if ($event == "cancellation_request") {
            $serviceId = isset($hookParameters["relid"]) ? $hookParameters["relid"] : "";
        } else {
            $serviceId = isset($hookParameters["params"]["serviceid"]) ? $hookParameters["params"]["serviceid"] : "";
        }
        if (!$serviceId) {
            return NULL;
        }
        $service = \WHMCS\Service\Service::find($serviceId);
        $userId = $service->userId;
        $domain = $service->domain;
        $status = $service->status;
        $firstName = $service->client->firstName;
        $lastName = $service->client->lastName;
        $nextDueDate = $service->nextDueDate;
        $failureResponseMessage = isset($hookParameters["failureResponseMessage"]) ? $hookParameters["failureResponseMessage"] : "";
        $url = \App::getSystemUrl() . \App::get_admin_folder_name() . "/clientsservices.php?userid=" . $userId . "&id=" . $serviceId;
        $clientUrl = \App::getSystemUrl() . \App::get_admin_folder_name() . "/clientssummary.php?userid=" . $userId;
        $title = \AdminLang::trans("notifications.service." . $event . "Title" . ($failureResponseMessage ? "Error" : ""));
        $message = \AdminLang::trans("notifications.service." . $event . ($failureResponseMessage ? "Error" : ""));
        $statusStyle = "primary";
        if ($status == "Active") {
            $statusStyle = "success";
        } else {
            if ($status == "Terminated") {
                $statusStyle = "danger";
            } else {
                if ($status == "Suspended") {
                    $statusStyle = "info";
                }
            }
        }
        $notification = (new \WHMCS\Notification\Notification())->setTitle($title)->setMessage($message)->setUrl($url)->addAttribute((new \WHMCS\Notification\NotificationAttribute())->setLabel(\AdminLang::trans("fields.client"))->setValue($firstName . " " . $lastName)->setUrl($clientUrl));
        if ($domain) {
            $notification->addAttribute((new \WHMCS\Notification\NotificationAttribute())->setLabel(\AdminLang::trans("fields.domain"))->setValue($domain)->setUrl("http://" . $domain));
        }
        if ($failureResponseMessage) {
            $notification->addAttribute((new \WHMCS\Notification\NotificationAttribute())->setLabel(\AdminLang::trans("fields.failureMessage"))->setValue($failureResponseMessage)->setStyle("danger"));
        }
        return $notification->addAttribute((new \WHMCS\Notification\NotificationAttribute())->setLabel(\AdminLang::trans("fields.status"))->setValue($status)->setStyle($statusStyle));
    }
}

?>