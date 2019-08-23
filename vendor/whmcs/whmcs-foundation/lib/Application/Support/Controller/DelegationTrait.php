<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Application\Support\Controller;

trait DelegationTrait
{
    public function redirect(\WHMCS\Http\Message\ServerRequest $request)
    {
        return new \Zend\Diactoros\Response\RedirectResponse($request->getAttribute("redirect"));
    }
    public function redirectTo($pathData, \WHMCS\Http\Message\ServerRequest $request)
    {
        $pathVars = array();
        if (is_array($pathData)) {
            list($pathName, $pathVars) = $pathData;
        } else {
            $pathName = $pathData;
        }
        return $this->redirect($request->withAttribute("redirect", routePath($pathName, $pathVars)));
    }
    protected function delegateTo($pathData, \WHMCS\Http\Message\ServerRequest $request)
    {
        $pathVars = array();
        if (is_array($pathData)) {
            list($pathName, $pathVars) = $pathData;
        } else {
            $pathName = $pathData;
        }
        $request = $request->withUri($request->getUri()->withPath(\DI::make("Route\\UriPath")->getRawPath($pathName, $pathVars)));
        return (new \WHMCS\Route\Middleware\BackendDispatch())->getDispatch($request)->dispatch($request);
    }
}

?>