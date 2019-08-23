<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace League\CLImate\Util\Writer;

class StdOut implements WriterInterface
{
    /**
     * Write the content to the stream
     *
     * @param  string $content
     */
    public function write($content)
    {
        fwrite(\STDOUT, $content);
    }
}

?>