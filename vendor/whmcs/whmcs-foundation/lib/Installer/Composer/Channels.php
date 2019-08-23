<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Installer\Composer;

class Channels
{
    private $minStabilityLevels = NULL;
    private $allVersionsToPin = NULL;
    private $currentMinorVersion = NULL;
    private $repositoryURL = NULL;
    private $packagesJson = NULL;
    private $ltsJson = NULL;
    public function __construct($repository = NULL, $minStabilityLevels = array())
    {
        if (is_null($repository)) {
            $this->setRepositoryURL(ComposerUpdate::getAllVersionsRepositoryUrl());
        }
        if (empty($minStabilityLevels)) {
            $this->setMinStabilityLevels(ComposerUpdate::getDefaultAllowedCoreStabilityTiers());
        }
    }
    public function setMinStabilityLevels($toSet)
    {
        if (is_array($toSet)) {
            $this->minStabilityLevels = $toSet;
            return $this;
        }
        throw new \WHMCS\Exception("MinStabilityLevels must be an array.");
    }
    public function getMinStabilityLevels()
    {
        return $this->minStabilityLevels;
    }
    public function setAllVersionsToPin($toSet)
    {
        if (is_array($toSet)) {
            $this->allVersionsToPin = $toSet;
            return $this;
        }
        throw new \WHMCS\Exception("VersionsToPin must be an array.");
    }
    public function getAllVersionsToPin()
    {
        return $this->allVersionsToPin;
    }
    public function setRepositoryURL($url)
    {
        $this->repositoryURL = rtrim($url, "/") . "/";
        return $this;
    }
    public function getRepositoryURL()
    {
        return $this->repositoryURL;
    }
    public function setPackagesJson($jsonBody)
    {
        $this->packagesJson = $jsonBody;
        return $this;
    }
    public function getPackagesJson()
    {
        return $this->packagesJson;
    }
    public function setLtsJson($jsonBody)
    {
        $this->ltsJson = $jsonBody;
        return $this;
    }
    public function getLtsJson()
    {
        return $this->ltsJson;
    }
    public function setCurrentVersion(\WHMCS\Version\SemanticVersion $version)
    {
        $this->currentMinorVersion = $version->getMajor() . "." . $version->getMinor();
        return $this;
    }
    public function fetchRemoteVersionsAvailable()
    {
        $this->downloadRemoteLtsJson();
        $this->filterRemoteVersions();
        return $this;
    }
    public function downloadRemoteLtsJson()
    {
        $guzzle = new \GuzzleHttp\Client(array("http_errors" => false));
        $url = $this->getRepositoryURL() . "lts.json";
        try {
            $result = $guzzle->get($url);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            throw new ComposerUpdateException("Unable to download remote lts.json from url: " . $url . " Message " . $e->getMessage());
        }
        $this->setLtsJson($result->getBody());
        return $this;
    }
    public function filterRemoteVersions()
    {
        $availableVersions = array();
        $knownVersions = json_decode($this->getPackagesJson(), true);
        $knownVersions = $knownVersions["packages"][ComposerWrapper::PACKAGE_NAME];
        foreach ($knownVersions as $version => $value) {
            $semVer = new \WHMCS\Version\SemanticVersion($version);
            $minVer = $semVer->getMajor() . "." . $semVer->getMinor();
            if (!in_array($minVer, $availableVersions)) {
                $availableVersions[] = $minVer;
            }
        }
        $this->setAllVersionsToPin($availableVersions);
        return $this;
    }
    public function getVersionsToOffer()
    {
        $potentialVersions = array();
        $supportedVersion = json_decode($this->getLtsJson(), true);
        $supportedVersion = array_merge($supportedVersion["Active"], $supportedVersion["LTS"]);
        foreach ($this->allVersionsToPin as $version) {
            if (version_compare($version, $this->currentMinorVersion, ">=") && in_array($version, $supportedVersion)) {
                $potentialVersions[] = $version;
            }
        }
        return $potentialVersions;
    }
    public function getSubscribeOptions()
    {
        return array_merge($this->getMinStabilityLevels(), $this->getVersionsToOffer());
    }
    protected function getActiveAndLTSVersions()
    {
        $ltsInfo = json_decode($this->getLtsJson(), true);
        return array_merge($ltsInfo["Active"], $ltsInfo["LTS"]);
    }
    public function isPinOutOfLTS($pinnedSetting = NULL)
    {
        if (is_null($pinnedSetting)) {
            $pinnedSetting = \WHMCS\Config\Setting::getValue("WHMCSUpdatePinVersion");
        }
        if (in_array($pinnedSetting, $this->getMinStabilityLevels())) {
            return false;
        }
        if (!isset($this->ltsJson) || $this->ltsJson == "") {
            $this->downloadRemoteLtsJson();
        }
        if (in_array($pinnedSetting, $this->getActiveAndLTSVersions())) {
            return false;
        }
        return true;
    }
}

?>