<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace OAuth2;

/**
 * Interface which represents an object response.  Meant to handle and display the proper OAuth2 Responses
 * for errors and successes
 *
 * @see OAuth2\Response
 */
interface ResponseInterface
{
    public function addParameters(array $parameters);
    public function addHttpHeaders(array $httpHeaders);
    public function setStatusCode($statusCode);
    public function setError($statusCode, $name, $description = null, $uri = null);
    public function setRedirect($statusCode, $url, $state = null, $error = null, $errorDescription = null, $errorUri = null);
    public function getParameter($name);
}

?>