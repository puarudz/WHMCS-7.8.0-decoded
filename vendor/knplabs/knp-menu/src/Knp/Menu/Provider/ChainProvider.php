<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Knp\Menu\Provider;

class ChainProvider implements MenuProviderInterface
{
    /**
     * @var MenuProviderInterface[]
     */
    private $providers;
    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }
    public function get($name, array $options = array())
    {
        foreach ($this->providers as $provider) {
            if ($provider->has($name, $options)) {
                return $provider->get($name, $options);
            }
        }
        throw new \InvalidArgumentException(sprintf('The menu "%s" is not defined.', $name));
    }
    public function has($name, array $options = array())
    {
        foreach ($this->providers as $provider) {
            if ($provider->has($name, $options)) {
                return true;
            }
        }
        return false;
    }
}

?>