<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

/**
 * class for undefined variable object
 * This class defines an object for undefined variable handling
 *
 * @package    Smarty
 * @subpackage Template
 */
class Smarty_Undefined_Variable extends Smarty_Variable
{
    /**
     * Returns null for not existing properties
     *
     * @param string $name
     *
     * @return null
     */
    public function __get($name)
    {
        return null;
    }
    /**
     * Always returns an empty string.
     *
     * @return string
     */
    public function __toString()
    {
        return '';
    }
}

?>