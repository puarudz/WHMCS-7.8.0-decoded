<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Session\Database;

class Configuration
{
    private $lifetime = 1440;
    private $databaseConfiguration = array();
    private $table = "tblsessions";
    private $connectionAlias = "sessionsDbConnection";
    private $databaseManager = NULL;
    private $connection = NULL;
    private $logErrors = false;
    public function __construct(array $databaseConfig, \WHMCS\Database\Capsule $databaseManager)
    {
        if (!empty($databaseConfig["connectionAlias"])) {
            $this->setConnectionAlias($databaseConfig["connectionAlias"]);
        }
        if (!empty($databaseConfig["lifetime"])) {
            $this->setLifetime($databaseConfig["lifetime"]);
        }
        if (!empty($databaseConfig["table"])) {
            $this->setTable($databaseConfig["table"]);
        }
        if (!empty($databaseConfig["config"]) && is_array($databaseConfig["config"])) {
            $this->setDatabaseConfiguration($databaseConfig["config"]);
        }
        if (!empty($databaseConfig["logErrors"])) {
            $this->setLogErrors($databaseConfig["logErrors"]);
        }
        $this->setDatabaseManager($databaseManager);
    }
    public function getConnection()
    {
        if (!$this->connection) {
            $connection = null;
            $connectionAlias = $this->getConnectionAlias();
            if ($connectionAlias) {
                try {
                    $connection = $this->getDatabaseManager()->getConnection($connectionAlias);
                } catch (\Exception $e) {
                }
            }
            if (!$connection) {
                $databaseConfiguration = $this->getDatabaseConfiguration();
                if (!empty($databaseConfiguration)) {
                    $connection = $this->getConnectionFromConfiguration($databaseConfiguration);
                }
            }
            if (!$connection) {
                $connection = \WHMCS\Database\Capsule::connection();
            }
            if (!$connection) {
                throw new \WHMCS\Exception\Application\Configuration\CannotConnectToDatabase("Invalid database configuration for session handler");
            }
            $this->connection = $connection;
        }
        return $this->connection;
    }
    protected function getConnectionFromConfiguration($databaseConfiguration)
    {
        $connection = null;
        $connectionAlias = $this->getConnectionAlias();
        $databaseManager = $this->getDatabaseManager();
        if (empty($databaseConfiguration["database"]) || empty($databaseConfiguration["username"]) || empty($databaseConfiguration["password"])) {
            throw new \WHMCS\Exception\Session\Database\DatabaseSessionException("Missing session database configuration values");
        }
        if (empty($databaseConfiguration["driver"])) {
            $databaseConfiguration["driver"] = "mysql";
        }
        if (empty($databaseConfiguration["host"])) {
            $databaseConfiguration["host"] = "localhost";
        }
        if (empty($databaseConfiguration["charset"])) {
            $databaseConfiguration["charset"] = "utf8";
        }
        $databaseManager->addConnection($databaseConfiguration, $connectionAlias);
        $connection = $databaseManager->getConnection($connectionAlias);
        return $connection;
    }
    public function getLifetime()
    {
        return $this->lifetime;
    }
    public function setLifetime($lifetime)
    {
        $this->lifetime = $lifetime;
        return $this;
    }
    public function getDatabaseConfiguration()
    {
        return $this->databaseConfiguration;
    }
    public function setDatabaseConfiguration(array $databaseConfiguration)
    {
        $this->databaseConfiguration = $databaseConfiguration;
        return $this;
    }
    public function getTable()
    {
        return $this->table;
    }
    public function setTable($table)
    {
        $this->table = $table;
        return $this;
    }
    public function getConnectionAlias()
    {
        return $this->connectionAlias;
    }
    public function setConnectionAlias($connectionAlias)
    {
        $this->connectionAlias = $connectionAlias;
        return $this;
    }
    public function getDatabaseManager()
    {
        return $this->databaseManager;
    }
    public function setDatabaseManager(\WHMCS\Database\Capsule $databaseManager)
    {
        $this->databaseManager = $databaseManager;
        return $this;
    }
    public function getLogErrors()
    {
        return $this->logErrors;
    }
    public function setLogErrors($logErrors)
    {
        $this->logErrors = $logErrors;
        return $this;
    }
}

?>