<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Payment\Contracts;

interface RemoteTokenDetailsInterface
{
    public function getRemoteToken();
    public function setRemoteToken($value);
    public function createRemote();
    public function updateRemote();
    public function deleteRemote();
}

?>