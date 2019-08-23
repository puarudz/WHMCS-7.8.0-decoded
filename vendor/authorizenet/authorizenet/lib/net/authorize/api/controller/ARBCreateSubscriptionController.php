<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace net\authorize\api\controller;

use net\authorize\api\contract\v1\AnetApiRequestType;
use net\authorize\api\controller\base\ApiOperationBase;
class ARBCreateSubscriptionController extends ApiOperationBase
{
    public function __construct(AnetApiRequestType $request)
    {
        $responseType = 'net\\authorize\\api\\contract\\v1\\ARBCreateSubscriptionResponse';
        parent::__construct($request, $responseType);
    }
    protected function validateRequest()
    {
        //validate required fields of $this->apiRequest->
        //validate non-required fields of $this->apiRequest->
    }
}

?>