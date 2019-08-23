<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

/**
 * Smarty Method AddAutoloadFilters
 *
 * Smarty::addAutoloadFilters() method
 *
 * @package    Smarty
 * @subpackage PluginsInternal
 * @author     Uwe Tews
 */
class Smarty_Internal_Method_AddAutoloadFilters extends Smarty_Internal_Method_SetAutoloadFilters
{
    /**
     * Add autoload filters
     *
     * @api Smarty::setAutoloadFilters()
     *
     * @param \Smarty_Internal_TemplateBase|\Smarty_Internal_Template|\Smarty $obj
     * @param array                                                           $filters filters to load automatically
     * @param string                                                          $type    "pre", "output", … specify
     *                                                                                 the filter type to set.
     *                                                                                 Defaults to none treating
     *                                                                                 $filters' keys as the
     *                                                                                 appropriate types
     *
     * @return \Smarty|\Smarty_Internal_Template
     * @throws \SmartyException
     */
    public function addAutoloadFilters(Smarty_Internal_TemplateBase $obj, $filters, $type = null)
    {
        $smarty = $obj->_getSmartyObj();
        if ($type !== null) {
            $this->_checkFilterType($type);
            if (!empty($smarty->autoload_filters[$type])) {
                $smarty->autoload_filters[$type] = array_merge($smarty->autoload_filters[$type], (array) $filters);
            } else {
                $smarty->autoload_filters[$type] = (array) $filters;
            }
        } else {
            foreach ((array) $filters as $type => $value) {
                $this->_checkFilterType($type);
                if (!empty($smarty->autoload_filters[$type])) {
                    $smarty->autoload_filters[$type] = array_merge($smarty->autoload_filters[$type], (array) $value);
                } else {
                    $smarty->autoload_filters[$type] = (array) $value;
                }
            }
        }
        return $obj;
    }
}

?>