<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Setup\Notifications;

class NotificationsController
{
    public function index(\WHMCS\Http\Message\ServerRequest $request)
    {
        $view = (new \WHMCS\Admin\ApplicationSupport\View\Html\Smarty\BodyContentWrapper())->setTitle(\AdminLang::trans("notifications.title"))->setSidebarName("config")->setFavicon("notifications")->setHelpLink("Notifications");
        $inputActivate = \App::getFromRequest("activate");
        $moduleNameToActivate = "";
        $notificationProviders = array();
        $notificationsInterface = new \WHMCS\Module\Notification();
        foreach ($notificationsInterface->getList() as $module) {
            if ($notificationsInterface->load($module)) {
                $className = $notificationsInterface->getClassPath();
                $provider = new $className();
                if ($provider instanceof \WHMCS\Module\Contracts\NotificationModuleInterface) {
                    $notificationProviders[] = $provider;
                    if (strtolower($module) == strtolower($inputActivate)) {
                        $moduleNameToActivate = $provider->getName();
                    }
                }
            }
        }
        $output = view("notifications.index", array("notificationProviders" => $notificationProviders, "activateModuleName" => $moduleNameToActivate));
        $view->setBodyContent($output);
        return $view;
    }
    public function listNotifications(\WHMCS\Http\Message\ServerRequest $request)
    {
        $providers = array();
        foreach (\WHMCS\Notification\Provider::all() as $provider) {
            try {
                $providers[$provider->name] = $provider->initObject();
            } catch (\WHMCS\Exception $e) {
            }
        }
        $tableData = array();
        try {
            foreach (\WHMCS\Notification\Rule::orderBy("updated_at", "desc")->get() as $rule) {
                $event = \WHMCS\Notification\Events::factory($rule->event_type);
                if (is_null($event)) {
                    continue;
                }
                $events = array();
                $eventDefinitions = $event->getEvents();
                foreach (explode(",", $rule->events) as $eventKey) {
                    if (isset($eventDefinitions[$eventKey]["label"])) {
                        $events[] = $eventDefinitions[$eventKey]["label"];
                    }
                }
                $conditions = array();
                $conditionsDefinitions = $event->getConditions();
                foreach ($rule->conditions as $key => $value) {
                    if ($value && !in_array($value, array("contains", "exact", "greater", "less"))) {
                        $displayValue = $value;
                        if (isset($conditionsDefinitions[$key]["GetDisplayValue"])) {
                            $function = $conditionsDefinitions[$key]["GetDisplayValue"];
                            $displayValue = $function($displayValue);
                        }
                        $conditions[] = $conditionsDefinitions[$key]["FriendlyName"] . " => " . $displayValue;
                    }
                }
                $tableConditions = 0 < count($conditions) ? implode("<br>", $conditions) : "None";
                $tableProviders = isset($providers[$rule->provider]) ? $providers[$rule->provider]->getDisplayName() : $rule->provider;
                $tableData[] = array($rule->description, "<strong>" . $event::DISPLAY_NAME . "</strong><br>" . implode("<br>", $events), $tableConditions, $tableProviders, "<span class=\"hidden\">" . $rule->updated_at->toAtomString() . "</span>" . $rule->updated_at->diffForHumans(), "<input type=\"checkbox\" class=\"status-switch\" data-id=\"" . $rule->id . "\"" . ($rule->active ? " checked" : "") . ">", "<div class=\"btn-group\">\n                        <a href=\"" . routePath("admin-setup-notifications-rule-edit", $rule->id) . "\" class=\"btn btn-default btn-sm open-modal\" data-modal-size=\"modal-lg\" data-modal-title=\"Edit Notification Rule\" data-btn-submit-id=\"EditRule\" data-btn-submit-label=\"Save Changes\">\n                            <i class=\"fas fa-pencil-alt\"></i>\n                        </a>\n                        <a href=\"" . routePath("admin-setup-notifications-rule-duplicate", $rule->id) . "\" class=\"btn btn-default btn-sm open-modal\" data-modal-size=\"modal-lg\" data-modal-title=\"Duplicate Existing Rule\" data-btn-submit-id=\"DuplicateRule\" data-btn-submit-label=\"Duplicate\">\n                            <i class=\"far fa-copy\"></i>\n                        </a>\n                        <a href=\"#\" data-role=\"deleteRule\" class=\"btn btn-default btn-sm\" onclick=\"deleteRule('" . $rule->id . "');return false\">\n                            <i class=\"fas fa-trash-alt\"></i>\n                        </a>\n                    </div>");
            }
            return new \WHMCS\Http\Message\JsonResponse(array("data" => $tableData));
        } catch (\Exception $e) {
            return new \WHMCS\Http\Message\JsonResponse(array("data" => "Notification rules table is missing or corrupted."), 500);
        }
    }
    public function manageRule(\WHMCS\Http\Message\ServerRequest $request)
    {
        $ruleId = $request->get("rule_id");
        if ($ruleId) {
            $title = \AdminLang::trans("notifications.editRule");
            $rule = \WHMCS\Notification\Rule::find($ruleId);
        } else {
            $title = \AdminLang::trans("notifications.createNewRule");
            $rule = new \WHMCS\Notification\Rule();
            $rule->event_type = "Ticket";
            $rule->events = "opened";
            $rule->provider = "Hipchat";
        }
        return $this->manageRuleOutput($request, $title, $rule);
    }
    public function duplicateRule(\WHMCS\Http\Message\ServerRequest $request)
    {
        $ruleId = $request->get("rule_id");
        $rule = \WHMCS\Notification\Rule::find($ruleId);
        $rule->id = "";
        $rule->description .= " (" . \AdminLang::trans("global.copy") . ")";
        $title = \AdminLang::trans("notifications.duplicateRule");
        return $this->manageRuleOutput($request, $title, $rule);
    }
    public function deleteRule(\WHMCS\Http\Message\ServerRequest $request)
    {
        $ruleId = $request->get("rule_id");
        $rule = \WHMCS\Notification\Rule::find($ruleId);
        $rule->delete();
        return new \WHMCS\Http\Message\JsonResponse(array("success" => true));
    }
    protected function manageRuleOutput(\WHMCS\Http\Message\ServerRequest $request, $title, $rule)
    {
        $rule->events = explode(",", $rule->events);
        $eventTypes = $events = $conditions = array();
        foreach (\WHMCS\Notification\Events::all() as $event) {
            $eventType = getClassName($event);
            $eventTypes[$eventType] = $event::DISPLAY_NAME;
            $events[$eventType] = array();
            foreach ($event->getEvents() as $eventName => $eventOptions) {
                $events[$eventType][$eventName] = $eventOptions["label"];
            }
            $eventConditions = array();
            foreach ($event->getConditions() as $name => $values) {
                $eventConditions[$values["FriendlyName"]] = $this->buildConditionField($eventType, $name, $values, $rule->conditions);
            }
            $conditions[$eventType] = $eventConditions;
        }
        $providers = \WHMCS\Notification\Provider::active()->get();
        foreach ($providers as $key => $provider) {
            try {
                $provider->initObject();
            } catch (\WHMCS\Exception $e) {
                unset($providers[$key]);
            }
        }
        $output = view("notifications.rule", array("rule" => $rule, "eventTypes" => $eventTypes, "events" => $events, "conditions" => $conditions, "providers" => $providers));
        return new \WHMCS\Http\Message\JsonResponse(array("title" => $title, "body" => $output));
    }
    protected function buildConditionField($eventType, $name, $values, $ruleConditions)
    {
        $condition = "";
        if ($values["Type"] == "text") {
            $condition = "\n                <select class=\"form-control\" name=\"conditions[" . $eventType . "][" . $name . "_filter]\">\n                <option value=\"\">- Any -</option>\n                <option value=\"contains\"" . ($ruleConditions[$name . "_filter"] == "contains" ? " selected" : "") . ">Contains</option>\n                <option value=\"exact\"" . ($ruleConditions[$name . "_filter"] == "exact" ? " selected" : "") . ">Exact Match</option>\n                </select>\n                <input type=\"text\" name=\"conditions[" . $eventType . "][" . $name . "]\" class=\"form-control\" value=\"" . $ruleConditions[$name] . "\">";
        } else {
            if ($values["Type"] == "range") {
                $condition = "\n                <select class=\"form-control\" name=\"conditions[" . $eventType . "][" . $name . "_filter]\">\n                <option value=\"\">- Any -</option>\n                <option value=\"greater\"" . ($ruleConditions[$name . "_filter"] == "greater" ? " selected" : "") . ">Greater Than</option>\n                <option value=\"less\"" . ($ruleConditions[$name . "_filter"] == "less" ? " selected" : "") . ">Less Than</option>\n                </select>\n                <input type=\"text\" name=\"conditions[" . $eventType . "][" . $name . "]\" class=\"form-control\" value=\"" . $ruleConditions[$name] . "\">";
            } else {
                if ($values["Type"] == "yesno") {
                    $condition = " <input type=\"checkbox\" name=\"conditions[" . $eventType . "][" . $name . "]\" value=\"1\">";
                } else {
                    if ($values["Type"] == "dropdown") {
                        $condition = " <select  name=\"conditions[" . $eventType . "][" . $name . "]\" class=\"form-control\"><option value=\"\">- Any -</option>";
                        $options = $values["Options"];
                        if (is_callable($options)) {
                            $options = $options();
                        }
                        foreach ($options as $k => $v) {
                            $condition .= "<option value=\"" . $k . "\"" . ($ruleConditions[$name] == $k ? " selected" : "") . ">" . $v . "</option>";
                        }
                        $condition .= "</select>";
                    }
                }
            }
        }
        return $condition;
    }
    public function saveRule(\WHMCS\Http\Message\ServerRequest $request)
    {
        $ruleId = (int) $request->get("rule_id");
        $rule = \WHMCS\Notification\Rule::findOrNew($ruleId);
        if (!$rule->exists) {
            $rule->active = true;
            $rule->can_delete = true;
        }
        $description = $request->get("description");
        $eventType = $request->get("eventtype");
        $events = $request->get("events");
        $conditions = $request->get("conditions");
        $provider = $request->get("notificationProvider");
        $provider_config = $request->get("provider_config");
        if (!trim($description)) {
            return new \WHMCS\Http\Message\JsonResponse(array("errorMsgTitle" => "", "errorMsg" => \AdminLang::trans("notifications.validationNameRequired")));
        }
        $eventsList = array();
        foreach (\WHMCS\Notification\Events::all() as $event) {
            $eventsList[getClassName($event)] = $event->getEvents();
        }
        if (!array_key_exists($eventType, $eventsList)) {
            return new \WHMCS\Http\Message\JsonResponse(array("errorMsgTitle" => "", "errorMsg" => \AdminLang::trans("notifications.validationEventTypeRequired")));
        }
        $events = $events[$eventType];
        foreach ($events as $k => $v) {
            if (!array_key_exists($v, $eventsList[$eventType])) {
                unset($events[$k]);
            }
        }
        if (count($events) < 1) {
            return new \WHMCS\Http\Message\JsonResponse(array("errorMsgTitle" => "", "errorMsg" => \AdminLang::trans("notifications.validationEventRequired")));
        }
        $provider = \WHMCS\Notification\Provider::active()->where("name", $provider)->first();
        if (is_null($provider)) {
            return new \WHMCS\Http\Message\JsonResponse(array("errorMsgTitle" => "", "errorMsg" => \AdminLang::trans("notifications.validationProviderRequired")));
        }
        $providerName = $provider->name;
        $providerConfig = isset($provider_config[$providerName]) && is_array($provider_config[$providerName]) ? $provider_config[$providerName] : array();
        foreach ($provider->initObject()->notificationSettings() as $field => $values) {
            if (isset($values["Required"]) && $values["Required"] && !$providerConfig[$field]) {
                return new \WHMCS\Http\Message\JsonResponse(array("errorMsgTitle" => "", "errorMsg" => \AdminLang::trans("notifications.validationProviderFieldRequired") . " " . $values["FriendlyName"]));
            }
        }
        $rule->description = $description;
        $rule->event_type = $eventType;
        $rule->events = implode(",", $events);
        $rule->conditions = $conditions[$eventType];
        $rule->provider = $provider->name;
        $rule->provider_config = $providerConfig;
        $rule->save();
        \WHMCS\Notification\Rule::rebuildCache();
        return new \WHMCS\Http\Message\JsonResponse(array("successMsgTitle" => "", "successMsg" => $ruleId ? \AdminLang::trans("notifications.ruleUpdatedSuccessfully") : \AdminLang::trans("notifications.ruleCreatedSuccessfully"), "dismiss" => true));
    }
    public function setRuleStatus(\WHMCS\Http\Message\ServerRequest $request)
    {
        $id = $request->get("id");
        $rule = \WHMCS\Notification\Rule::find($id);
        $rule->active = $request->get("state") == "true";
        $rule->save();
        return new \WHMCS\Http\Message\JsonResponse(array("success" => true));
    }
    public function manageProvider(\WHMCS\Http\Message\ServerRequest $request, $errorMsg = NULL)
    {
        $requestProvider = $request->get("provider");
        $notificationsInterface = new \WHMCS\Module\Notification();
        if (!$notificationsInterface->load($requestProvider)) {
            throw new \WHMCS\Exception("Invalid provider");
        }
        $className = $notificationsInterface->getClassPath();
        $notificationProvider = new $className();
        require_once ROOTDIR . "/includes/modulefunctions.php";
        $settings = $request->get("settings");
        if (is_null($settings)) {
            $provider = \WHMCS\Notification\Provider::where("name", "=", $requestProvider)->first();
            $settings = is_null($provider) ? array() : $provider->settings;
        }
        $output = view("notifications.manage-provider", array("provider" => $notificationProvider, "settings" => $settings, "errorMsg" => $errorMsg));
        return new \WHMCS\Http\Message\JsonResponse(array("body" => $output));
    }
    public function saveProvider(\WHMCS\Http\Message\ServerRequest $request)
    {
        $requestProvider = $request->get("provider");
        $provider = \WHMCS\Notification\Provider::firstOrNew(array("name" => $requestProvider));
        try {
            $provider->initObject()->testConnection($request->get("settings"));
        } catch (\Exception $e) {
            return $this->manageProvider($request, $e->getMessage());
        }
        $provider = \WHMCS\Notification\Provider::firstOrNew(array("name" => $requestProvider));
        $provider->settings = $request->get("settings", "");
        $provider->active = true;
        $provider->save();
        return new \WHMCS\Http\Message\JsonResponse(array("successMsgTitle" => "", "successMsg" => \AdminLang::trans("notifications.settingsUpdatedConfirmation"), "dismiss" => true));
    }
    public function getDynamicField(\WHMCS\Http\Message\ServerRequest $request)
    {
        $requestProvider = $request->get("provider");
        $requestFieldName = $request->get("field");
        $provider = \WHMCS\Notification\Provider::firstOrNew(array("name" => $requestProvider));
        if (is_null($provider)) {
            return new \WHMCS\Http\Message\JsonResponse(array("error" => "Invalid Provider Name"));
        }
        try {
            $response = $provider->initObject()->getDynamicField($requestFieldName, $provider->settings);
            if (!is_array($response)) {
                $response = array();
            }
            return new \WHMCS\Http\Message\JsonResponse($response);
        } catch (\WHMCS\Exception $e) {
            return new \WHMCS\Http\Message\JsonResponse(array("error" => $e->getMessage()));
        }
    }
    public function getProvidersStatus()
    {
        $providers = array();
        $notificationsInterface = new \WHMCS\Module\Notification();
        foreach ($notificationsInterface->getList() as $module) {
            if ($notificationsInterface->load($module)) {
                $className = $notificationsInterface->getClassPath();
                $provider = new $className();
                if ($provider instanceof \WHMCS\Module\Contracts\NotificationModuleInterface) {
                    $providers[$module] = 0;
                }
            }
        }
        $configuredProviders = \WHMCS\Notification\Provider::pluck("active", "name");
        foreach ($configuredProviders as $provider => $active) {
            $providers[$provider] = $active;
        }
        return new \WHMCS\Http\Message\JsonResponse(array("providers" => $providers, "success" => true));
    }
    public function disableProvider(\WHMCS\Http\Message\ServerRequest $request)
    {
        $requestProvider = $request->get("provider");
        $provider = \WHMCS\Notification\Provider::firstOrNew(array("name" => $requestProvider));
        $provider->active = false;
        $provider->save();
        return new \WHMCS\Http\Message\JsonResponse(array("successMsgTitle" => "", "successMsg" => \AdminLang::trans("notifications.providerDisabledConfirmation")));
    }
}

?>