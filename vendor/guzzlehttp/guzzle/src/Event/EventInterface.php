<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace GuzzleHttp\Event;

/**
 * Base event interface used when dispatching events to listeners using an
 * event emitter.
 */
interface EventInterface
{
    /**
     * Returns whether or not stopPropagation was called on the event.
     *
     * @return bool
     * @see Event::stopPropagation
     */
    public function isPropagationStopped();
    /**
     * Stops the propagation of the event, preventing subsequent listeners
     * registered to the same event from being invoked.
     */
    public function stopPropagation();
}

?>