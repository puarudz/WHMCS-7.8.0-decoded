<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Http\Message;

class ServerRequest extends \Zend\Diactoros\ServerRequest
{
    protected $queryBag = NULL;
    protected $requestBag = NULL;
    protected $attributesBag = NULL;
    public static function fromGlobals(array $server = NULL, array $query = NULL, array $body = NULL, array $cookies = NULL, array $files = NULL)
    {
        $stdRequest = \Zend\Diactoros\ServerRequestFactory::fromGlobals($server, $query, $body, $cookies, $files);
        $ourRequest = new self($stdRequest->getServerParams(), $stdRequest->getUploadedFiles(), $stdRequest->getUri(), $stdRequest->getMethod(), $stdRequest->getBody(), $stdRequest->getHeaders(), $stdRequest->getCookieParams(), $stdRequest->getQueryParams(), $stdRequest->getParsedBody(), $stdRequest->getProtocolVersion());
        return $ourRequest;
    }
    public function withQueryParams(array $query)
    {
        $this->queryBag = null;
        return parent::withQueryParams($query);
    }
    public function query()
    {
        if (!$this->queryBag) {
            $this->queryBag = new \Symfony\Component\HttpFoundation\ParameterBag((array) $this->getQueryParams());
        }
        return $this->queryBag;
    }
    public function withParsedBody($data)
    {
        $this->requestBag = null;
        return parent::withParsedBody($data);
    }
    public function request()
    {
        if (!$this->requestBag) {
            $this->requestBag = new \Symfony\Component\HttpFoundation\ParameterBag((array) $this->getParsedBody());
        }
        return $this->requestBag;
    }
    public function getResponseType()
    {
        $responseFactory = \DI::make("Route\\ResponseType");
        return $responseFactory->getMappedRoute($this->getAttribute("matchedRouteHandle"));
    }
    public function expectsJsonResponse()
    {
        if ($this->getResponseType() == ResponseFactory::RESPONSE_TYPE_JSON) {
            return true;
        }
        $headerValues = $this->getHeader("X-Requested-With");
        if (in_array("XMLHttpRequest", $headerValues)) {
            return true;
        }
        return false;
    }
    public function isAdminRequest()
    {
        return (bool) $this->getAttribute(\WHMCS\Route\Middleware\RoutableAdminRequestUri::ATTRIBUTE_ADMIN_REQUEST);
    }
    public function isApiRequest()
    {
        return (bool) $this->getAttribute(\WHMCS\Route\Middleware\RoutableApiRequestUri::ATTRIBUTE_API_REQUEST);
    }
    public function withAttribute($attribute, $value)
    {
        $this->attributesBag = null;
        return parent::withAttribute($attribute, $value);
    }
    public function withoutAttribute($attribute)
    {
        $this->attributesBag = null;
        return parent::withoutAttribute($attribute);
    }
    public function attributes()
    {
        if (!$this->attributesBag) {
            $this->attributesBag = new \Symfony\Component\HttpFoundation\ParameterBag((array) $this->getAttributes());
        }
        return $this->attributesBag;
    }
    public function has($key)
    {
        if ($this->query()->has($key) || $this->attributes()->has($key) || $this->request()->has($key)) {
            return true;
        }
        return false;
    }
    public function get($key, $default = NULL)
    {
        if ($this !== ($result = $this->query()->get($key, $this))) {
            return $result;
        }
        if ($this !== ($result = $this->attributes()->get($key, $this))) {
            return $result;
        }
        if ($this !== ($result = $this->request()->get($key, $this))) {
            return $result;
        }
        return $default;
    }
}

?>