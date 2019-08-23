<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Route\Middleware;

class RoutableApiRequestUri extends WhitelistFilter
{
    const ATTRIBUTE_API_REQUEST = "isApiRouteRequest";
    public function __construct($strictFilter = true, array $filterList = array())
    {
        parent::__construct($strictFilter, array_unique(array("/api", "/includes/api.php")));
    }
    protected function whitelistRequest(\Psr\Http\Message\ServerRequestInterface $request)
    {
        include_once ROOTDIR . "/includes/adminfunctions.php";
        if (!defined("APICALL")) {
            define("APICALL", true);
        }
        if (!$request instanceof \WHMCS\Api\ApplicationSupport\Http\ServerRequest) {
            $apiRequest = \WHMCS\Api\ApplicationSupport\Http\ServerRequest::fromGlobals($request->getServerParams(), $request->getQueryParams(), $request->getParsedBody(), $request->getCookieParams(), $request->getUploadedFiles());
            $apiRequest = $apiRequest->withUri($request->getUri());
            foreach ($request->getAttributes() as $attribute => $value) {
                $apiRequest = $apiRequest->withAttribute($attribute, $value);
            }
        } else {
            $apiRequest = $request;
        }
        if (!$apiRequest->getAttribute(static::ATTRIBUTE_API_REQUEST)) {
            $apiRequest = $apiRequest->withAttribute(static::ATTRIBUTE_API_REQUEST, true);
        }
        return $apiRequest;
    }
    protected function blacklistRequest(\Psr\Http\Message\ServerRequestInterface $request)
    {
        return $request->withAttribute(static::ATTRIBUTE_API_REQUEST, false);
    }
}

?>