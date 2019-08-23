<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace League\CLImate\Util;

trait UtilImporter
{
    /**
     * An instance of the UtilFactory
     *
     * @var \League\CLImate\Util\UtilFactory $util
     */
    protected $util;
    /**
     * Sets the $util property
     *
     * @param UtilFactory $util
     */
    public function util(UtilFactory $util)
    {
        $this->util = $util;
    }
}

?>