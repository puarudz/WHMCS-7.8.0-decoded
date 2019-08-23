<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Database;

class MysqlCompat
{
    public static $emulated = array("mysql_affected_rows", "mysql_error", "mysql_fetch_array", "mysql_fetch_assoc", "mysql_fetch_object", "mysql_fetch_row", "mysql_get_client_info", "mysql_get_server_info", "mysql_insert_id", "mysql_num_fields", "mysql_num_rows", "mysql_query", "mysql_real_escape_string");
    public static $notSupported = array("mysql_client_encoding", "mysql_close", "mysql_connect", "mysql_create_db", "mysql_data_seek", "mysql_db_name", "mysql_db_query", "mysql_drop_db", "mysql_errno", "mysql_escape_string", "mysql_fetch_field", "mysql_fetch_lengths", "mysql_field_flags", "mysql_field_len", "mysql_field_name", "mysql_field_seek", "mysql_field_table", "mysql_field_type", "mysql_free_result", "mysql_get_host_info", "mysql_get_proto_info", "mysql_info", "mysql_list_dbs", "mysql_list_fields", "mysql_list_processes", "mysql_list_tables", "mysql_pconnect", "mysql_ping", "mysql_result", "mysql_select_db", "mysql_set_charset", "mysql_stat", "mysql_tablename", "mysql_thread_id", "mysql_unbuffered_query");
    protected $lastStatement = NULL;
    protected $lastPDOException = NULL;
    protected $pdo = NULL;
    public function __construct(\PDO $pdo)
    {
        $this->setPdo($pdo);
    }
    public function getLastStatement()
    {
        return $this->lastStatement;
    }
    public function setLastStatement(\PDOStatement $lastStatement = NULL)
    {
        $this->lastStatement = $lastStatement;
        return $this;
    }
    public function getPdo()
    {
        return $this->pdo;
    }
    public function setPdo($pdo)
    {
        $this->pdo = $pdo;
        return $this;
    }
    public function isExecuteQuery($query)
    {
        $execQueryStarts = array("unlock", "lock");
        $query = strtolower($query);
        foreach ($execQueryStarts as $keyword) {
            if (strpos($query, $keyword) === 0) {
                return true;
            }
        }
        return false;
    }
    public function mysqlAffectedRows()
    {
        $statement = $this->getLastStatement();
        return $statement->rowCount();
    }
    public function mysqlQuery($query, $pdo = NULL)
    {
        if (!$pdo || !$pdo instanceof \PDO) {
            $pdo = $this->getPdo();
        }
        $query = trim($query);
        try {
            $this->lastPDOException = null;
            if ($this->isExecuteQuery($query)) {
                return $pdo->exec($query);
            }
            $statement = $pdo->query($query);
            $this->setLastStatement($statement);
            $returnStatementOn = array("select", "show", "alter", "create", "drop", "rename", "truncate");
            $doReturnStatement = false;
            foreach ($returnStatementOn as $ret) {
                if (stripos($query, $ret) === 0) {
                    $doReturnStatement = true;
                    break;
                }
            }
            if ($doReturnStatement) {
                $result = $statement;
            } else {
                $result = $statement->rowCount();
            }
        } catch (\PDOException $e) {
            $this->lastPDOException = $e;
            $result = false;
        }
        return $result;
    }
    public function mysqlError()
    {
        if ($this->lastPDOException instanceof \PDOException) {
            $error = $this->lastPDOException->errorInfo;
        } else {
            if ($this->getLastStatement()) {
                $error = $this->getLastStatement()->errorInfo();
            } else {
                $error = $this->getPdo()->errorInfo();
            }
        }
        $mysqlError = trim(implode(" ", $error));
        if ($mysqlError == "00000") {
            $mysqlError = "";
        }
        return $mysqlError;
    }
    public function mysqlFetchArray(\PDOStatement $statement)
    {
        return $statement->fetch(\PDO::FETCH_BOTH);
    }
    public function mysqlFetchAssoc(\PDOStatement $statement)
    {
        return $statement->fetch(\PDO::FETCH_ASSOC);
    }
    public function mysqlFetchObject(\PDOStatement $statement)
    {
        return $statement->fetch(\PDO::FETCH_OBJ);
    }
    public function mysqlFetchRow(\PDOStatement $statement)
    {
        return $statement->fetch(\PDO::FETCH_NUM);
    }
    public function mysqlGetClientInfo($pdo = NULL)
    {
        if (!$pdo || !$pdo instanceof \PDO) {
            $pdo = $this->getPdo();
        }
        return $pdo->getAttribute(\PDO::ATTR_CLIENT_VERSION);
    }
    public function mysqlGetServerInfo($pdo = NULL)
    {
        if (!$pdo || !$pdo instanceof \PDO) {
            $pdo = $this->getPdo();
        }
        return $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
    }
    public function mysqlInsertId()
    {
        return (int) $this->getPdo()->lastInsertId();
    }
    public function mysqlNumFields(\PDOStatement $statement)
    {
        return $statement->columnCount();
    }
    public function mysqlNumRows(\PDOStatement $statement)
    {
        return $statement->rowCount();
    }
    public function mysqlRealEscapeString($data)
    {
        return substr($this->getPdo()->quote($data), 1, -1);
    }
}

?>