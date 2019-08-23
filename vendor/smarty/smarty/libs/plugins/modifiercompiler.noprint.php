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
 * @subpackage PluginsModifierCompiler
 */
/**
 * Smarty noprint modifier plugin
 * Type:     modifier
 * Name:     noprint
 * Purpose:  return an empty string
 *
 * @author Uwe Tews
 * @return string with compiled code
 */
function smarty_modifiercompiler_noprint()
{
    return "''";
}

?>