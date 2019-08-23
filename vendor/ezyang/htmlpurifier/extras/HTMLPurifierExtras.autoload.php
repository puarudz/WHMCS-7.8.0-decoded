<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

/**
 * @file
 * Convenience file that registers autoload handler for HTML Purifier.
 *
 * @warning
 *      This autoloader does not contain the compatibility code seen in
 *      HTMLPurifier_Bootstrap; the user is expected to make any necessary
 *      changes to use this library.
 */
if (function_exists('spl_autoload_register')) {
    spl_autoload_register(array('HTMLPurifierExtras', 'autoload'));
    if (function_exists('__autoload')) {
        // Be polite and ensure that userland autoload gets retained
        spl_autoload_register('__autoload');
    }
} elseif (!function_exists('__autoload')) {
    function __autoload($class)
    {
        return HTMLPurifierExtras::autoload($class);
    }
}
// vim: et sw=4 sts=4

?>