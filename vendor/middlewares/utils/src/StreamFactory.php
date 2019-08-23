<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Middlewares\Utils;

use Interop\Http\Factory\StreamFactoryInterface;
/**
 * Simple class to create instances of PSR-7 streams.
 */
class StreamFactory implements StreamFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createStream($content = '')
    {
        $stream = $this->createStreamFromFile('php://temp', 'r+');
        $stream->write($content);
        return $stream;
    }
    /**
     * {@inheritdoc}
     */
    public function createStreamFromFile($file, $mode = 'r')
    {
        return $this->createStreamFromResource(fopen($file, $mode));
    }
    /**
     * {@inheritdoc}
     */
    public function createStreamFromResource($resource)
    {
        if (class_exists('Zend\\Diactoros\\Stream')) {
            return new \Zend\Diactoros\Stream($resource);
        }
        if (class_exists('GuzzleHttp\\Psr7\\Stream')) {
            return new \GuzzleHttp\Psr7\Stream($resource);
        }
        if (class_exists('Slim\\Http\\Stream')) {
            return new \Slim\Http\Stream($resource);
        }
        throw new \RuntimeException('Unable to create a stream. No PSR-7 stream library detected');
    }
}

?>