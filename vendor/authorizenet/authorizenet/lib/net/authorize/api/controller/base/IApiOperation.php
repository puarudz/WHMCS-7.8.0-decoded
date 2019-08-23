<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace net\authorize\api\controller\base;

interface IApiOperation
{
    /**
     * @return \net\authorize\api\contract\v1\ANetApiResponseType
     */
    public function getApiResponse();
    /**
     * @return \net\authorize\api\contract\v1\ANetApiResponseType
     */
    public function executeWithApiResponse($endPoint = null);
    /**
     * @return void
     */
    public function execute($endPoint = null);
}

?>