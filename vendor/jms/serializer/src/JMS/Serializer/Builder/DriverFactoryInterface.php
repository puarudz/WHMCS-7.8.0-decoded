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
interface DriverFactoryInterface
{
    /**
     * @param array $metadataDirs
     * @param Reader $annotationReader
     *
     * @return DriverInterface
     */
    public function createDriver(array $metadataDirs, Reader $annotationReader);
}

?>