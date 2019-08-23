<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

/**
 * Exception thrown by the parser.
 */
namespace PhpMyAdmin\SqlParser\Exceptions;

use PhpMyAdmin\SqlParser\Token;
/**
 * Exception thrown by the parser.
 *
 * @category   Exceptions
 *
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */
class ParserException extends \Exception
{
    /**
     * The token that produced this error.
     *
     * @var Token
     */
    public $token;
    /**
     * Constructor.
     *
     * @param string $msg   the message of this exception
     * @param Token  $token the token that produced this exception
     * @param int    $code  the code of this error
     */
    public function __construct($msg = '', Token $token = null, $code = 0)
    {
        parent::__construct($msg, $code);
        $this->token = $token;
    }
}

?>