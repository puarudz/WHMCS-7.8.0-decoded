<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Apps\Meta\Schema;

class AbstractVersion
{
    public $metaData = array();
    public function __construct(array $metaData)
    {
        $this->metaData = $metaData;
    }
    protected function meta($key)
    {
        $parts = explode(".", $key);
        $response = $this->metaData;
        foreach ($parts as $part) {
            if (isset($response[$part])) {
                $response = $response[$part];
            } else {
                return null;
            }
        }
        return $response;
    }
}

?>