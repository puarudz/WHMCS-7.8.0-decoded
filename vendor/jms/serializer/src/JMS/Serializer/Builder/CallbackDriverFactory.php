<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace JMS\Serializer\Builder;

use Doctrine\Common\Annotations\Reader;
use Metadata\Driver\DriverInterface;
class CallbackDriverFactory implements DriverFactoryInterface
{
    private $callback;
    /**
     * @param callable $callable
     */
    public function __construct($callable)
    {
        $this->callback = $callable;
    }
    public function createDriver(array $metadataDirs, Reader $reader)
    {
        $driver = call_user_func($this->callback, $metadataDirs, $reader);
        if (!$driver instanceof DriverInterface) {
            throw new \LogicException('The callback must return an instance of DriverInterface.');
        }
        return $driver;
    }
}

?>