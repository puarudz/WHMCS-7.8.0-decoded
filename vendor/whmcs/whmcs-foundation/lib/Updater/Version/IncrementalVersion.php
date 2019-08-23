<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class IncrementalVersion
{
    protected $updateActions = array();
    protected $version = NULL;
    protected $runUpdateCodeBeforeDatabase = false;
    public static $versionIncrements = array("3.2.0", "3.2.1", "3.2.2", "3.2.3", "3.3.0", "3.4.0", "3.4.1", "3.5.0", "3.5.1", "3.6.0", "3.6.1", "3.6.2", "3.7.0", "3.7.1", "3.7.2", "3.8.0", "3.8.1", "3.8.2", "4.0.0", "4.0.1", "4.1.0", "4.1.1", "4.1.2", "4.2.0", "4.2.1", "4.3.0", "4.3.1", "4.4.0", "4.4.1", "4.4.2", "4.5.0", "4.5.1", "4.5.2", "5.0.0", "5.0.1", "5.0.2", "5.0.3", "5.1.0", "5.1.1", "5.1.2", "5.2.0", "5.2.1", "5.2.2", "5.2.3", "5.2.4", "5.2.5", "5.3.0", "5.3.1", "5.3.2", "5.3.3-rc.1", "5.3.3-rc.2", "5.3.3-release.1", "5.3.4-release.1", "5.3.5-release.1", "5.3.6-release.1", "5.3.7-release.1", "5.3.8-release.1", "5.3.9-release.1", "5.3.12-release.1", "6.0.0-alpha.1", "6.0.0-beta.1", "6.0.0-beta.2", "6.0.0-beta.3", "6.0.0-beta.4", "6.0.0-beta.5", "6.0.0-rc.1", "6.0.0-rc.2", "6.0.0-rc.3", "6.0.0-release.1", "6.0.1-release.1", "6.0.2-release.1", "6.1.0-alpha.1", "6.1.0-rc.1", "6.1.0-release.1", "6.1.1-release.1", "6.2.0-alpha.1", "6.2.0-rc.1", "6.2.0-release.1", "6.2.1-release.1", "6.2.2-release.1", "6.3.0-alpha.1", "6.3.0-rc.1", "6.3.0-release.1", "6.3.1-release.1", "7.0.0-alpha.1", "7.0.0-alpha.5", "7.0.0-beta.1", "7.0.0-beta.2", "7.0.0-rc.1", "7.0.0-release.1", "7.0.1-release.1", "7.1.0-alpha.1", "7.1.0-beta.1", "7.1.0-rc.1", "7.1.0-release.1", "7.1.1-release.1", "7.1.2-release.1", "7.2.0-alpha.1", "7.2.0-beta.1", "7.2.0-beta.2", "7.2.0-beta.3", "7.2.0-rc.1", "7.2.0-release.1", "7.2.1-release.1", "7.2.2-release.1", "7.2.3-release.1", "7.3.0-alpha.1", "7.3.0-beta.1", "7.3.0-rc.1", "7.3.0-release.1", "7.4.0-alpha.1", "7.4.0-beta.1", "7.4.0-rc.1", "7.4.0-release.1", "7.4.1-release.1", "7.4.2-release.1", "7.5.0-alpha.1", "7.5.0-beta.1", "7.5.0-rc.1", "7.5.0-release.1", "7.5.1-release.1", "7.5.1-release.2", "7.5.2-release.1", "7.5.3-release.1", "7.6.0-alpha.1", "7.6.0-beta.1", "7.6.0-rc.1", "7.6.0-release.1", "7.6.1-release.1", "7.6.2-release.1", "7.7.0-alpha.1", "7.7.0-beta.1", "7.7.0-rc.1", "7.7.0-release.1", "7.7.1-release.1", "7.8.0-alpha.1", "7.8.0-beta.1", "7.8.0-beta.2", "7.8.0-rc.1");
    protected $filesToRemove = array();
    public function __construct(\WHMCS\Version\SemanticVersion $version)
    {
        $this->setVersion($version);
    }
    public function getVersion()
    {
        return $this->version;
    }
    protected function setVersion(\WHMCS\Version\SemanticVersion $version)
    {
        if (!in_array($version->getCasual(), static::$versionIncrements) && !in_array($version->getCanonical(), static::$versionIncrements)) {
            throw new \WHMCS\Exception("Unknown version " . $version->getCanonical() . ".");
        }
        $this->version = $version;
        return $this;
    }
    public static function factory($version)
    {
        $semanticVersion = new \WHMCS\Version\SemanticVersion($version);
        $version = self::useCanonicalVersion($semanticVersion) ? $semanticVersion->getCanonical() : $semanticVersion->getCasual();
        $versionClassName = "WHMCS\\Updater\\Version" . "\\Version" . strtolower(preg_replace("/[^a-zA-Z0-9]/", "", $version));
        $className = class_exists($versionClassName) ? $versionClassName : "WHMCS\\Updater\\Version\\IncrementalVersion";
        return new $className(new \WHMCS\Version\SemanticVersion($version));
    }
    protected function generateDatabaseFileName()
    {
        $version = self::useCanonicalVersion($this->getVersion()) ? $this->getVersion()->getCanonical() : $this->getVersion()->getCasual();
        $version = strtolower(preg_replace("/[^a-zA-Z0-9]/", "", $version));
        return ROOTDIR . DIRECTORY_SEPARATOR . "resources" . DIRECTORY_SEPARATOR . "sql" . DIRECTORY_SEPARATOR . "upgrade" . $version . ".sql";
    }
    protected function importDatabaseChanges()
    {
        $importFile = $this->generateDatabaseFileName();
        if (file_exists($importFile)) {
            try {
                $dumper = new \WHMCS\Database\Dumper\Database(\DI::make("db"));
                $dumper->importFrom($this->generateDatabaseFileName());
            } catch (\WHMCS\Exception $e) {
                throw new \WHMCS\Exception("Unable to import the " . $this->getVersion()->getCasual() . " database file. " . $e->getMessage());
            }
        }
        return $this;
    }
    protected function runUpdateCode()
    {
        foreach ($this->updateActions as $action) {
            \Log::info("Performing Update Action: " . $action);
            $this->{$action}();
        }
        return $this;
    }
    protected static function useCanonicalVersion(\WHMCS\Version\SemanticVersion $version)
    {
        $version533 = new \WHMCS\Version\SemanticVersion("5.3.3-rc.1");
        return !\WHMCS\Version\SemanticVersion::compare($version, $version533, "<");
    }
    protected function applyDatabaseVersion()
    {
        $version = self::useCanonicalVersion($this->getVersion()) ? $this->getVersion()->getCanonical() : $this->getVersion()->getCasual();
        try {
            $notSafeForModelSchema = \WHMCS\Version\SemanticVersion::compare($this->getVersion(), new \WHMCS\Version\SemanticVersion("6.0.0-alpha.1"), "<");
            if ($notSafeForModelSchema) {
                global $CONFIG;
                $CONFIG["Version"] = $version;
                $existingVersions = \Illuminate\Database\Capsule\Manager::table("tblconfiguration")->where("setting", "Version");
                if (0 < $existingVersions->count()) {
                    $existingVersions->delete();
                }
                \Illuminate\Database\Capsule\Manager::table("tblconfiguration")->insert(array("setting" => "Version", "value" => $version));
            } else {
                \WHMCS\Config\Setting::setValue("Version", $version);
            }
        } catch (\WHMCS\Exception $e) {
            throw new \WHMCS\Exception("Unable to apply database version " . $version . ".");
        }
        return $this;
    }
    protected function removeFiles()
    {
        \Log::info("Removing any obsolete file and directories");
        foreach ($this->filesToRemove as $glob) {
            $files = @glob($glob, GLOB_BRACE);
            if (!is_array($files)) {
                continue;
            }
            foreach ($files as $file) {
                try {
                    if (!is_writable($file)) {
                        throw new \WHMCS\Exception\File\NotDeleted("Permissions prevent removal of " . $file . ".");
                    }
                    if (is_dir($file)) {
                        \WHMCS\Utility\File::recursiveDelete($file, array(), true);
                        \Log::info("Recursively removed directory " . $file);
                    } else {
                        if (file_exists($file) && !@unlink($file)) {
                            throw new \WHMCS\Exception\File\NotDeleted("Unable to remove " . $file . ".");
                        }
                        \Log::info("Removed file " . $file);
                    }
                } catch (\Exception $e) {
                    \Log::warning(sprintf("Error removing %s %s in incremental update %s: %s", is_dir($file) ? "directory" : "file", $file, $this->getVersion()->getCanonical(), $e->getMessage()), array("incrementalVersion" => $this->getVersion()->getCanonical(), "trace" => $e->getTraceAsString()));
                }
            }
        }
        return $this;
    }
    public function applyUpdate()
    {
        \Log::info("Applying Updates for " . $this->getVersion()->getCanonical());
        if ($this->runUpdateCodeBeforeDatabase) {
            $this->runUpdateCode()->importDatabaseChanges();
        } else {
            $this->importDatabaseChanges()->runUpdateCode();
        }
        $this->applyDatabaseVersion()->removeFiles();
        return $this;
    }
    public function getFeatureHighlights()
    {
        return array();
    }
    public function addDomainsToCategories($rawTopLevelDomainsAndCategories)
    {
        $topLevelDomainsAndCategories = json_decode($rawTopLevelDomainsAndCategories, true);
        foreach ($topLevelDomainsAndCategories as $topLevelDomain => $categories) {
            $tld = \WHMCS\Domain\TopLevel::where("tld", "=", $topLevelDomain)->first();
            if (!$tld) {
                $tld = new \WHMCS\Domain\TopLevel();
                $tld->tld = $topLevelDomain;
                $tld->save();
            }
            $categoryIds = array();
            foreach ($categories as $category) {
                $catId = \WHMCS\Domain\TopLevel\Category::where("category", "=", $category)->value("id");
                $categoryIds[] = $catId;
            }
            $categoryIds = array_filter($categoryIds);
            if ($categoryIds) {
                $tld->categories()->sync($categoryIds, true);
            }
        }
        return $this;
    }
}

?>