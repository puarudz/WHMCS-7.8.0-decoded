<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Aws\ClientSideMonitoring;

class Configuration implements ConfigurationInterface
{
    private $clientId;
    private $enabled;
    private $port;
    /**
     * Constructs a new Configuration object with the specified CSM options set.
     *
     * @param mixed $enabled
     * @param string|int $port
     * @param string $clientId
     */
    public function __construct($enabled, $port, $clientId = '')
    {
        $this->port = filter_var($port, FILTER_VALIDATE_INT);
        if ($this->port === false) {
            throw new \InvalidArgumentException("CSM 'port' value must be an integer!");
        }
        // Unparsable $enabled flag errors on the side of disabling CSM
        $this->enabled = filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
        $this->clientId = trim($clientId);
    }
    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return $this->enabled;
    }
    /**
     * {@inheritdoc}
     */
    public function getClientId()
    {
        return $this->clientId;
    }
    /**
     * {@inheritdoc}
     */
    public function getPort()
    {
        return $this->port;
    }
    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return ['client_id' => $this->getClientId(), 'enabled' => $this->isEnabled(), 'port' => $this->getPort()];
    }
}

?>