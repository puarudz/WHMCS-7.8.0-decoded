<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Gateway\GoCardless\Resources;

class AbstractResource
{
    protected $params = array();
    protected $client = NULL;
    public function __construct(array $gatewayParams)
    {
        $this->params = $gatewayParams;
        $this->client = \WHMCS\Module\Gateway\GoCardless\Client::factory($gatewayParams["accessToken"]);
    }
}

?>