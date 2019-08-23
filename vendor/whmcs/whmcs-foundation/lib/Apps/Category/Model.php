<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Apps\Category;

class Model
{
    protected $data = NULL;
    public function __construct($data)
    {
        $this->data = $data;
    }
    public function getSlug()
    {
        return $this->data["slug"];
    }
    public function getDisplayName()
    {
        return $this->data["name"];
    }
    public function getTagline()
    {
        return $this->data["tagline"];
    }
    public function getModuleType()
    {
        return $this->data["moduleType"];
    }
    public function getFeatured()
    {
        return $this->data["featured"];
    }
    public function getExclusions()
    {
        return $this->data["exclusions"];
    }
    public function getAdditions()
    {
        return $this->data["additions"];
    }
    public function getIcon()
    {
        return isset($this->data["icon"]) ? $this->data["icon"] : "fa fa-star";
    }
    public function includeInHomeFeatured()
    {
        return isset($this->data["includeInHomeFeatured"]) && $this->data["includeInHomeFeatured"];
    }
    public function getHomeFeaturedNumApps()
    {
        return isset($this->data["homeNumFeaturedApps"]) && is_numeric($this->data["homeNumFeaturedApps"]) ? $this->data["homeNumFeaturedApps"] : 4;
    }
    public function getFeaturedAppKeys()
    {
        $featured = $this->getFeatured();
        $country = strtolower(\WHMCS\Config\Setting::getValue("DefaultCountry"));
        return array_key_exists($country, $featured) ? $featured[$country] : $featured["default"];
    }
    public function getFeaturedApps($apps)
    {
        $featuredApps = $this->getFeaturedAppKeys();
        $appsToReturn = array();
        foreach ($this->getFeaturedAppKeys() as $appKey) {
            $app = $apps->get($appKey);
            if ($app) {
                $appsToReturn[] = $app;
            }
        }
        return $appsToReturn;
    }
    public function getFeaturedAppsForHome($apps)
    {
        $featuredApps = $this->getFeaturedApps($apps);
        return array_slice($featuredApps, 0, $this->getHomeFeaturedNumApps());
    }
    protected function getAllApps($apps)
    {
        $appsToReturn = array();
        foreach ($apps->all() as $app) {
            if (($app->getModuleType() == $this->getModuleType() && !$app->getCategory() || in_array($app->getKey(), $this->getAdditions()) || $app->getCategory() == $this->getSlug()) && !in_array($app->getKey(), $this->getExclusions())) {
                $appsToReturn[] = $app;
            }
        }
        return $appsToReturn;
    }
    public function getNonFeaturedApps($apps)
    {
        $apps = $this->getAllApps($apps);
        $featuredAppKeys = $this->getFeaturedAppKeys();
        foreach ($apps as $key => $app) {
            if (in_array($app->getKey(), $featuredAppKeys)) {
                unset($apps[$key]);
            }
        }
        return $apps;
    }
}

?>