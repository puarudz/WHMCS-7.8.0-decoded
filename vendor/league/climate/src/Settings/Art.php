<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace League\CLImate\Settings;

class Art implements SettingsInterface
{
    /**
     * An array of valid art directories
     *  @var array[] $dirs
     */
    public $dirs = [];
    /**
     * Add directories of art
     */
    public function add()
    {
        $this->dirs = array_merge($this->dirs, func_get_args());
        $this->dirs = array_filter($this->dirs);
        $this->dirs = array_values($this->dirs);
    }
}

?>