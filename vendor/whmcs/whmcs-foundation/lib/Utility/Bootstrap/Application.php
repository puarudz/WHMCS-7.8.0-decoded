<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Utility\Bootstrap;

class Application extends AbstractBootstrap
{
    public static function boot(\WHMCS\Config\RuntimeStorage $preBootInstances = NULL)
    {
        $container = parent::boot();
        static::defineClassAliases(static::getAliases());
        $container->bind("terminus", function () {
            return \WHMCS\Terminus::getInstance();
        });
        $instances = static::getInstances();
        if ($preBootInstances) {
            $errMgmt = $preBootInstances->errorManagement;
            if ($errMgmt instanceof \WHMCS\Utility\ErrorManagement) {
                $instances["ErrorManagement"] = $errMgmt;
            }
        }
        static::bindInstances($container, $instances);
        static::bindSingletons($container, static::getSingletons());
        static::registerServices($container, static::getServices());
        return $container;
    }
    public static function verifyInstallerIsAbsent()
    {
        if (file_exists(ROOTDIR . "/install/install.php")) {
            if (\WHMCS\Config\Setting::getValue("Version") == \WHMCS\Application::FILES_VERSION) {
                $msg = "Application is up to date, but installer directory is still present";
            } else {
                $msg = "Installer directory exists but not expected.";
            }
            throw new \WHMCS\Exception\Application\InstallerExists($msg);
        }
    }
    public static function persistSession()
    {
        \WHMCS\Auth::persistAdminSession();
        \WHMCS\Auth::persistClientSession();
    }
    public static function getAliases()
    {
        return array("\\WHMCS\\Application\\Support\\Facades\\Log" => "Log", "\\WHMCS\\Application\\Support\\Facades\\Menu" => "Menu", "\\WHMCS\\Log\\Activity" => "ActivityLog", "\\WHMCS\\Application\\Support\\Facades\\Config" => "Config", "\\WHMCS\\Application\\Support\\Facades\\App" => "App", "\\WHMCS\\Application\\Support\\Facades\\Lang" => "Lang", "\\WHMCS\\Application\\Support\\Facades\\AdminLang" => "AdminLang", "\\WHMCS\\Application\\Support\\Facades\\Storage" => "Storage", "\\Firebase\\JWT\\JWT" => "JWT", "\\WHMCS\\ClientArea" => "WHMCS_ClientArea", "\\WHMCS\\Chart" => "WHMCSChart");
    }
    public static function getInstances()
    {
        return array("ErrorManagement" => new \WHMCS\Utility\ErrorManagement(\WHMCS\Utility\ErrorManagement::factoryRunner()));
    }
    public static function getSingletons()
    {
        return array("config" => function () {
            return new \WHMCS\Config\Application();
        }, "db" => function () {
            $db = new \WHMCS\Database(\DI::make("config"));
            return $db;
        }, "mysqlCompat" => function () {
            return new \WHMCS\Database\MysqlCompat(\DI::make("db")->getPdo());
        }, "license" => function () {
            $app = \DI::make("app");
            $config = $app->getApplicationConfig();
            $license = (new \WHMCS\License())->checkFile("a896faf2c31f2acd47b0eda0b3fd6070958f1161")->setSalt($app->get_config("Version"), $app->get_hash())->setLicenseKey($app->get_license_key())->setLocalKey($app->get_config("License"));
            if ($config["use_internal_licensing_validation"]) {
                $license->useInternalValidationMirror();
            }
            return $license;
        }, "lang" => function () {
            $language = \WHMCS\Language\ClientLanguage::factory(\WHMCS\Config\Setting::getValue("Language"), \WHMCS\Session::get("Language"), isset($_REQUEST["language"]) ? $_REQUEST["language"] : "", defined("CLIENTAREA"));
            global $_LANG;
            $_LANG = $language->toArray();
            return $language;
        }, "adminlang" => function ($container, $languages) {
            if (!empty($languages) && is_array($languages)) {
                $language = array_shift($languages);
            } else {
                if (empty($languages)) {
                    $language = \WHMCS\Language\AdminLanguage::FALLBACK_LANGUAGE;
                } else {
                    $language = $languages;
                }
            }
            $language = \WHMCS\Language\AdminLanguage::factory($language);
            global $_ADMINLANG;
            $_ADMINLANG = $language->toArray();
            return $language;
        }, "menu" => function () {
            return new \WHMCS\View\Client\Menu\MenuRepository(new \WHMCS\View\Client\Menu\PrimaryNavbarFactory(), new \WHMCS\View\Client\Menu\SecondaryNavbarFactory(), new \WHMCS\View\Client\Menu\PrimarySidebarFactory(), new \WHMCS\View\Client\Menu\SecondarySidebarFactory());
        }, "oauth2_server" => function () {
            $storage = new \WHMCS\ApplicationLink\Storage\Whmcs();
            $server = new \WHMCS\ApplicationLink\Server\Server($storage, array("use_openid_connect" => true));
            $server->addGrantType(new \OAuth2\GrantType\ClientCredentials($storage));
            $server->addGrantType(new \OAuth2\OpenID\GrantType\AuthorizationCode($storage));
            $request = \OAuth2\HttpFoundationBridge\Request::createFromGlobals();
            if ($moduleServer = $server->getModuleApplicationLinkServer($request)) {
                return $moduleServer;
            }
            return $server;
        }, "oauth2_sso" => function () {
            $storage = new \WHMCS\ApplicationLink\Storage\Whmcs();
            $server = new \WHMCS\ApplicationLink\Server\Server($storage);
            $server->setScopeUtil(new \OAuth2\Scope($storage));
            $server->addGrantType(new \WHMCS\ApplicationLink\GrantType\SingleSignOn($storage), "client_credentials");
            $request = \OAuth2\HttpFoundationBridge\Request::createFromGlobals();
            if ($moduleServer = $server->getModuleApplicationLinkServer($request)) {
                return $moduleServer;
            }
            return $server;
        }, "runtimeStorage" => function () {
            return new \WHMCS\Config\RuntimeStorage();
        }, "remoteAuth" => function () {
            return new \WHMCS\Authentication\Remote\RemoteAuth();
        });
    }
    public static function getServices()
    {
        return array("\\WHMCS\\Log\\LogServiceProvider", "\\WHMCS\\Route\\RouteServiceProvider", "\\WHMCS\\Application\\ApplicationServiceProvider", "\\WHMCS\\Api\\ApiServiceProvider", "\\WHMCS\\Smarty\\SmartyServiceProvider", "\\WHMCS\\View\\ViewServiceProvider", "\\WHMCS\\ClientArea\\ClientAreaServiceProvider", "\\WHMCS\\Knowledgebase\\KnowledgebaseServiceProvider", "\\WHMCS\\MarketConnect\\MarketConnectServiceProvider", "\\WHMCS\\Cart\\CartServiceProvider", "\\WHMCS\\Authentication\\Remote\\ServiceProvider", "\\WHMCS\\File\\StorageServiceProvider", "WHMCS\\Payment\\PaymentServiceProvider");
    }
}

?>