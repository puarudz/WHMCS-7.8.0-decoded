<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Middlewares\Utils;

use Interop\Http\Factory\ResponseFactoryInterface;
/**
 * Simple class to create response instances of PSR-7 classes.
 */
class ResponseFactory implements ResponseFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createResponse($code = 200)
    {
        if (class_exists('Zend\\Diactoros\\Response')) {
            return new \Zend\Diactoros\Response('php://memory', $code);
        }
        if (class_exists('GuzzleHttp\\Psr7\\Response')) {
            return new \GuzzleHttp\Psr7\Response($code);
        }
        if (class_exists('Slim\\Http\\Response')) {
            return new \Slim\Http\Response($code);
        }
        throw new \RuntimeException('Unable to create a response. No PSR-7 stream library detected');
    }
}

?>