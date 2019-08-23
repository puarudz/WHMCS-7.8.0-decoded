<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\MarketConnect\Services;

interface ServiceInterface
{
    public function provision($model, array $params);
    public function configure($model, array $params);
    public function cancel($model);
    public function renew($model, array $response);
    public function install($model);
}

?>