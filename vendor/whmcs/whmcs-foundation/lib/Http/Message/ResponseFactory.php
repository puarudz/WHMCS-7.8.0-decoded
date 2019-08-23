<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Http\Message;

class ResponseFactory implements \WHMCS\Route\Contracts\MapInterface
{
    use \WHMCS\Route\HandleMapTrait;
    const RESPONSE_TYPE_JSON = "JSON_MESSAGE";
    const RESPONSE_TYPE_HTML = "HTML_MESSAGE";
    public function getMappedAttributeName()
    {
        return "responseType";
    }
    public function factory(ServerRequest $request, $data = NULL)
    {
        $responseType = $this->getMappedRoute($request->getAttribute("matchedRouteHandle"));
        if (!$responseType) {
            $responseType = static::RESPONSE_TYPE_HTML;
        }
        if ($responseType == static::RESPONSE_TYPE_HTML) {
            if ($request->isAdminRequest()) {
                $response = new \WHMCS\Admin("");
                $response->setBodyContent((string) $data);
                $response->setResponseType($responseType);
                $response = $response->display();
            } else {
                $response = new \Zend\Diactoros\Response\HtmlResponse((string) $data);
            }
            return $response;
        }
        return new JsonResponse((array) $data);
    }
    public function factoryFromException(ServerRequest $request, \WHMCS\Exception\HttpCodeException $exception)
    {
        if ($exception instanceof \WHMCS\Exception\Authorization\AccessDenied) {
            return $this->factoryAccessDenied($request);
        }
        if ($exception instanceof \WHMCS\Exception\Authorization\InvalidCsrfToken) {
            return $this->factoryInvalidCsrfToken($request);
        }
        if ($exception instanceof \WHMCS\Exception\Authentication\LoginRequired) {
            return $this->factoryLoginRequired($request);
        }
        if ($request->isAdminRequest()) {
            return (new \WHMCS\Admin\ApplicationSupport\Http\Message\ResponseFactory())->genericError($request, $exception->getCode());
        }
        $response = $this->factory($request);
        if ($response instanceof JsonResponse) {
            $response = $response->withData(array("status" => "error", "errorMessage" => $exception->getMessage()));
        }
        $response = $response->withStatus($exception->getCode());
        return $response;
    }
    public function factoryAccessDenied(ServerRequest $request)
    {
        if ($request->isAdminRequest()) {
            $viewFactory = new \WHMCS\Admin\ApplicationSupport\Http\Message\ResponseFactory();
            return $viewFactory->genericError($request, 403);
        }
        return new \Zend\Diactoros\Response\RedirectResponse(routePath("clientarea-homepage"));
    }
    public function factoryInvalidCsrfToken(ServerRequest $request)
    {
        if ($request->isAdminRequest()) {
            $viewFactory = new \WHMCS\Admin\ApplicationSupport\Http\Message\ResponseFactory();
            return $viewFactory->invalidCsrfToken($request);
        }
        return new \Zend\Diactoros\Response\RedirectResponse(routePath("clientarea-homepage"));
    }
    public function factoryLoginRequired(ServerRequest $request)
    {
        if ($request->isAdminRequest()) {
            $controller = new \WHMCS\Admin\Controller\ErrorController();
            return $controller->loginRequired($request);
        }
        return new \Zend\Diactoros\Response\RedirectResponse(routePath("clientarea-homepage"));
    }
}

?>