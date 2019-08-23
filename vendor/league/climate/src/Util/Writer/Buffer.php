<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace League\CLImate\Util\Writer;

class Buffer implements WriterInterface
{
    /**
     * @var string $contents The buffered data.
     */
    protected $contents = "";
    /**
     * Write the content to the buffer.
     *
     * @param string $content
     *
     * @return void
     */
    public function write($content)
    {
        $this->contents .= $content;
    }
    /**
     * Get the buffered data.
     *
     * @return string
     */
    public function get()
    {
        return $this->contents;
    }
    /**
     * Clean the buffer and throw away any data.
     *
     * @return void
     */
    public function clean()
    {
        $this->contents = "";
    }
}

?>