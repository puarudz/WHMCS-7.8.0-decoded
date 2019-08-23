<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Installer\Update;

class ReleaseMetadata
{
    protected $releaseNotesUrl = "";
    protected $changeLogUrl = "";
    protected $valid = false;
    protected $simulated = false;
    protected $repositoryUrl = "";
    protected $productVersion = "";
    public function __construct()
    {
        $this->clean()->setRepositoryUrl(\WHMCS\Installer\Composer\ComposerUpdate::getRepositoryUrl());
    }
    public function getReleaseNotesUrl()
    {
        return $this->releaseNotesUrl;
    }
    public function setReleaseNotesUrl($value)
    {
        $this->releaseNotesUrl = $value;
        return $this;
    }
    public function getChangeLogUrl()
    {
        return $this->changeLogUrl;
    }
    public function setChangeLogUrl($value)
    {
        $this->changeLogUrl = $value;
        return $this;
    }
    public function setRepositoryUrl($value)
    {
        $this->repositoryUrl = rtrim($value, "/") . "/";
        return $this;
    }
    public function isValid()
    {
        return $this->valid;
    }
    public function clean()
    {
        $this->releaseNotesUrl = "";
        $this->changeLogUrl = "";
        $this->productVersion = "";
        $this->valid = false;
        $this->simulated = false;
        return $this;
    }
    protected function loadFromCache()
    {
        $this->clean();
        $metadataString = \WHMCS\Config\Setting::getValue("ReleaseMetadata");
        $metadata = json_decode($metadataString, true);
        if (!$metadata) {
            return false;
        }
        if ($metadata["productVersion"] !== \App::getVersion()->getCanonical()) {
            return false;
        }
        $this->releaseNotesUrl = $metadata["releaseNotesUrl"];
        $this->changeLogUrl = $metadata["changeLogUrl"];
        $this->productVersion = $metadata["productVersion"];
        $this->valid = true;
        return true;
    }
    public function setFromPackageMetadata($productVersion, array $packageMetadata)
    {
        $this->clean();
        $this->productVersion = $productVersion;
        $this->releaseNotesUrl = isset($packageMetadata["releaseNotesUrl"]) ? $packageMetadata["releaseNotesUrl"] : "";
        $this->changeLogUrl = isset($packageMetadata["changeLogUrl"]) ? $packageMetadata["changeLogUrl"] : "";
        $this->valid = true;
        $this->simulated = false;
        return $this;
    }
    protected function loadFromRepository()
    {
        $this->clean();
        $packageDefinitionsUrl = $this->repositoryUrl . "packages.json";
        $guzzle = new \GuzzleHttp\Client(array("http_errors" => false));
        try {
            $result = $guzzle->get($packageDefinitionsUrl);
        } catch (\Exception $e) {
            return false;
        }
        $packageDefinitions = json_decode($result->getBody(), true);
        if (!$packageDefinitions) {
            return false;
        }
        foreach ($packageDefinitions["packages"][\WHMCS\Installer\Composer\Hooks\ComposerInstaller::PACKAGE_NAME] as $version => $versionData) {
            $installedCanonicalVersion = \App::getVersion()->getCanonical();
            if ($version === $installedCanonicalVersion) {
                $this->setFromPackageMetadata($version, $versionData["extra"]);
                break;
            }
        }
        return $this->valid;
    }
    protected function loadSimulatedData()
    {
        $this->clean();
        $installedVersion = \App::getVersion();
        $this->productVersion = $installedVersion->getCanonical();
        $this->releaseNotesUrl = sprintf("https://docs.whmcs.com/Version_%s.%s.%s_Release_Notes", $installedVersion->getMajor(), $installedVersion->getMinor(), $installedVersion->getPatch());
        $this->changeLogUrl = sprintf("https://docs.whmcs.com/Changelog:WHMCS_V%s.%s#Version_%s.%s.%s", $installedVersion->getMajor(), $installedVersion->getMinor(), $installedVersion->getMajor(), $installedVersion->getMinor(), $installedVersion->getPatch());
        $this->valid = true;
        $this->simulated = true;
        return true;
    }
    public function save()
    {
        if (!$this->valid || $this->simulated) {
            return false;
        }
        $metadata = array("productVersion" => $this->productVersion, "releaseNotesUrl" => $this->releaseNotesUrl, "changeLogUrl" => $this->changeLogUrl);
        \WHMCS\Config\Setting::setValue("ReleaseMetadata", json_encode($metadata));
        return true;
    }
    public function load($allowRepositoryFetch = true)
    {
        if ($this->loadFromCache()) {
            return true;
        }
        if ($allowRepositoryFetch && $this->loadFromRepository()) {
            $this->save();
            return true;
        }
        if ($this->loadSimulatedData()) {
            return true;
        }
        return false;
    }
}

?>