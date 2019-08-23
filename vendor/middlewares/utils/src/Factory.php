<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Middlewares\Utils;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Interop\Http\Factory\ResponseFactoryInterface;
use Interop\Http\Factory\StreamFactoryInterface;
use Interop\Http\Factory\UriFactoryInterface;
use Interop\Http\Factory\ServerRequestFactoryInterface;
/**
 * Simple class to create instances of PSR-7 classes.
 */
abstract class Factory
{
    /**
     * @var ResponseFactoryInterface
     */
    private static $responseFactory;
    /**
     * @var StreamFactoryInterface
     */
    private static $streamFactory;
    /**
     * @var UriFactoryInterface
     */
    private static $uriFactory;
    /**
     * @var ServerRequestFactoryInterface
     */
    private static $serverRequestFactory;
    /**
     * Set a custom ResponseFactory.
     *
     * @param ResponseFactoryInterface $responseFactory
     */
    public static function setResponseFactory(ResponseFactoryInterface $responseFactory)
    {
        self::$responseFactory = $responseFactory;
    }
    /**
     * Set a custom StreamFactory.
     *
     * @param StreamFactoryInterface $streamFactory
     */
    public static function setStreamFactory(StreamFactoryInterface $streamFactory)
    {
        self::$streamFactory = $streamFactory;
    }
    /**
     * Set a custom UriFactory.
     *
     * @param UriFactoryInterface $uriFactory
     */
    public static function setUriFactory(UriFactoryInterface $uriFactory)
    {
        self::$uriFactory = $uriFactory;
    }
    /**
     * Set a custom ServerRequestFactory.
     *
     * @param ServerRequestFactoryInterface $serverRequestFactory
     */
    public static function setServerRequestFactory(ServerRequestFactoryInterface $serverRequestFactory)
    {
        self::$serverRequestFactory = $serverRequestFactory;
    }
    /**
     * Creates a Response instance.
     *
     * @param int $code The status code
     *
     * @return ResponseInterface
     */
    public static function createResponse($code = 200)
    {
        if (self::$responseFactory === null) {
            self::$responseFactory = new ResponseFactory();
        }
        return self::$responseFactory->createResponse($code);
    }
    /**
     * Creates a Stream instance.
     *
     * @param resource $resource A resource returned by fopen
     *
     * @return StreamInterface
     */
    public static function createStream($resource = null)
    {
        if (self::$streamFactory === null) {
            self::$streamFactory = new StreamFactory();
        }
        if ($resource === null) {
            return self::$streamFactory->createStream();
        }
        return self::$streamFactory->createStreamFromResource($resource);
    }
    /**
     * Creates an Uri instance.
     *
     * @param string $uri
     *
     * @return UriInterface
     */
    public static function createUri($uri = '')
    {
        if (self::$uriFactory === null) {
            self::$uriFactory = new UriFactory();
        }
        return self::$uriFactory->createUri($uri);
    }
    /**
     * Creates a ServerRequest instance.
     *
     * @param array  $server
     * @param string $method
     * @param string $uri
     *
     * @return ServerRequest
     */
    public static function createServerRequest(array $server = [], $method = 'GET', $uri = '/')
    {
        if (self::$serverRequestFactory === null) {
            self::$serverRequestFactory = new ServerRequestFactory();
        }
        return self::$serverRequestFactory->createServerRequest($server, $method, $uri);
    }
}

?>