<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace GuzzleHttp\Event;

use GuzzleHttp\Exception\RequestException;
/**
 * Event emitted when an error occurs while sending a request.
 *
 * This event MAY be emitted multiple times. You MAY intercept the exception
 * and inject a response into the event to rescue the request using the
 * intercept() method of the event.
 *
 * This event allows the request to be retried using the "retry" method of the
 * event.
 */
class ErrorEvent extends AbstractRetryableEvent
{
    /**
     * Get the exception that was encountered
     *
     * @return RequestException
     */
    public function getException()
    {
        return $this->transaction->exception;
    }
}

?>