<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS;

class Application extends Init
{
    protected $clientTemplate = NULL;
    protected $dangerousVariables = array("_GET", "_POST", "_REQUEST", "_SERVER", "_COOKIE", "_FILES", "_ENV", "GLOBALS");
    protected $phpSelf = NULL;
    const FILES_VERSION = "7.8.0-rc.1";
    const RELEASE_DATE = "2019-08-06";
    public function __construct(Config\AbstractConfig $config, Database $database = NULL)
    {
        global $CONFIG;
        $this->initInputs();
        $this->importConfig($config);
        $this->loadDatabase($database);
        $this->loadAdminDefinedConfigurations();
        Http\Request::defineProxyTrustFromApplication($this);
        $this->setRemoteIp(Utility\Environment\CurrentUser::getIP());
        $this->setPhpSelf($_SERVER["SCRIPT_NAME"]);
        if ($this->shouldRedirectForIPBan() && $this->isVisitorIPBanned()) {
            $this->redirect($CONFIG["SystemURL"] . "/banned.php");
        }
        Session::initializeHandler($this->getApplicationConfig());
        $instanceid = $this->getWHMCSInstanceID();
        if (!$instanceid) {
            $instanceid = $this->createWHMCSInstanceID();
        }
        $session = new Session();
        $session->create($instanceid);
        $token_manager =& getTokenManager($this);
        $token_manager->conditionallySetToken();
        $this->clientTemplate = View\Template::factory(Config\Setting::getValue("Template"), Session::get("Template"), $this->get_req_var("systpl"));
        if (isset($_REQUEST["carttpl"])) {
            $_SESSION["OrderFormTemplate"] = $_REQUEST["carttpl"];
        }
        $this->validate_templates();
        if (!defined("DoNotForceNonSSLonDLFile")) {
            $this->forced_non_ssl_filenames[] = "dl";
        }
    }
    public static function getInstance()
    {
        return \DI::make("app");
    }
    protected function loadDatabase(Database $database = NULL)
    {
        if (!$database) {
            \DI::make("db");
        }
        return $this;
    }
    public function importConfig(Config\AbstractConfig $config)
    {
        $vars = $config->validConfigVariables();
        foreach ($vars as $varToGlobal) {
            $this->registerGlobalVariable($varToGlobal, $config[$varToGlobal]);
        }
        if (!file_exists(ROOTDIR . DIRECTORY_SEPARATOR . "loghandler.php")) {
            $errMgmt = new Utility\ErrorManagement();
            $errMgmt->applyConfigurationSettings($config);
        }
        if (!$config["templates_compiledir"] || preg_match("/^" . Config\Application::DEFAULT_COMPILED_TEMPLATES_FOLDER . "[\\\\\\/]*\$/", $config["templates_compiledir"])) {
            $config["templates_compiledir"] = ROOTDIR . DIRECTORY_SEPARATOR . Config\Application::DEFAULT_COMPILED_TEMPLATES_FOLDER;
        }
        if (!$config["attachments_dir"]) {
            $config["attachments_dir"] = ROOTDIR . DIRECTORY_SEPARATOR . Config\Application::DEFAULT_ATTACHMENTS_FOLDER;
        }
        if (!$config["downloads_dir"]) {
            $config["downloads_dir"] = ROOTDIR . DIRECTORY_SEPARATOR . Config\Application::DEFAULT_DOWNLOADS_FOLDER;
        }
        if (!$config["crons_dir"]) {
            $config["crons_dir"] = ROOTDIR . DIRECTORY_SEPARATOR . Config\Application::DEFAULT_CRON_FOLDER;
        }
        if (!$config["customadminpath"]) {
            $config["customadminpath"] = Config\Application::DEFAULT_ADMIN_FOLDER;
        }
        $config["templates_compiledir"] = preg_replace("/[\\\\\\/]+\$/", "", $config["templates_compiledir"]);
        $config["attachments_dir"] = preg_replace("/[\\\\\\/]+\$/", "", $config["attachments_dir"]);
        $config["downloads_dir"] = preg_replace("/[\\\\\\/]+\$/", "", $config["downloads_dir"]);
        $config["crons_dir"] = preg_replace("/[\\\\\\/]+\$/", "", $config["crons_dir"]);
        if (isset($config["overidephptimelimit"]) && is_numeric($config["overidephptimelimit"])) {
            $overidephptimelimit = (int) $config["overidephptimelimit"];
        } else {
            $overidephptimelimit = 300;
        }
        @set_time_limit($overidephptimelimit);
        $this->license = trim($config["license"]);
        $this->cc_hash = $config["cc_encryption_hash"];
        $this->templates_compiledir = $config["templates_compiledir"];
        $this->customadminpath = $config["customadminpath"];
        return $this;
    }
    public function getLicense()
    {
        return \DI::make("license");
    }
    public function getLicenseClientKey()
    {
        return $this->license;
    }
    public function setPhpSelf($script)
    {
        global $PHP_SELF;
        $_SERVER["PHP_SELF"] = $script;
        $PHP_SELF = $this->phpSelf = $_SERVER["PHP_SELF"];
        return $this;
    }
    public function getPhpSelf()
    {
        return $this->phpSelf;
    }
    public function setRemoteIp($ip)
    {
        global $remote_ip;
        $remote_ip = $this->remote_ip = $ip;
        return $this;
    }
    public function getRemoteIp()
    {
        return $this->remote_ip;
    }
    public function getDatabaseObj()
    {
        return \DI::make("db");
    }
    public function getApplicationConfig()
    {
        return \DI::make("config");
    }
    public function getAttachmentsDir()
    {
        return $this->getApplicationConfig()->attachments_dir;
    }
    public function getDownloadsDir()
    {
        return $this->getApplicationConfig()->downloads_dir;
    }
    public function getTemplatesCacheDir()
    {
        return $this->getApplicationConfig()->templates_compiledir;
    }
    public function getCronDirectory()
    {
        return $this->getApplicationConfig()->crons_dir;
    }
    public function redirect($path = NULL, $vars = array(), $prefix = "")
    {
        $url = $this->getRedirectUrl($path, $vars, $prefix);
        header("Location: " . $url);
        Terminus::getInstance()->doExit();
    }
    public function getRedirectUrl($path, $vars = array(), $prefix = "")
    {
        if (!$path) {
            $path = $this->getPhpSelf();
        }
        $filenamePattern = "/^[a-zA-Z0-9~\\._\\/\\:\\-]*\$/";
        if (preg_match($filenamePattern, $path) !== 1) {
            throw new Exception\Fatal(sprintf("Invalid filename for redirect: %s", htmlspecialchars($path, ENT_QUOTES)));
        }
        $AnyMultipleSlashNotPrecededByColonPattern = "/([^:]|^)\\/\\/+/";
        $precedingCharacterIfAnyWithOneSlash = "\${1}/";
        $prefix = preg_replace($AnyMultipleSlashNotPrecededByColonPattern, $precedingCharacterIfAnyWithOneSlash, $prefix);
        if (is_array($vars)) {
            $vars = http_build_query($vars);
        }
        if (is_string($vars) && strpos($vars, "=") !== false) {
            $urlEncodedNewline = urlencode("\n");
            $urlEncodedCarriageReturn = urlencode("\r");
            $newlinePattern = "/[\n\r]|(" . $urlEncodedNewline . ")|(" . $urlEncodedCarriageReturn . ")/i";
            $vars = sprintf("?%s", preg_replace($newlinePattern, "", trim($vars)));
        } else {
            if ($vars) {
                throw new Exception\Fatal(sprintf("URL parameter variables must be in the form of an array or HTTP build query string"));
            }
        }
        return sprintf("%s%s%s", $prefix, $path, $vars);
    }
    public function redirectSystemURL($path = "", $vars = "")
    {
        $this->redirect($path, $vars, $this->getSystemURL());
    }
    public function redirectToRoutePath($route, $routeVariables = array(), $queryParameters = NULL)
    {
        $redirectUrl = routePathWithQuery($route, $routeVariables, $queryParameters);
        header("Location: " . $redirectUrl);
        Terminus::getInstance()->doExit();
    }
    public function initInputs()
    {
        $_GET = $this->sanitize_input_vars($_GET);
        $_POST = $this->sanitize_input_vars($_POST);
        $_REQUEST = $this->sanitize_input_vars($_REQUEST);
        $_SERVER = $this->sanitize_input_vars($_SERVER);
        $_COOKIE = $this->sanitize_input_vars($_COOKIE);
        if (isset($_SERVER["REQUEST_METHOD"])) {
            switch ($_SERVER["REQUEST_METHOD"]) {
                case "GET":
                case "POST":
                case "OPTIONS":
                case "HEAD":
                    break;
                default:
                    header("HTTP/ 405 Method Not Allowed");
                    header("Allow: GET, POST, OPTIONS, HEAD");
                    throw new Exception\Fatal((string) $_SERVER["REQUEST_METHOD"] . " Request Method Not Allowed");
            }
        }
        foreach ($this->dangerousVariables as $var) {
            if (isset($_REQUEST[$var]) || isset($_FILES[$var])) {
                Terminus::getInstance()->doDie("Hacking attempt");
            }
        }
        $this->load_input();
        $this->clean_input();
        $this->register_globals();
        return $this;
    }
    protected function registerGlobalVariable($globalVariableName, $globalVariableValue)
    {
        global ${$globalVariableName};
        ${$globalVariableName} = $globalVariableValue;
    }
    protected function register_globals()
    {
        foreach ($this->input as $k => $v) {
            $this->registerGlobalVariable($k, $v);
        }
    }
    protected function loadAdminDefinedConfigurations()
    {
        global $CONFIG;
        $CONFIG = array();
        $CONFIG = Config\Setting::allAsArray();
        if (isset($CONFIG["DisplayErrors"]) && $CONFIG["DisplayErrors"]) {
            Utility\ErrorManagement::enableIniDisplayErrors();
        }
        header("Content-Type: text/html; charset=" . $CONFIG["Charset"]);
        foreach (array("SystemURL", "Domain") as $v) {
            if (!isset($CONFIG[$v])) {
                $CONFIG[$v] = "";
            }
            if (substr($CONFIG[$v], -1, 1) == "/") {
                $CONFIG[$v] = substr($CONFIG[$v], 0, -1);
            }
        }
        return $CONFIG;
    }
    public function isVisitorIPBanned()
    {
        $handle = $this->getDatabaseObj()->retrieveDatabaseConnection();
        \Illuminate\Database\Capsule\Manager::table("tblbannedips")->where("expires", "<", date("Y-m-d H:i:s"))->delete();
        $visitorIP = $this->getRemoteIp();
        $visitorIPParts = explode(".", $visitorIP);
        array_pop($visitorIPParts);
        $remoteIP1 = implode(".", $visitorIPParts) . ".*";
        array_pop($visitorIPParts);
        $remoteIP2 = implode(".", $visitorIPParts) . ".*.*";
        $result = full_query("SELECT id FROM tblbannedips WHERE " . "ip='" . db_escape_string($visitorIP) . "' OR " . "ip='" . db_escape_string($remoteIP1) . "' OR " . "ip='" . db_escape_string($remoteIP2) . "' " . "ORDER BY id DESC", $handle);
        $data = mysql_fetch_array($result);
        if ($data["id"]) {
            return true;
        }
        return false;
    }
    protected function shouldRedirectForIPBan()
    {
        $excludedPages = array("banned.php", "includes/api.php");
        foreach ($excludedPages as $excludedPage) {
            $currentPage = substr($this->getPhpSelf(), strlen($excludedPage) * -1);
            if ($currentPage == $excludedPage) {
                return false;
            }
        }
        return true;
    }
    public function getWHMCSInstanceID()
    {
        return $this->get_config("InstanceID");
    }
    protected function createWHMCSInstanceID()
    {
        $instanceId = genRandomVal(12);
        $this->set_config("InstanceID", $instanceId);
        return $instanceId;
    }
    public function getCurrentFilename($stripExtension = true)
    {
        $filename = $this->getPhpSelf();
        $filename = substr($filename, strrpos($filename, "/"));
        $filename = str_replace("/", "", $filename);
        if ($stripExtension) {
            $filename = substr($filename, 0, strrpos($filename, "."));
        }
        return $filename;
    }
    public function getSystemURL($withTrailing = true)
    {
        $url = trim($this->get_config("SystemURL"));
        if ($url) {
            while (substr($url, -1) == "/") {
                $url = substr($url, 0, -1);
            }
            if ($withTrailing && substr($url, -1) != "/") {
                $url .= "/";
            }
        }
        return $url;
    }
    public function getSystemSSLURL()
    {
        return $this->isSSLAvailable() ? $this->getSystemURL() : "";
    }
    public function getSystemSSLURLOrFail()
    {
        if (!$this->isSSLAvailable()) {
            throw new Exception\Fatal("Application Link \"issuer\" (your site) is required by the OIDC specification to use SSL");
        }
        return $this->getSystemURL();
    }
    public function isSSLAvailable()
    {
        return substr($this->getSystemURL(), 0, 5) == "https";
    }
    public function isApiRequest()
    {
        return defined("APICALL");
    }
    public function isClientAreaRequest()
    {
        return defined("CLIENTAREA");
    }
    public function isAdminAreaRequest()
    {
        return defined("ADMINAREA");
    }
    public function isExecutingViaCron()
    {
        return defined("IN_CRON");
    }
    public function getClientAreaTemplate()
    {
        return $this->clientTemplate;
    }
    public function getVersion()
    {
        return new Version\SemanticVersion(self::FILES_VERSION);
    }
    public function getDBVersion()
    {
        return new Version\SemanticVersion($this->get_config("Version"));
    }
    public function doFileAndDBVersionsNotMatch()
    {
        $filesVersionObj = $this->getVersion();
        $dbVersionOjb = $this->getDBVersion();
        return !Version\SemanticVersion::compare($dbVersionOjb, $filesVersionObj, "==");
    }
    public function getReleaseDate()
    {
        return self::RELEASE_DATE;
    }
    public function setMaintenanceMode()
    {
        $maintenanceMode = Config\Setting::find("MaintenanceMode");
        $maintenanceMode->value = true;
        $maintenanceMode->save();
        return $this;
    }
    public function unsetMaintenanceMode()
    {
        $maintenanceMode = Config\Setting::find("MaintenanceMode");
        $maintenanceMode->value = false;
        $maintenanceMode->save();
        return $this;
    }
    public function isInMaintenanceMode()
    {
        return (bool) Config\Setting::getValue("MaintenanceMode");
    }
    public function isUpdateAvailable()
    {
        $updater = new Installer\Update\Updater();
        return $updater->isUpdateAvailable();
    }
    public function isUpdating()
    {
        return null;
    }
    public function getLogoUrlForEmailTemplate()
    {
        $logoUrl = trim(Config\Setting::getValue("LogoURL"));
        if ($logoUrl && substr($logoUrl, 0, 4) != "http") {
            $logoUrl = ltrim($logoUrl, "/");
            $scheme = $this->isSSLAvailable() ? "https" : "http";
            $logoUrl = $scheme . "://" . $logoUrl;
        }
        return $logoUrl;
    }
}

?>