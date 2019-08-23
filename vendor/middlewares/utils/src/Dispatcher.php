<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Middlewares\Utils;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Closure;
class Dispatcher
{
    /**
     * @var MiddlewareInterface[]
     */
    private $stack;
    /**
     * Static helper to create and dispatch a request.
     *
     * @param MiddlewareInterface[]
     * @param ServerRequestInterface|null $request
     *
     * @return ResponseInterface
     */
    public static function run(array $stack, ServerRequestInterface $request = null)
    {
        if ($request === null) {
            $request = Factory::createServerRequest();
        }
        return (new static($stack))->dispatch($request);
    }
    /**
     * @param MiddlewareInterface[] $stack middleware stack (with at least one middleware component)
     */
    public function __construct(array $stack)
    {
        assert(count($stack) > 0);
        $this->stack = $stack;
    }
    /**
     * Dispatches the middleware stack and returns the resulting `ResponseInterface`.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function dispatch(ServerRequestInterface $request)
    {
        $resolved = $this->resolve(0);
        return $resolved->process($request);
    }
    /**
     * @param int $index middleware stack index
     *
     * @return DelegateInterface
     */
    private function resolve($index)
    {
        return new Delegate(function (ServerRequestInterface $request) use($index) {
            $middleware = isset($this->stack[$index]) ? $this->stack[$index] : new CallableMiddleware(function () {
            });
            if ($middleware instanceof Closure) {
                $middleware = new CallableMiddleware($middleware);
            }
            assert($middleware instanceof MiddlewareInterface);
            $result = $middleware->process($request, $this->resolve($index + 1));
            assert($result instanceof ResponseInterface);
            return $result;
        });
    }
}

?>