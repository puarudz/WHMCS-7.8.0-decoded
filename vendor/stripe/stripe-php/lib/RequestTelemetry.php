<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Stripe;

/**
 * Class RequestTelemetry
 *
 * Tracks client request telemetry
 * @package Stripe
 */
class RequestTelemetry
{
    public $requestId;
    public $requestDuration;
    /**
     * Initialize a new telemetry object.
     *
     * @param string $requestId The request's request ID.
     * @param int $requestDuration The request's duration in milliseconds.
     */
    public function __construct($requestId, $requestDuration)
    {
        $this->requestId = $requestId;
        $this->requestDuration = $requestDuration;
    }
}

?>