<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace League\CLImate\Decorator\Parser;

trait ParserImporter
{
    /**
     * An instance of the Parser class
     *
     * @var \League\CLImate\Decorator\Parser\Parser $parser
     */
    protected $parser;
    /**
     * Import the parser and set the property
     *
     * @param \League\CLImate\Decorator\Parser\Parser $parser
     */
    public function parser(Parser $parser)
    {
        $this->parser = $parser;
    }
}

?>