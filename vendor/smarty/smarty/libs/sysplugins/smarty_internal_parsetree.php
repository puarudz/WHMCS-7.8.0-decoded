<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

/**
 * Smarty Internal Plugin Templateparser Parsetree
 * These are classes to build parsetree in the template parser
 *
 * @package    Smarty
 * @subpackage Compiler
 * @author     Thue Kristensen
 * @author     Uwe Tews
 */
/**
 * @package    Smarty
 * @subpackage Compiler
 * @ignore
 */
abstract class Smarty_Internal_ParseTree
{
    /**
     * Buffer content
     *
     * @var mixed
     */
    public $data;
    /**
     * Subtree array
     *
     * @var array
     */
    public $subtrees = array();
    /**
     * Return buffer
     *
     * @param \Smarty_Internal_Templateparser $parser
     *
     * @return string buffer content
     */
    public abstract function to_smarty_php(Smarty_Internal_Templateparser $parser);
    /**
     * Template data object destructor
     */
    public function __destruct()
    {
        $this->data = null;
        $this->subtrees = null;
    }
}

?>