<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

/**
 * Smarty Method GetGlobal
 *
 * Smarty::getGlobal() method
 *
 * @package    Smarty
 * @subpackage PluginsInternal
 * @author     Uwe Tews
 */
class Smarty_Internal_Method_GetGlobal
{
    /**
     * Valid for all objects
     *
     * @var int
     */
    public $objMap = 7;
    /**
     * Returns a single or all global  variables
     *
     * @api Smarty::getGlobal()
     *
     * @param \Smarty_Internal_Data $data
     * @param string                $varName variable name or null
     *
     * @return string|array variable value or or array of variables
     */
    public function getGlobal(Smarty_Internal_Data $data, $varName = null)
    {
        if (isset($varName)) {
            if (isset(Smarty::$global_tpl_vars[$varName])) {
                return Smarty::$global_tpl_vars[$varName]->value;
            } else {
                return '';
            }
        } else {
            $_result = array();
            foreach (Smarty::$global_tpl_vars as $key => $var) {
                $_result[$key] = $var->value;
            }
            return $_result;
        }
    }
}

?>