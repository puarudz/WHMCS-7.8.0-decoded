<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Interop\Http\Factory;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
interface ServerRequestFactoryInterface
{
    /**
     * Create a new server request.
     *
     * The method and URI of the request SHOULD be derived from the given
     * server parameters.
     *
     * If the method or URI parameter is passed, these values MUST be used
     * instead of the server parameters.
     *
     * @param array $server
     * @param string|null $method
     * @param UriInterface|string|null $uri
     *
     * @throws \InvalidArgumentException
     *  If no valid method or URI can be determined.
     *
     * @return ServerRequestInterface
     */
    public function createServerRequest(array $server, $method = null, $uri = null);
}

?>