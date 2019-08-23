<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace League\CLImate\TerminalObject\Dynamic;

use League\CLImate\Util\Reader\ReaderInterface;
use League\CLImate\Util\Reader\Stdin;
abstract class InputAbstract extends DynamicTerminalObject
{
    /**
     * The prompt text
     *
     * @var string $prompt
     */
    protected $prompt;
    /**
     * An instance of ReaderInterface
     *
     * @var \League\CLImate\Util\Reader\ReaderInterface $reader
     */
    protected $reader;
    /**
     * Do it! Prompt the user for information!
     *
     * @return string
     */
    public abstract function prompt();
    /**
     * Format the prompt incorporating spacing and any acceptable options
     *
     * @return string
     */
    protected abstract function promptFormatted();
}

?>