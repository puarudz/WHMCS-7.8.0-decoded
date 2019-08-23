<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Apps;

class Feed
{
    protected $cacheTimeout = 604800;
    protected $feed = array();
    public function __construct()
    {
        $this->feed = $this->loadFeed();
        return $this;
    }
    protected function loadFeed()
    {
        $feedData = $this->loadFromCache();
        if (is_null($feedData)) {
            $feedData = $this->fetchFromRemote();
            $feedData = json_decode($feedData, true);
            if (!is_null($feedData)) {
                \WHMCS\TransientData::getInstance()->chunkedStore("apps.feed", json_encode($feedData), $this->cacheTimeout);
            }
        }
        if (is_null($feedData)) {
            throw new \WHMCS\Exception\Http\ConnectionError("Unable to retrieve Apps data feed.");
        }
        return $feedData;
    }
    protected function loadFromCache()
    {
        $data = \WHMCS\TransientData::getInstance()->retrieveChunkedItem("apps.feed");
        if (!empty($data)) {
            return json_decode($data, true);
        }
        return null;
    }
    protected function fetchFromRemote()
    {
        return curlCall("https://appsfeed.whmcs.com/feed.json", "");
    }
    public function heros()
    {
        return $this->feed["heros"];
    }
    public function categories()
    {
        return $this->feed["categories"];
    }
    public function apps()
    {
        return $this->feed["apps"];
    }
    public function additionalApps()
    {
        return $this->feed["additional_apps"];
    }
}

?>