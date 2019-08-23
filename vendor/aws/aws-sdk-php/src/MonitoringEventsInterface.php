<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Aws;

/**
 * Interface for adding and retrieving client-side monitoring events
 */
interface MonitoringEventsInterface
{
    /**
     * Get client-side monitoring events attached to this object. Each event is
     * represented as an associative array within the returned array.
     *
     * @return array
     */
    public function getMonitoringEvents();
    /**
     * Prepend a client-side monitoring event to this object's event list
     *
     * @param array $event
     */
    public function prependMonitoringEvent(array $event);
    /**
     * Append a client-side monitoring event to this object's event list
     *
     * @param array $event
     */
    public function appendMonitoringEvent(array $event);
}

?>