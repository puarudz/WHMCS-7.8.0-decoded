<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

class HTMLPurifier_HTMLModule_CommonAttributes extends HTMLPurifier_HTMLModule
{
    /**
     * @type string
     */
    public $name = 'CommonAttributes';
    /**
     * @type array
     */
    public $attr_collections = array('Core' => array(
        0 => array('Style'),
        // 'xml:space' => false,
        'class' => 'Class',
        'id' => 'ID',
        'title' => 'CDATA',
    ), 'Lang' => array(), 'I18N' => array(0 => array('Lang')), 'Common' => array(0 => array('Core', 'I18N')));
}
// vim: et sw=4 sts=4

?>