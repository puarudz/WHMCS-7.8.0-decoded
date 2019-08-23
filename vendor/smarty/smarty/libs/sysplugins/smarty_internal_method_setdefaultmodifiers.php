<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

/**
 * Smarty Method SetDefaultModifiers
 *
 * Smarty::setDefaultModifiers() method
 *
 * @package    Smarty
 * @subpackage PluginsInternal
 * @author     Uwe Tews
 */
class Smarty_Internal_Method_SetDefaultModifiers
{
    /**
     * Valid for Smarty and template object
     *
     * @var int
     */
    public $objMap = 3;
    /**
     * Set default modifiers
     *
     * @api Smarty::setDefaultModifiers()
     *
     * @param \Smarty_Internal_TemplateBase|\Smarty_Internal_Template|\Smarty $obj
     * @param array|string                                                    $modifiers modifier or list of modifiers
     *                                                                                   to set
     *
     * @return \Smarty|\Smarty_Internal_Template
     */
    public function setDefaultModifiers(Smarty_Internal_TemplateBase $obj, $modifiers)
    {
        $smarty = $obj->_getSmartyObj();
        $smarty->default_modifiers = (array) $modifiers;
        return $obj;
    }
}

?>