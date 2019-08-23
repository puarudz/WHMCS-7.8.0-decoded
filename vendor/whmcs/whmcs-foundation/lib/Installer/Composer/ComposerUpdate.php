<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Installer\Composer;

class ComposerUpdate
{
    private $composer = NULL;
    private $updateLog = "";
    private $composerJsonFilePath = "";
    private $coreStabilityTier = "";
    private $coreVersion = "";
    private $allowedCoreStabilityTiers = array();
    private $firstUpdatableReleaseDate = "";
    private $latestVersion = NULL;
    private $packageMetadata = array();
    private $skipLicenseCheck = false;
    const REPOSITORY_URL_TESTING = "https://releases.dev.whmcs.com/v2/";
    const REPOSITORY_URL_PRODUCTION = "https://releases.whmcs.com/v2/";
    const REPO_DATE_STAMPED_MANIFESTS_PATH = "daily/";
    const FIRST_UPDATABLE_RELEASE_DATE = "2016-07-19";
    public function __construct($updateTempPath)
    {
        $this->setComposerWrapper(new ComposerWrapper($updateTempPath));
        if (!$this->composer->isEnvironmentValid()) {
            throw new ComposerUpdateException(implode("\n", $this->composer->getEnvironmentErrors()));
        }
        $allowedCoreStabilityTiers = self::getDefaultAllowedCoreStabilityTiers();
        $this->setAllowedCoreStabilityTiers($allowedCoreStabilityTiers)->setCoreStabilityTier(self::isUsingInternalUpdateResources() ? ComposerJson::STABILITY_ALPHA : ComposerJson::STABILITY_STABLE)->setFirstUpdatableReleaseDate(self::FIRST_UPDATABLE_RELEASE_DATE);
    }
    public static function isDryRunOnlyUpdate()
    {
        $config = \DI::make("config");
        return $config->update_dry_run_only ? true : false;
    }
    public static function isUsingInternalUpdateResources()
    {
        $config = \DI::make("config");
        return $config->use_internal_update_resources ? true : false;
    }
    public static function getDefaultAllowedCoreStabilityTiers()
    {
        $tiers = array(ComposerJson::STABILITY_STABLE, ComposerJson::STABILITY_RC, ComposerJson::STABILITY_BETA);
        if (ComposerUpdate::isUsingInternalUpdateResources()) {
            $tiers[] = ComposerJson::STABILITY_ALPHA;
        }
        return $tiers;
    }
    public static function getRepositoryUrl()
    {
        return self::isUsingInternalUpdateResources() ? self::REPOSITORY_URL_TESTING : self::REPOSITORY_URL_PRODUCTION;
    }
    public function getFirstUpdatableReleaseDate()
    {
        return $this->firstUpdatableReleaseDate;
    }
    public function setFirstUpdatableReleaseDate($value)
    {
        $this->firstUpdatableReleaseDate = $value;
        return $this;
    }
    public function getAllowedCoreStabilityTiers()
    {
        return $this->allowedCoreStabilityTiers;
    }
    public function setAllowedCoreStabilityTiers(array $value)
    {
        $this->allowedCoreStabilityTiers = $value;
        return $this;
    }
    public function getCoreStabilityTier()
    {
        return $this->coreStabilityTier;
    }
    public function setCoreStabilityTier($value)
    {
        if (!in_array($value, $this->getAllowedCoreStabilityTiers())) {
            throw new ComposerUpdateException("Unsupported core stability tier: " . $value);
        }
        $this->coreStabilityTier = $value;
        $this->coreVersion = "";
        return $this;
    }
    public function getCoreVersion()
    {
        return $this->coreVersion;
    }
    protected function isValidCoreVersion($value)
    {
        return preg_match("/^\\d+\\.\\d+\$/", $value) ? true : false;
    }
    public function setCoreVersion($value)
    {
        if (!$this->isValidCoreVersion($value)) {
            throw new ComposerUpdateException("Invalid format for core version");
        }
        $this->coreVersion = $value;
        $this->coreStabilityTier = "";
        return $this;
    }
    public function setSkipLicenseCheck($value)
    {
        if (!is_bool($value)) {
            return $this;
        }
        $this->skipLicenseCheck = $value;
        return $this;
    }
    public function getSkipLicenseCheck()
    {
        return $this->skipLicenseCheck;
    }
    protected function setComposerWrapper(ComposerWrapper $composer)
    {
        $this->composer = $composer;
    }
    public function getInstalledVersion()
    {
        return new \WHMCS\Version\SemanticVersion(\WHMCS\Application::FILES_VERSION);
    }
    public static function getAllVersionsRepositoryUrl()
    {
        return rtrim(self::getRepositoryUrl(), "/") . "/";
    }
    protected function getRepositoryUrlForDate($updatesExpiryDate)
    {
        $dateObject = $updatesExpiryDate instanceof \WHMCS\Carbon ? $updatesExpiryDate : \WHMCS\Carbon::createFromFormat("Y-m-d", $updatesExpiryDate);
        return self::getRepositoryUrl() . self::REPO_DATE_STAMPED_MANIFESTS_PATH . $dateObject->format("Y/m/d/");
    }
    protected function getRepositoryUrlForLicense(\WHMCS\License $license)
    {
        if (!$license->getRequiresUpdates() || $this->skipLicenseCheck) {
            return self::getAllVersionsRepositoryUrl();
        }
        $updatesExpiryDate = null;
        try {
            $expirationDateString = $license->getUpdatesExpirationDate();
            if ($expirationDateString !== "") {
                $updatesExpiryDate = \WHMCS\Carbon::createFromFormat("Y-m-d", $expirationDateString);
            }
        } catch (\Exception $e) {
        }
        if (is_object($updatesExpiryDate) && $updatesExpiryDate < \WHMCS\Carbon::today() && \WHMCS\Carbon::createFromFormat("Y-m-d", $this->getFirstUpdatableReleaseDate()) <= $updatesExpiryDate) {
            return $this->getRepositoryUrlForDate($updatesExpiryDate);
        }
        return self::getAllVersionsRepositoryUrl();
    }
    public function getRepositoryUrlForCurrentInstall()
    {
        return $this->getRepositoryUrlForLicense(\DI::make("app")->getLicense());
    }
    public function pinUpdateChannel($versionOrTier)
    {
        if ($this->isValidCoreVersion($versionOrTier)) {
            $this->setCoreVersion($versionOrTier);
        } else {
            $this->setCoreStabilityTier($versionOrTier);
        }
        return $this;
    }
    protected function getComposerConfig()
    {
        $jsonBuilder = new ComposerJson();
        $jsonBuilder->addRepository($this->getRepositoryUrlForCurrentInstall(), WhmcsRepository::REPOSITORY_TYPE)->disablePackagist();
        if ($this->coreVersion) {
            $jsonBuilder->setMinimumStability(ComposerJson::STABILITY_STABLE)->addRequirementWithVersion(ComposerWrapper::PACKAGE_NAME, $jsonBuilder->getVersionRequirementFromVersionPin($this->coreVersion));
        } else {
            $stability = $this->getCoreStabilityTier();
            $jsonBuilder->setMinimumStability($stability)->addRequirementWithStability(ComposerWrapper::PACKAGE_NAME, $stability);
        }
        return $jsonBuilder->buildArray();
    }
    protected function deleteComposerJsonFile()
    {
        if (!empty($this->composerJsonFilePath) && file_exists($this->composerJsonFilePath)) {
            @unlink($this->composerJsonFilePath);
            $this->composerJsonFilePath = "";
        }
    }
    protected function initComposerEnvironment()
    {
        $this->composer->setConfig($this->getComposerConfig());
    }
    protected function cleanupComposerEnvironment()
    {
    }
    protected function doComposerCommand(\Closure $command)
    {
        $this->initComposerEnvironment();
        $commandResult = $command();
        $this->cleanupComposerEnvironment();
        return $commandResult;
    }
    protected function addToUpdateLog($message)
    {
        $this->updateLog .= $message . "\n";
    }
    protected function validateUpdateResults()
    {
        $installDirPath = ROOTDIR . DIRECTORY_SEPARATOR . "install";
        if (!is_dir($installDirPath)) {
            $this->addToUpdateLog("Failed to locate ./install directory. Please verify that WHMCS core files were properly relocated.");
            return false;
        }
        return true;
    }
    public function update()
    {
        $this->updateLog = "";
        $commandResult = $this->doComposerCommand(function () {
            if (self::isDryRunOnlyUpdate()) {
                $this->composer->setDryRun(true);
            }
            return $this->composer->update()->getCommandSuccess() ? true : false;
        });
        $this->updateLog = $this->composer->getCommandOutput();
        $this->packageMetadata = $this->composer->getLastRunPackageMetadata();
        if (true === $commandResult) {
            $commandResult = $this->validateUpdateResults();
        }
        return $commandResult;
    }
    public function getLatestVersion($forceUpdate = false)
    {
        if ($this->latestVersion && !$forceUpdate) {
            return $this->latestVersion;
        }
        $latestVersion = $this->doComposerCommand(function () {
            return $this->composer->getLatestVersion();
        });
        if ($this->composer->getCommandSuccess()) {
            $this->latestVersion = new \WHMCS\Version\SemanticVersion($latestVersion);
            return $this->latestVersion;
        }
        $this->latestVersion = null;
        throw new ComposerUpdateException("Failed to retrieve latest version: " . $this->composer->getCommandOutput());
    }
    public function canUpdate(\WHMCS\Version\SemanticVersion $latestVersion)
    {
        return \WHMCS\Version\SemanticVersion::compare($this->getInstalledVersion(), $latestVersion, "<");
    }
    public function getUpdateLog()
    {
        return $this->updateLog;
    }
    protected function getMetadataByPackage($packageName)
    {
        if (isset($this->packageMetadata[$packageName])) {
            return $this->packageMetadata[$packageName];
        }
        return array();
    }
    public function getReleaseMetaData()
    {
        return $this->getMetadataByPackage(ComposerWrapper::PACKAGE_NAME);
    }
}

?>