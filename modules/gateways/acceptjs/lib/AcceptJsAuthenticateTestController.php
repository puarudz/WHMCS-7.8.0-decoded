<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Gateway\AcceptJs;

class AcceptJsAuthenticateTestController extends \net\authorize\api\controller\AuthenticateTestController
{
    public function __construct(\net\authorize\api\contract\v1\AnetApiRequestType $request)
    {
        parent::__construct($request);
        $this->httpClient = new AcceptJsHttpClient();
    }
}

?>