<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace League\CLImate\Util\Writer;

interface WriterInterface
{
    /**
     * @param  string $content
     *
     * @return void
     */
    public function write($content);
}

?>