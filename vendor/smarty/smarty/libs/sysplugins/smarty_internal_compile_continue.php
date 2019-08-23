<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

/**
 * Smarty Internal Plugin Compile Continue
 * Compiles the {continue} tag
 *
 * @package    Smarty
 * @subpackage Compiler
 * @author     Uwe Tews
 */
/**
 * Smarty Internal Plugin Compile Continue Class
 *
 * @package    Smarty
 * @subpackage Compiler
 */
class Smarty_Internal_Compile_Continue extends Smarty_Internal_Compile_Break
{
    /**
     * Tag name
     *
     * @var string
     */
    public $tag = 'continue';
}

?>