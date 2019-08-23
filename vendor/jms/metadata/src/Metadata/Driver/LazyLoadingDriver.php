<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Metadata\Driver;

use Symfony\Component\DependencyInjection\ContainerInterface;
class LazyLoadingDriver implements DriverInterface
{
    private $container;
    private $realDriverId;
    public function __construct(ContainerInterface $container, $realDriverId)
    {
        $this->container = $container;
        $this->realDriverId = $realDriverId;
    }
    /**
     * {@inheritDoc}
     */
    public function loadMetadataForClass(\ReflectionClass $class)
    {
        return $this->container->get($this->realDriverId)->loadMetadataForClass($class);
    }
}

?>