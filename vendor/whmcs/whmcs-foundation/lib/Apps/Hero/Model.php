<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Apps\Hero;

class Model
{
    public $data = NULL;
    public function __construct($data)
    {
        $this->data = $data;
    }
    public function getImageUrl()
    {
        return $this->data["image"];
    }
    public function hasTargetAppKey()
    {
        return 0 < strlen($this->getTargetAppKey());
    }
    public function getTargetAppKey()
    {
        return $this->data["app_key"];
    }
    public function hasRemoteUrl()
    {
        return 0 < strlen($this->getRemoteUrl());
    }
    public function getRemoteUrl()
    {
        return $this->data["remote_url"];
    }
}

?>