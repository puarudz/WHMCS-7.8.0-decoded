<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module;

abstract class AbstractWidget
{
    protected $title = NULL;
    protected $description = NULL;
    protected $columns = 1;
    protected $weight = 100;
    protected $wrapper = true;
    protected $cache = false;
    protected $cachePerUser = false;
    protected $cacheExpiry = 3600;
    protected $requiredPermission = "";
    protected $draggable = true;
    protected $adminUser = NULL;
    public function getId()
    {
        return str_replace("WHMCS\\Module\\Widget\\", "", get_class($this));
    }
    public function getTitle()
    {
        return $this->title;
    }
    public function getDescription()
    {
        return $this->description;
    }
    public function getColumnSize()
    {
        return (int) $this->columns;
    }
    public function getWeight()
    {
        $weight = $this->weight;
        $widgetId = $this->getId();
        if (is_null($this->adminUser)) {
            $this->adminUser = \WHMCS\User\Admin::find((int) \WHMCS\Session::get("adminid"));
        }
        if ($this->adminUser && $this->adminUser->widgetOrder && in_array($widgetId, $this->adminUser->widgetOrder)) {
            $weight = array_search($widgetId, $this->adminUser->widgetOrder);
        }
        return (int) $weight;
    }
    public function showWrapper()
    {
        return (bool) $this->wrapper;
    }
    public function isCachable()
    {
        return (bool) $this->cache;
    }
    public function isCachedPerUser()
    {
        return (bool) $this->cachePerUser;
    }
    public function getCacheExpiry()
    {
        return (int) $this->cacheExpiry;
    }
    public function getRequiredPermission()
    {
        return $this->requiredPermission;
    }
    public abstract function getData();
    public abstract function generateOutput($data);
    protected function fetchData($forceRefresh = false)
    {
        $storage = new \WHMCS\TransientData();
        $storageName = "widget." . $this->getId();
        if ($this->isCachedPerUser()) {
            $storageName .= ":" . \WHMCS\Session::get("adminid");
        }
        if ($this->isCachable() && !$forceRefresh) {
            $data = $storage->retrieve($storageName);
            if (!is_null($data)) {
                $decoded = json_decode($data, true);
                if (is_array($decoded) && count($decoded)) {
                    return $decoded;
                }
            }
        }
        $data = $this->getData();
        $data = $this->sanitizeData($data);
        if ($this->isCachable()) {
            $storage->store($storageName, json_encode($data), $this->getCacheExpiry());
        }
        return $data;
    }
    public function sanitizeData($data)
    {
        if ($this instanceof Widget\Activity && !empty($data["activity"]["entry"]) && is_array($data["activity"]["entry"])) {
            foreach ($data["activity"]["entry"] as $key => $entry) {
                if (isset($entry["description"])) {
                    $data["activity"]["entry"][$key]["description"] = \WHMCS\Input\Sanitize::makeSafeForOutput($data["activity"]["entry"][$key]["description"]);
                }
            }
        }
        return $data;
    }
    public function render($forceRefresh = false)
    {
        $data = $this->fetchData($forceRefresh);
        $response = $this->generateOutput($data);
        if (is_array($response)) {
            return json_encode($response);
        }
        return $response;
    }
    public function isDraggable()
    {
        return (bool) $this->draggable;
    }
}

?>