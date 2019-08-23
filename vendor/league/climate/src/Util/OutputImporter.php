<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace League\CLImate\Util;

trait OutputImporter
{
    /**
     * An instance of the OutputFactory
     *
     * @var \League\CLImate\Util\Output $output
     */
    protected $output;
    /**
     * Sets the $output property
     *
     * @param Output $output
     */
    public function output(Output $output)
    {
        $this->output = $output;
    }
}

?>