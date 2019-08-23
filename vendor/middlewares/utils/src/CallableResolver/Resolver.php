<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Middlewares\Utils\CallableResolver;

/**
 * Common logic for callable resolvers.
 */
abstract class Resolver implements CallableResolverInterface
{
    /**
     * Resolves a callable from a string.
     *
     * @param string $callable
     *
     * @return array|string
     */
    protected function resolveString($callable)
    {
        //ClassName/Service::method
        if (strpos($callable, '::') !== false) {
            list($id, $method) = explode('::', $callable, 2);
            return [$id, $method];
        }
        return $callable;
    }
}

?>