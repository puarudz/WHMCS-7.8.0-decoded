<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Exception\Storage;

class StorageConfigurationException extends StorageException
{
    private $fields = array();
    public function __construct(array $fields)
    {
        parent::__construct(join(" ", array_values($fields)));
        $this->fields = $fields;
    }
    public function getFields()
    {
        return $this->fields;
    }
}

?>