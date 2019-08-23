<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module;

class Widget extends AbstractModule
{
    protected $type = self::TYPE_WIDGET;
    protected $usesDirectories = false;
    protected $widgets = NULL;
    protected $hookName = "AdminHomeWidgets";
    public function loadWidgets()
    {
        $this->widgets = array();
        foreach ($this->getList() as $widgetName) {
            if ($widgetName == "index") {
                continue;
            }
            try {
                $widgetClass = "\\WHMCS\\Module\\Widget\\" . $widgetName;
                if (class_exists($widgetClass)) {
                    $widget = new $widgetClass();
                    if (!$widget->getRequiredPermission() || checkPermission($widget->getRequiredPermission(), true)) {
                        $this->widgets[] = new $widgetClass();
                    }
                }
            } catch (\Exception $e) {
                logActivity("An Error Occurred loading widget " . $widgetName . ": " . $e->getMessage());
            }
        }
        $this->loadWidgetsViaHooks();
        usort($this->widgets, function ($a, $b) {
            return $b->getWeight() < $a->getWeight();
        });
        return $this->widgets;
    }
    protected function initGlobalChartForLegacyWidgets()
    {
        global $chart;
        if (!$chart instanceof \WHMCS\Chart) {
            $chart = new \WHMCS\Chart();
        }
    }
    protected function loadWidgetsViaHooks()
    {
        $hooks = get_registered_hooks($this->hookName);
        if (count($hooks) == 0) {
            return NULL;
        }
        $allowedwidgets = get_query_val("tbladmins", "tbladminroles.widgets", array("tbladmins.id" => \WHMCS\Session::get("adminid")), "", "", "", "tbladminroles ON tbladminroles.id=tbladmins.roleid");
        $allowedwidgets = explode(",", $allowedwidgets);
        $hookjquerycode = "";
        $args = array("adminid" => \WHMCS\Session::get("adminid"), "loading" => "<img src=\"images/loading.gif\" align=\"absmiddle\" /> " . \AdminLang::trans("global.loading"));
        $results = array();
        foreach ($hooks as $hook) {
            $widgetname = substr($hook["hook_function"], 7);
            if (is_callable($hook["hook_function"]) && (!$widgetname || in_array($widgetname, $allowedwidgets))) {
                try {
                    $this->initGlobalChartForLegacyWidgets();
                    $response = call_user_func($hook["hook_function"], $args);
                    $widget = null;
                    if ($response instanceof AbstractWidget) {
                        $widget = $response;
                    } else {
                        if (is_array($response)) {
                            $widget = LegacyWidget::factory($response["title"], $response["content"], $response["jscode"], $response["jquerycode"]);
                        }
                    }
                    if ($widget && (!$widget->getRequiredPermission() || checkPermission($widget->getRequiredPermission(), true))) {
                        $this->widgets[] = $widget;
                    }
                } catch (\Exception $e) {
                    logActivity("An Error Occurred loading widget " . $widgetname . ": " . $e->getMessage());
                } catch (\Error $e) {
                    logActivity("An Error Occurred loading widget " . $widgetname . ": " . $e->getMessage());
                }
            }
        }
    }
    public function getAllWidgets()
    {
        if (is_null($this->widgets)) {
            $this->loadWidgets();
        }
        return $this->widgets;
    }
    public function getWidgetByName($widgetId)
    {
        if (is_null($this->widgets)) {
            $this->loadWidgets();
        }
        foreach ($this->widgets as $widget) {
            if ($widget->getId() == $widgetId) {
                return $widget;
            }
        }
        throw new \WHMCS\Exception("Invalid widget name.");
    }
}

?>