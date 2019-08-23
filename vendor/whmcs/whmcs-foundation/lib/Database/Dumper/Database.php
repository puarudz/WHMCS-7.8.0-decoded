<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Database\Dumper;

class Database
{
    protected $database = NULL;
    protected $addCreateDatabase = false;
    protected $addDropDatabase = false;
    public function __construct(\WHMCS\Database\DatabaseInterface $database, array $options = array())
    {
        $this->setDatabase($database);
        if (isset($options["addCreateDatabase"])) {
            $this->setAddCreateDatabase($options["addCreateDatabase"]);
        }
        if (isset($options["addDropDatabase"])) {
            $this->setAddDropDatabase($options["addDropDatabase"]);
        }
    }
    protected function setDatabase(\WHMCS\Database\DatabaseInterface $database)
    {
        $this->database = $database;
        return $this;
    }
    protected function getDatabase()
    {
        return $this->database;
    }
    protected function setAddCreateDatabase($addCreateDatabase)
    {
        if (!is_bool($addCreateDatabase)) {
            throw new \WHMCS\Exception("Invalid add create database option.");
        }
        $this->addCreateDatabase = $addCreateDatabase;
        return $this;
    }
    protected function getAddCreateDatabase()
    {
        return $this->addCreateDatabase;
    }
    protected function setAddDropDatabase($addDropDatabase)
    {
        if (!is_bool($addDropDatabase)) {
            throw new \WHMCS\Exception("Invalid add drop database option.");
        }
        $this->addDropDatabase = $addDropDatabase;
        return $this;
    }
    protected function getAddDropDatabase()
    {
        return $this->addDropDatabase;
    }
    public function dumpTo($path, array $tables = array())
    {
        if (!is_string($path)) {
            throw new \WHMCS\Exception("Please provide a valid dump path.");
        }
        $path = trim($path);
        $pathDir = dirname($path);
        if (!is_dir($pathDir)) {
            throw new \WHMCS\Exception((string) $pathDir . " is not a directory.");
        }
        if (!is_writable($pathDir)) {
            throw new \WHMCS\Exception((string) $pathDir . " is not writable.");
        }
        if (realpath($pathDir) != $pathDir) {
            throw new \WHMCS\Exception("Please provide a valid dump path.");
        }
        if (!touch($path) || !chmod($path, 384) || ($fh = fopen($path, "w")) === false) {
            throw new \WHMCS\Exception("Unable to open " . $path . " for writing.");
        }
        try {
            $this->dump($fh);
            if (count($tables) == 0) {
                $tables = $this->getAllTables();
            }
            foreach ($tables as $table => $options) {
                $tableDumper = new Table($this->getDatabase(), $table, $options);
                $tableDumper->dump($fh);
            }
        } catch (\WHMCS\Exception $e) {
            $this->unlock();
            fclose($fh);
            throw $e;
        }
        $this->unlock();
        fclose($fh);
        return $this;
    }
    public function importFrom($path)
    {
        $pdo = \WHMCS\Database\Capsule::getInstance()->getConnection()->getPdo();
        if (!is_string($path) || !file_exists($path)) {
            throw new \WHMCS\Exception("Import path does not exist.");
        }
        $path = trim($path);
        $pathDir = dirname($path);
        if (realpath($pathDir) != $pathDir) {
            throw new \WHMCS\Exception("Unable to access import path.");
        }
        $fh = fopen($path, "r");
        if ($fh === false) {
            throw new \WHMCS\Exception("Unable to open " . $path . " for reading.");
        }
        $query = "";
        while (($line = fgets($fh)) !== false) {
            if ($query == "" && trim($line) == "") {
                continue;
            }
            $query .= $line;
            if (strpos($query, "--") === 0) {
                $query = "";
                continue;
            }
            if (substr(trim($query), -1) == ";") {
                try {
                    $pdo->exec(trim($query));
                    $query = "";
                } catch (\Exception $e) {
                    fclose($fh);
                    throw new \WHMCS\Exception("Unable to import " . $path . ": " . $e->getMessage());
                }
            }
        }
        fclose($fh);
        return $this;
    }
    public function dump($fh)
    {
        if (!is_resource($fh)) {
            throw new \WHMCS\Exception("Please provide a valid fopen() handle.");
        }
        $result = fwrite($fh, $this->generateDumpHeader());
        if ($result === false || $result === 0) {
            throw new \WHMCS\Exception("Unable to write " . $this->getDatabase()->getDatabaseName() . " header.");
        }
        if ($this->getAddCreateDatabase()) {
            if ($this->getAddDropDatabase()) {
                $result = fwrite($fh, $this->generateDropDatabase());
                if ($result === false || $result === 0) {
                    throw new \WHMCS\Exception("Unable to write " . $this->getDatabase()->getDatabaseName() . " drop statement.");
                }
            }
            $result = fwrite($fh, $this->generateCreateDatabase());
            if ($result === false || $result === 0) {
                throw new \WHMCS\Exception("Unable to write " . $this->getDatabase()->getDatabaseName() . " create statement.");
            }
        }
        $result = fwrite($fh, $this->generateUseDatabase());
        if ($result === false || $result === 0) {
            throw new \WHMCS\Exception("Unable to write " . $this->getDatabase()->getDatabaseName() . " use statement.");
        }
        return $this;
    }
    protected function getAllTables()
    {
        $return = array();
        $query = "SHOW TABLES FROM `" . $this->getDatabase()->getDatabaseName() . "`";
        $result = mysql_query($query, $this->getDatabase()->getConnection());
        $error = mysql_error();
        if ($error != "") {
            throw new \WHMCS\Exception("Unable to retrieve a list of tables from " . $this->getDatabase()->getDatabaseName() . ": " . $error . ".");
        }
        while ($row = mysql_fetch_assoc($result)) {
            $return[$row["Tables_in_" . $this->getDatabase()->getDatabaseName()]] = array();
        }
        return $return;
    }
    protected function generateDumpHeader()
    {
        $class = get_called_class();
        $serverVersion = mysql_get_server_info($this->getDatabase()->getConnection());
        return "-- Generated by " . $class . "\n--\n-- Database: " . $this->getDatabase()->getDatabaseName() . "\n-- ------------------------------------------------------\n-- Server version\t" . $serverVersion . "\n\n/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n/*!40101 SET NAMES utf8 */;\n/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;\n/*!40103 SET TIME_ZONE='+00:00' */;\n/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;\n/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;\n/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;\n/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;\n\n";
    }
    protected function generateCreateDatabase()
    {
        return "CREATE DATABASE `" . $this->getDatabase()->getDatabaseName() . "`;\n\n";
    }
    protected function generateDropDatabase()
    {
        return "DROP DATABASE IF EXISTS `" . $this->getDatabase()->getDatabaseName() . "`;\n";
    }
    protected function generateUseDatabase()
    {
        return "USE `" . $this->getDatabase()->getDatabaseName() . "`;\n\n";
    }
    protected function unlock()
    {
        full_query("UNLOCK TABLES", $this->getDatabase()->getConnection());
        $error = mysql_error();
        if ($error != "") {
            throw new \WHMCS\Exception("Unable to unlock tables: " . $error);
        }
        return $this;
    }
}

?>