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
 * Smarty upper modifier plugin
 * Type:     modifier
 * Name:     lower
 * Purpose:  convert string to uppercase
 *
 * @link   http://smarty.php.net/manual/en/language.modifier.upper.php lower (Smarty online manual)
 * @author Uwe Tews
 *
 * @param array $params parameters
 *
 * @return string with compiled code
 */
function smarty_modifiercompiler_upper($params)
{
    if (Smarty::$_MBSTRING) {
        return 'mb_strtoupper(' . $params[0] . ', \'' . addslashes(Smarty::$_CHARSET) . '\')';
    }
    // no MBString fallback
    return 'strtoupper(' . $params[0] . ')';
}

?>