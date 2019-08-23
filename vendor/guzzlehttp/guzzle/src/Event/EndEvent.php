<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace GuzzleHttp\Event;

/**
 * A terminal event that is emitted when a request transaction has ended.
 *
 * This event is emitted for both successful responses and responses that
 * encountered an exception. You need to check if an exception is present
 * in your listener to know the difference.
 *
 * You MAY intercept the response associated with the event if needed, but keep
 * in mind that the "complete" event will not be triggered as a result.
 */
class EndEvent extends AbstractTransferEvent
{
    /**
     * Get the exception that was encountered (if any).
     *
     * This method should be used to check if the request was sent successfully
     * or if it encountered errors.
     *
     * @return \Exception|null
     */
    public function getException()
    {
        return $this->transaction->exception;
    }
}

?>