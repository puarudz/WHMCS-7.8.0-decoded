<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Middlewares\Utils\CallableResolver;

use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
/**
 * Resolve a callable using reflection.
 */
final class ReflectionResolver extends Resolver
{
    public function resolve($callable, array $args = [])
    {
        if (is_string($callable)) {
            $callable = $this->resolveString($callable);
        }
        if (is_string($callable)) {
            if (!function_exists($callable)) {
                $callable = $this->createClass($callable, $args);
            }
        } elseif (is_array($callable) && is_string($callable[0])) {
            list($class, $method) = $callable;
            $refMethod = new ReflectionMethod($class, $method);
            if (!$refMethod->isStatic()) {
                $class = $this->createClass($class, $args);
                $callable = [$class, $method];
            }
        }
        if (is_callable($callable)) {
            return $callable;
        }
        throw new RuntimeException('Invalid callable provided');
    }
    /**
     * Create a new class.
     *
     * @param string $class
     * @param array  $args
     *
     * @return object
     */
    private function createClass($class, $args = [])
    {
        if (!class_exists($class)) {
            throw new RuntimeException("The class {$class} does not exists");
        }
        $refClass = new ReflectionClass($class);
        if ($refClass->hasMethod('__construct')) {
            $instance = $refClass->newInstanceArgs($args);
        } else {
            $instance = $refClass->newInstance();
        }
        return $instance;
    }
}

?>