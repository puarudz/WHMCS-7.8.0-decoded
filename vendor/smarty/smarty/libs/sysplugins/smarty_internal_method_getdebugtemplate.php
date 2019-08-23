<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

/**
 * Smarty Method GetDebugTemplate
 *
 * Smarty::getDebugTemplate() method
 *
 * @package    Smarty
 * @subpackage PluginsInternal
 * @author     Uwe Tews
 */
class Smarty_Internal_Method_GetDebugTemplate
{
    /**
     * Valid for Smarty and template object
     *
     * @var int
     */
    public $objMap = 3;
    /**
     * return name of debugging template
     *
     * @api Smarty::getDebugTemplate()
     *
     * @param \Smarty_Internal_TemplateBase|\Smarty_Internal_Template|\Smarty $obj
     *
     * @return string
     */
    public function getDebugTemplate(Smarty_Internal_TemplateBase $obj)
    {
        $smarty = $obj->_getSmartyObj();
        return $smarty->debug_tpl;
    }
}

?>