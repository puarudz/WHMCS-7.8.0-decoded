<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Aws\ClientSideMonitoring;

/**
 * Provides access to client-side monitoring configuration options:
 * 'client_id', 'enabled', 'port'
 */
interface ConfigurationInterface
{
    /**
     * Checks whether or not client-side monitoring is enabled.
     *
     * @return bool
     */
    public function isEnabled();
    /**
     * Returns the Client ID, if available.
     *
     * @return string|null
     */
    public function getClientId();
    /**
     * Returns the configured port.
     *
     * @return int|null
     */
    public function getPort();
    /**
     * Returns the configuration as an associative array.
     *
     * @return array
     */
    public function toArray();
}

?>