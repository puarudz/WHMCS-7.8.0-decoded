<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace OAuth2;

interface RequestInterface
{
    public function query($name, $default = null);
    public function request($name, $default = null);
    public function server($name, $default = null);
    public function headers($name, $default = null);
    public function getAllQueryParameters();
}

?>