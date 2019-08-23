<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace GuzzleHttp\Event;

use GuzzleHttp\Message\ResponseInterface;
/**
 * Event object emitted before a request is sent.
 *
 * This event MAY be emitted multiple times (i.e., if a request is retried).
 * You MAY change the Response associated with the request using the
 * intercept() method of the event.
 */
class BeforeEvent extends AbstractRequestEvent
{
    /**
     * Intercept the request and associate a response
     *
     * @param ResponseInterface $response Response to set
     */
    public function intercept(ResponseInterface $response)
    {
        $this->transaction->response = $response;
        $this->transaction->exception = null;
        $this->stopPropagation();
    }
}

?>