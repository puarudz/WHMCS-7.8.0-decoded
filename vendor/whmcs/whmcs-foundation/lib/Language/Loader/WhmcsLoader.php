<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Language\Loader;

class WhmcsLoader extends \Symfony\Component\Translation\Loader\ArrayLoader implements \Symfony\Component\Translation\Loader\LoaderInterface
{
    protected $globalVariable = NULL;
    public function __construct($globalVariable = "_LANG")
    {
        $this->globalVariable = $globalVariable;
    }
    public function load($resource, $locale, $domain = "messages")
    {
        ${$this->globalVariable} = array();
        ob_start();
        require $resource;
        ob_end_clean();
        return parent::load(${$this->globalVariable}, $locale, $domain);
    }
}

?>