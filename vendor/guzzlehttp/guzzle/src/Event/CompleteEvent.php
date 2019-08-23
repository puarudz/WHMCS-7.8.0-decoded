<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace GuzzleHttp\Event;

/**
 * Event object emitted after a request has been completed.
 *
 * This event MAY be emitted multiple times for a single request. You MAY
 * change the Response associated with the request using the intercept()
 * method of the event.
 *
 * This event allows the request to be retried if necessary using the retry()
 * method of the event.
 */
class CompleteEvent extends AbstractRetryableEvent
{
}

?>