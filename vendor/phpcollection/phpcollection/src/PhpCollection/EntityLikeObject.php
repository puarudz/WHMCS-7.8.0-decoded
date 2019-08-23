<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace PhpCollection;

/**
 * Implementation for ObjectBasics for entity-like objects.
 *
 * Two objects are considered equal if they refer to the same instance.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
trait EntityLikeObject
{
    public function hash()
    {
        return spl_object_hash($this);
    }
    public function equals(ObjectBasics $other)
    {
        return $this === $other;
    }
}

?>