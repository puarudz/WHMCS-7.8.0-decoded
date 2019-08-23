<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function getMysqlCompat()
{
    return DI::make("mysqlCompat");
}
function checkIsPdoStatement($statement, $functionName)
{
    if ($statement && $statement instanceof PDOStatement) {
        return true;
    }
    trigger_error($functionName . "() expects parameter to be resource, " . gettype($statement) . " was given.", 512);
    return false;
}
function mysql_affected_rows()
{
    return getmysqlcompat()->mysqlAffectedRows();
}
function mysql_client_encoding()
{
    throw new BadFunctionCallException("mysql_client_encoding" . " is not supported");
}
function mysql_close()
{
    throw new BadFunctionCallException("mysql_close" . " is not supported");
}
function mysql_connect()
{
    throw new BadFunctionCallException("mysql_connect" . " is not supported");
}
function mysql_create_db()
{
    throw new BadFunctionCallException("mysql_create_db" . " is not supported");
}
function mysql_data_seek()
{
    throw new BadFunctionCallException("mysql_data_seek" . " is not supported");
}
function mysql_db_name()
{
    throw new BadFunctionCallException("mysql_db_name" . " is not supported");
}
function mysql_db_query()
{
    throw new BadFunctionCallException("mysql_db_query" . " is not supported");
}
function mysql_drop_db()
{
    throw new BadFunctionCallException("mysql_drop_db" . " is not supported");
}
function mysql_errno()
{
    throw new BadFunctionCallException("mysql_errno" . " is not supported");
}
function mysql_error()
{
    return getmysqlcompat()->mysqlError();
}
function mysql_escape_string()
{
    throw new BadFunctionCallException("mysql_escape_string" . " is not supported");
}
function mysql_fetch_array($statement = NULL)
{
    if (!checkispdostatement($statement, "mysql_fetch_array")) {
        return NULL;
    }
    return getmysqlcompat()->mysqlFetchArray($statement);
}
function mysql_fetch_assoc($statement = NULL)
{
    if (!checkispdostatement($statement, "mysql_fetch_assoc")) {
        return NULL;
    }
    return getmysqlcompat()->mysqlFetchAssoc($statement);
}
function mysql_fetch_field()
{
    throw new BadFunctionCallException("mysql_fetch_field" . " is not supported");
}
function mysql_fetch_lengths()
{
    throw new BadFunctionCallException("mysql_fetch_lengths" . " is not supported");
}
function mysql_fetch_object($statement = NULL)
{
    if (!checkispdostatement($statement, "mysql_fetch_object")) {
        return false;
    }
    return getmysqlcompat()->mysqlFetchObject($statement);
}
function mysql_fetch_row($statement = NULL)
{
    if (!checkispdostatement($statement, "mysql_fetch_row")) {
        return NULL;
    }
    return getmysqlcompat()->mysqlFetchRow($statement);
}
function mysql_field_flags()
{
    throw new BadFunctionCallException("mysql_field_flags" . " is not supported");
}
function mysql_field_len()
{
    throw new BadFunctionCallException("mysql_field_len" . " is not supported");
}
function mysql_field_name()
{
    throw new BadFunctionCallException("mysql_field_name" . " is not supported");
}
function mysql_field_seek()
{
    throw new BadFunctionCallException("mysql_field_seek" . " is not supported");
}
function mysql_field_table()
{
    throw new BadFunctionCallException("mysql_field_table" . " is not supported");
}
function mysql_field_type()
{
    throw new BadFunctionCallException("mysql_field_type" . " is not supported");
}
function mysql_free_result()
{
    throw new BadFunctionCallException("mysql_free_result" . " is not supported");
}
function mysql_get_client_info($pdo = NULL)
{
    return getmysqlcompat()->mysqlGetClientInfo($pdo);
}
function mysql_get_host_info()
{
    throw new BadFunctionCallException("mysql_get_host_info" . " is not supported");
}
function mysql_get_proto_info()
{
    throw new BadFunctionCallException("mysql_get_proto_info" . " is not supported");
}
function mysql_get_server_info($pdo = NULL)
{
    return getmysqlcompat()->mysqlGetServerInfo($pdo);
}
function mysql_info()
{
    throw new BadFunctionCallException("mysql_info" . " is not supported");
}
function mysql_insert_id()
{
    return getmysqlcompat()->mysqlInsertId();
}
function mysql_list_dbs()
{
    throw new BadFunctionCallException("mysql_list_dbs" . " is not supported");
}
function mysql_list_fields()
{
    throw new BadFunctionCallException("mysql_list_fields" . " is not supported");
}
function mysql_list_processes()
{
    throw new BadFunctionCallException("mysql_list_processes" . " is not supported");
}
function mysql_list_tables()
{
    throw new BadFunctionCallException("mysql_list_tables" . " is not supported");
}
function mysql_num_fields($statement = NULL)
{
    if (!checkispdostatement($statement, "mysql_num_fields")) {
        return NULL;
    }
    return getmysqlcompat()->mysqlNumFields($statement);
}
function mysql_num_rows($statement = NULL)
{
    if (!checkispdostatement($statement, "mysql_num_rows")) {
        return NULL;
    }
    return getmysqlcompat()->mysqlNumRows($statement);
}
function mysql_pconnect()
{
    throw new BadFunctionCallException("mysql_pconnect" . " is not supported");
}
function mysql_ping()
{
    throw new BadFunctionCallException("mysql_ping" . " is not supported");
}
function mysql_query($query)
{
    return getmysqlcompat()->mysqlQuery($query);
}
function mysql_real_escape_string($data)
{
    return getmysqlcompat()->mysqlRealEscapeString($data);
}
function mysql_result()
{
    throw new BadFunctionCallException("mysql_result" . " is not supported");
}
function mysql_select_db()
{
    throw new BadFunctionCallException("mysql_select_db" . " is not supported");
}
function mysql_set_charset()
{
    throw new BadFunctionCallException("mysql_set_charset" . " is not supported");
}
function mysql_stat()
{
    throw new BadFunctionCallException("mysql_stat" . " is not supported");
}
function mysql_tablename()
{
    throw new BadFunctionCallException("mysql_tablename" . " is not supported");
}
function mysql_thread_id()
{
    throw new BadFunctionCallException("mysql_thread_id" . " is not supported");
}
function mysql_unbuffered_query()
{
    throw new BadFunctionCallException("mysql_unbuffered_query" . " is not supported");
}

?>