<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Apps\Hero;

class Collection
{
    public $heros = NULL;
    public function __construct()
    {
        $this->heros = (new \WHMCS\Apps\Feed())->heros();
    }
    public function get()
    {
        $country = strtolower(\WHMCS\Config\Setting::getValue("DefaultCountry"));
        $heros = array_key_exists($country, $this->heros) ? $this->heros[$country] : $this->heros["default"];
        foreach ($heros as $key => $values) {
            $heros[$key] = new Model($values);
        }
        return $heros;
    }
}

?>