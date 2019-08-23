<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Route\Middleware;

class WhitelistFilter implements \WHMCS\Route\Contracts\Middleware\StrategyInterface
{
    use Strategy\DelegatingMiddlewareTrait;
    protected $filterList = NULL;
    protected $strictFilter = true;
    public function __construct($strictFilter = true, array $filterList = array())
    {
        $this->setStrictFilter($strictFilter)->setFilterList($filterList);
    }
    public function _process(\WHMCS\Http\Message\ServerRequest $request, \Interop\Http\ServerMiddleware\DelegateInterface $delegate)
    {
        $isAllowed = false;
        if ($this->isAllowed($request)) {
            $request = $this->whitelistRequest($request);
            $isAllowed = true;
        } else {
            $request = $this->blacklistRequest($request);
        }
        if (!$isAllowed && $this->isStrictFilter()) {
            return new \WHMCS\Exception\HttpCodeException("Bad Request For Endpoint");
        }
        return $delegate->process($request);
    }
    protected function isAllowed(\Psr\Http\Message\ServerRequestInterface $request)
    {
        $path = $request->getUri()->getPath();
        foreach ($this->getFilterList() as $basePath) {
            if (strpos($path, $basePath) === 0) {
                return true;
            }
        }
        return false;
    }
    protected function getFilterList()
    {
        return $this->filterList;
    }
    protected function setFilterList(array $filterList)
    {
        $this->filterList = $filterList;
        return $this;
    }
    protected function isStrictFilter()
    {
        return $this->strictFilter;
    }
    protected function setStrictFilter($strictFilter)
    {
        $this->strictFilter = $strictFilter;
        return $this;
    }
    protected function whitelistRequest(\Psr\Http\Message\ServerRequestInterface $request)
    {
        return $request;
    }
    protected function blacklistRequest(\Psr\Http\Message\ServerRequestInterface $request)
    {
        return $request;
    }
}

?>