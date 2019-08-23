<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

/**
 * Whoops - php errors for cool kids
 * @author Filipe Dobreira <http://github.com/filp>
 */
namespace Whoops\Util;

/**
 * Used as output callable for Symfony\Component\VarDumper\Dumper\HtmlDumper::dump()
 *
 * @see TemplateHelper::dump()
 */
class HtmlDumperOutput
{
    private $output;
    public function __invoke($line, $depth)
    {
        // A negative depth means "end of dump"
        if ($depth >= 0) {
            // Adds a two spaces indentation to the line
            $this->output .= str_repeat('  ', $depth) . $line . "\n";
        }
    }
    public function getOutput()
    {
        return $this->output;
    }
    public function clear()
    {
        $this->output = null;
    }
}

?>