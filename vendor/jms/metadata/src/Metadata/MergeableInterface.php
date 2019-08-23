<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Metadata;

interface MergeableInterface
{
    /**
     * @param MergeableInterface $object
     *
     * @return void
     */
    public function merge(MergeableInterface $object);
}

?>