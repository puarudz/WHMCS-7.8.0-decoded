<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

class HTMLPurifier_AttrDef_CSS_AlphaValue extends HTMLPurifier_AttrDef_CSS_Number
{
    public function __construct()
    {
        parent::__construct(false);
        // opacity is non-negative, but we will clamp it
    }
    /**
     * @param string $number
     * @param HTMLPurifier_Config $config
     * @param HTMLPurifier_Context $context
     * @return string
     */
    public function validate($number, $config, $context)
    {
        $result = parent::validate($number, $config, $context);
        if ($result === false) {
            return $result;
        }
        $float = (double) $result;
        if ($float < 0.0) {
            $result = '0';
        }
        if ($float > 1.0) {
            $result = '1';
        }
        return $result;
    }
}
// vim: et sw=4 sts=4

?>