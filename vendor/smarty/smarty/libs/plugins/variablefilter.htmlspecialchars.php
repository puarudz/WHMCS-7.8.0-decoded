<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

/**
 * Smarty plugin
 *
 * @package    Smarty
 * @subpackage PluginsFilter
 */
/**
 * Smarty htmlspecialchars variablefilter plugin
 *
 * @param string                    $source input string
 * @param \Smarty_Internal_Template $template
 *
 * @return string filtered output
 */
function smarty_variablefilter_htmlspecialchars($source, Smarty_Internal_Template $template)
{
    return htmlspecialchars($source, ENT_QUOTES, Smarty::$_CHARSET);
}

?>