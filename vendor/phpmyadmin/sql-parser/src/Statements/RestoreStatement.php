<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

/**
 * `RESTORE` statement.
 */
namespace PhpMyAdmin\SqlParser\Statements;

/**
 * `RESTORE` statement.
 *
 * RESTORE TABLE tbl_name [, tbl_name] ... FROM '/path/to/backup/directory'
 *
 * @category   Statements
 *
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */
class RestoreStatement extends MaintenanceStatement
{
    /**
     * Options of this statement.
     *
     * @var array
     */
    public static $OPTIONS = array('TABLE' => 1, 'FROM' => array(2, 'var'));
}

?>