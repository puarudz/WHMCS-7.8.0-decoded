<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Installer;

class Installer
{
    protected $installed = false;
    protected $version = NULL;
    protected $latestVersion = NULL;
    protected $database = NULL;
    protected $customAdminPath = "admin";
    protected $templatesCompiledDir = \WHMCS\Config\Application::DEFAULT_COMPILED_TEMPLATES_FOLDER;
    protected $installerDirectory = "";
    const DEFAULT_VERSION = "0.0.0";
    public function __construct(\WHMCS\Version\SemanticVersion $installedVersion, \WHMCS\Version\SemanticVersion $latestVersionAvailable)
    {
        $this->setVersion($installedVersion)->setLatestVersion($latestVersionAvailable)->checkIfInstalled();
    }
    public function setInstallerDirectory($dir)
    {
        if (!is_dir($dir)) {
            throw new \WHMCS\Exception\Installer(sprintf("\"%s\" is not a valid installer directory", $dir));
        }
        $this->installerDirectory = $dir;
    }
    public function getInstallerDirectory()
    {
        return $this->installerDirectory;
    }
    public function isInstalled()
    {
        return $this->installed;
    }
    public function getLatestMajorMinorVersion()
    {
        $latest = $this->getLatestVersion();
        return sprintf("%s.%s", $latest->getMajor(), $latest->getMinor());
    }
    public function getInstalledVersion()
    {
        return $this->getVersion()->getRelease();
    }
    public function getInstalledVersionNumeric()
    {
        $previous = $this->getVersion();
        return sprintf("%s%s%s", $previous->getMajor(), $previous->getMinor(), $previous->getPatch());
    }
    protected function shouldRunUpgrade(\WHMCS\Version\SemanticVersion $versionOfInterest)
    {
        $previousInstalledVersion = $this->getVersionFromDatabase();
        return \WHMCS\Version\SemanticVersion::compare($versionOfInterest, $previousInstalledVersion, ">");
    }
    public function isUpToDate()
    {
        return !$this->shouldRunUpgrade($this->latestVersion);
    }
    public function checkIfInstalled($forceLoadConfig = false)
    {
        $db = null;
        if (!$forceLoadConfig) {
            try {
                $capsule = \WHMCS\Database\Capsule::getInstance();
                if ($capsule && ($connection = $capsule->connection())) {
                    $db = $connection->getPdo();
                }
            } catch (\Exception $e) {
            }
        }
        $applicationConfig = new \WHMCS\Config\Application();
        if (!$db && $applicationConfig->configFileExists(\WHMCS\Config\Application::WHMCS_DEFAULT_CONFIG_FILE)) {
            $db_host = $db_port = $db_username = $db_password = $db_name = $mysql_charset = $templates_compiledir = $customadminpath = "";
            include ROOTDIR . DIRECTORY_SEPARATOR . "configuration.php";
            if ($customadminpath) {
                $this->customAdminPath = $customadminpath;
            }
            if ($templates_compiledir) {
                $this->templatesCompiledDir = $templates_compiledir;
            }
            if (!$this->templatesCompiledDir || preg_match("/^" . \WHMCS\Config\Application::DEFAULT_COMPILED_TEMPLATES_FOLDER . "[\\\\\\/]*\$/", $this->templatesCompiledDir)) {
                $this->templatesCompiledDir = ROOTDIR . DIRECTORY_SEPARATOR . \WHMCS\Config\Application::DEFAULT_COMPILED_TEMPLATES_FOLDER;
            }
            $this->templatesCompiledDir = rtrim($this->templatesCompiledDir, DIRECTORY_SEPARATOR);
            if ($db_username && $db_name) {
                try {
                    $db = $this->factoryDatabase($db_host, $db_port, $db_username, $db_password, $db_name, $mysql_charset);
                    $this->setDatabase($db);
                } catch (\WHMCS\Exception $e) {
                }
            }
        }
        try {
            if ($db) {
                $previousVersion = $this->getVersionFromDatabase();
                if ($previousVersion instanceof \WHMCS\Version\SemanticVersion) {
                    $this->setVersion($previousVersion);
                }
                if (!\WHMCS\Version\SemanticVersion::compare(new \WHMCS\Version\SemanticVersion(self::DEFAULT_VERSION), $previousVersion, "==")) {
                    $this->installed = true;
                }
            }
        } catch (\Exception $e) {
        }
        return $this;
    }
    public function factoryDatabase($host = "127.0.0.1", $port = "", $username = "", $password = "", $dbName = "", $mysqlCharset = "")
    {
        $tmpConfig = new \WHMCS\Config\Application();
        $tmpConfig->setDatabaseCharset($mysqlCharset)->setDatabaseHost($host)->setDatabaseName($dbName)->setDatabaseUsername($username)->setDatabasePassword($password);
        if ($port) {
            $tmpConfig->setDatabasePort($port);
        }
        try {
            $db = new \WHMCS\Database($tmpConfig);
        } catch (\Exception $e) {
            $hostAndPort = $host;
            if ($port) {
                $hostAndPort .= ":" . $port;
            }
            throw new \WHMCS\Exception(sprintf("Could not connect to MySQL database \"%s\" at \"%s\" with user \"%s\"", $dbName, $hostAndPort, $username));
        }
        return $db;
    }
    protected function getVersionFromDatabase()
    {
        $versionToReturn = new \WHMCS\Version\SemanticVersion(self::DEFAULT_VERSION);
        try {
            $storedVersion = $this->fetchDatabaseConfigurationValue("Version");
            $previousVersion = $storedVersion ? $storedVersion : self::DEFAULT_VERSION;
            if ($previousVersion == "5.3.3") {
                $previousVersion .= "-rc.1";
            }
            $versionToReturn = new \WHMCS\Version\SemanticVersion($previousVersion);
        } catch (\WHMCS\Exception $e) {
        }
        return $versionToReturn;
    }
    public function getDatabase()
    {
        return $this->database;
    }
    public function setDatabase($db)
    {
        $this->database = $db;
        return $this;
    }
    protected function fetchDatabaseConfigurationValue($key = "Version")
    {
        if (!is_string($key)) {
            throw new \InvalidArgumentException("Configuration setting to retrieve must be a string");
        }
        $query = sprintf("SELECT value FROM tblconfiguration WHERE setting=\"%s\"", $key);
        if ($result = mysql_query($query)) {
            $data = mysql_fetch_array($result);
            if (isset($data["value"])) {
                return trim($data["value"]);
            }
            throw new \WHMCS\Exception(sprintf("Could not retrieve configuration value for \"%s\" . Invalid database schema", $key));
        }
        throw new \WHMCS\Exception("Could not query database");
    }
    protected function storeDatabaseConfigurationValue($value, $key = "Version")
    {
        if (!is_string($value)) {
            throw new \InvalidArgumentException("Configuration setting value to store must be a string");
        }
        if (!is_string($key)) {
            throw new \InvalidArgumentException("Configuration setting name to store must be a string");
        }
        $query = sprintf("UPDATE tblconfiguration SET value=\"%s\" WHERE setting=\"%s\"", $value, $key);
        mysql_query($query);
        return $this;
    }
    public function runUpgrades()
    {
        \DI::make("db");
        $versionOfInterest = "";
        try {
            foreach (\WHMCS\Updater\Version\IncrementalVersion::$versionIncrements as $version) {
                $currentVersion = new \WHMCS\Version\SemanticVersion(\WHMCS\Config\Setting::getValue("Version"));
                $versionOfInterest = new \WHMCS\Version\SemanticVersion($version);
                if (\WHMCS\Version\SemanticVersion::compare($versionOfInterest, $currentVersion, ">")) {
                    \WHMCS\Updater\Version\IncrementalVersion::factory($version)->applyUpdate();
                }
            }
            require_once ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "modulefunctions.php";
            rebuildModuleHookCache();
            rebuildAddonHookCache();
        } catch (\WHMCS\Exception\File\NotDeleted $e) {
            \Log::warning($e->getMessage(), array("incrementalVersion" => $versionOfInterest->getCanonical(), "trace" => $e->getTraceAsString()));
        } catch (\WHMCS\Exception $e) {
            $msg = "Unable to complete incremental updates: " . $e->getMessage();
            \Log::error($msg, array("incrementalVersion" => $versionOfInterest->getCanonical(), "trace" => $e->getTraceAsString()));
            throw new \WHMCS\Exception($msg);
        }
        return $this;
    }
    public function getAdminPath()
    {
        return $this->customAdminPath;
    }
    public function getVersion()
    {
        return $this->version;
    }
    public function setVersion(\WHMCS\Version\SemanticVersion $version)
    {
        $this->version = $version;
        return $this;
    }
    public function getLatestVersion()
    {
        return $this->latestVersion;
    }
    public function setLatestVersion(\WHMCS\Version\SemanticVersion $latest)
    {
        $this->latestVersion = $latest;
        return $this;
    }
    public function clearCompiledTemplates()
    {
        if (is_dir($this->templatesCompiledDir)) {
            $fileDeletionErrors = false;
            $finder = new \Symfony\Component\Finder\Finder();
            $files = $finder->name("*.php")->in(array($this->templatesCompiledDir));
            foreach ($files as $file) {
                $filename = $file->getFilename();
                if ($filename != "index.php" && !@unlink($this->templatesCompiledDir . DIRECTORY_SEPARATOR . $filename)) {
                    $fileDeletionErrors = true;
                }
            }
            $subdirsToDelete = array("HTML");
            foreach ($subdirsToDelete as $subdir) {
                if (is_dir($this->templatesCompiledDir . DIRECTORY_SEPARATOR . $subdir)) {
                    \WHMCS\Utility\File::recursiveDelete($this->templatesCompiledDir . DIRECTORY_SEPARATOR . $subdir);
                }
            }
            if ($fileDeletionErrors) {
                throw new \WHMCS\Exception("Failed to delete one or more compiled template files.");
            }
        }
        return $this;
    }
    public function setReleaseTierPin()
    {
        if (\WHMCS\Config\Setting::getValue("WHMCSUpdatePinVersion")) {
            return NULL;
        }
        $filesVersion = new \WHMCS\Version\SemanticVersion(\WHMCS\Application::FILES_VERSION);
        switch ($filesVersion->getPreReleaseIdentifier()) {
            case "release":
                $pin = Composer\ComposerJson::STABILITY_STABLE;
                break;
            case "rc":
                $pin = Composer\ComposerJson::STABILITY_RC;
                break;
            case "beta":
                $pin = Composer\ComposerJson::STABILITY_BETA;
                break;
            case "alpha":
                $pin = Composer\ComposerJson::STABILITY_ALPHA;
                break;
            default:
                $pin = Composer\ComposerJson::STABILITY_STABLE;
        }
        \WHMCS\Config\Setting::setValue("WHMCSUpdatePinVersion", $pin);
    }
    public function seedDatabase()
    {
        $parser = new \PhpMyAdmin\SqlParser\Parser((new DatabaseContent())->getDatabaseSeedContent());
        $pdo = \WHMCS\Database\Capsule::getInstance()->getConnection()->getPdo();
        foreach ($parser->statements as $statement) {
            $sql = $statement->build();
            if (substr($sql, 0, 3) != "SET") {
                $pdo->exec($sql);
            }
        }
    }
    public function createInitialAdminUser($username, $firstName, $lastName, $password, $email)
    {
        $hasher = new \WHMCS\Security\Hash\Password();
        $passwordHash = $hasher->hash($password);
        $apiPasswordHash = $hasher->hash(md5($password));
        $username = mysql_real_escape_string($username);
        $firstName = mysql_real_escape_string($firstName);
        $lastName = mysql_real_escape_string($lastName);
        $email = mysql_real_escape_string($email);
        $notes = "Welcome to WHMCS!  " . "Please ensure you have setup the cron job to automate tasks";
        $widgets = "getting_started:true,orders_overview:true," . "supporttickets_overview:true,my_notes:true,client_activity:true," . "open_invoices:true,activity_log:true|income_overview:true," . "system_overview:true,whmcs_news:true,sysinfo:true," . "admin_activity:true,todo_list:true,network_status:true," . "income_forecast:true|";
        $tbladminData = array("username" => $username, "password" => $apiPasswordHash, "passwordhash" => $passwordHash, "firstname" => $firstName, "lastname" => $lastName, "email" => $email, "signature" => "", "notes" => $notes, "supportdepts" => ",", "roleid" => "1", "template" => "blend", "homewidgets" => $widgets, "uuid" => \Ramsey\Uuid\Uuid::uuid4()->toString(), "id" => "1");
        mysql_query(sprintf("INSERT INTO `tbladmins` (`%s`) VALUE (\"%s\")", implode("`, `", array_keys($tbladminData)), implode("\", \"", array_values($tbladminData))));
    }
    public function performNonSeedIncrementalChange()
    {
        $version = new \WHMCS\Version\SemanticVersion("6.3.0-rc.1");
        $updater630 = new \WHMCS\Updater\Version\Version630rc1($version);
        $updater630->insertUpgradeTimeForMDE();
        $version = new \WHMCS\Version\SemanticVersion("7.2.0-alpha.1");
        $updater720 = new \WHMCS\Updater\Version\Version720alpha1($version);
        $updater720->conditionallyCreateHtaccessFile();
        $updater720->detectAndSetUriPathMode();
        $table = "tblannouncements";
        $published = "1";
        $title = "Thank you for choosing WHMCS!";
        $announcement = "<p>Welcome to <a title=\"WHMCS\" href=\"http://whmcs.com\" target=\"_blank\">WHMCS</a>!\n You have made a great choice and we want to help you get up and running as\n quickly as possible.</p>\n<p>This is a sample announcement. Announcements are a great way to keep your\n customers informed about news and special offers. You can edit or delete this\n announcement by logging into the admin area and navigating to <em>Support &gt; \n Announcements</em>.</p>\n<p>If at any point you get stuck, our support team is available 24x7 to\n assist you. Simply visit <a title=\"www.whmcs.com/support\" \n href=\"https://www.whmcs.com/support\" target=\"_blank\">www.whmcs.com/support</a>\n to request assistance.</p>";
        $sql = "INSERT INTO `%s` " . "(date, title, announcement, published) " . "VALUES(NOW(), '%s', '%s', '%s')";
        mysql_query(sprintf($sql, $table, $title, $announcement, $published));
        $table = "tbltransientdata";
        $name = "cronComplete";
        $data = "1";
        $expires = strtotime("+1 day", time());
        $sql = "INSERT INTO `%s` (name, data, expires) VALUES('%s', '%s', '%s')";
        mysql_query(sprintf($sql, $table, $name, $data, $expires));
        $version = new \WHMCS\Version\SemanticVersion("7.7.0-alpha.1");
        $updater770 = new \WHMCS\Updater\Version\Version770alpha1($version);
        $updater770->migrateStorageConfigurations();
    }
}

?>