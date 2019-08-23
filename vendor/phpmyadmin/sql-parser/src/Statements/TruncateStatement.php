<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

/**
 * `TRUNCATE` statement.
 */
namespace PhpMyAdmin\SqlParser\Statements;

use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Statement;
/**
 * `TRUNCATE` statement.
 *
 * @category   Statements
 *
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */
class TruncateStatement extends Statement
{
    /**
     * Options for `TRUNCATE` statements.
     *
     * @var array
     */
    public static $OPTIONS = array('TABLE' => 1);
    /**
     * The name of the truncated table.
     *
     * @var Expression
     */
    public $table;
}

?>