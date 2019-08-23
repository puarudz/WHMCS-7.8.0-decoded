<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

/**
 * `BACKUP` statement.
 */
namespace PhpMyAdmin\SqlParser\Statements;

/**
 * `BACKUP` statement.
 *
 * BACKUP TABLE tbl_name [, tbl_name] ... TO '/path/to/backup/directory'
 *
 * @category   Statements
 *
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */
class BackupStatement extends MaintenanceStatement
{
    /**
     * Options of this statement.
     *
     * @var array
     */
    public static $OPTIONS = array('TABLE' => 1, 'NO_WRITE_TO_BINLOG' => 2, 'LOCAL' => 3, 'TO' => array(4, 'var'));
}

?>