<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Middlewares\Utils;

use Psr\Http\Message\ResponseInterface;
/**
 * Simple class to execute callables and returns responses.
 */
abstract class CallableHandler
{
    /**
     * Execute the callable.
     *
     * @param callable $callable
     * @param array    $arguments
     *
     * @return ResponseInterface
     */
    public static function execute($callable, array $arguments = [])
    {
        ob_start();
        $level = ob_get_level();
        try {
            $return = call_user_func_array($callable, $arguments);
            if ($return instanceof ResponseInterface) {
                $response = $return;
                $return = '';
            } else {
                $response = Factory::createResponse();
            }
            while (ob_get_level() >= $level) {
                $return = ob_get_clean() . $return;
            }
            $body = $response->getBody();
            if ($return !== '' && $body->isWritable()) {
                $body->write($return);
            }
            return $response;
        } catch (\Exception $exception) {
            while (ob_get_level() >= $level) {
                ob_end_clean();
            }
            throw $exception;
        }
    }
}

?>