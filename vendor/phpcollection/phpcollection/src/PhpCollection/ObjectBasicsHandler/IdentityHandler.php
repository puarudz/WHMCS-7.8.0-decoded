<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace PhpCollection\ObjectBasicsHandler;

use PhpCollection\ObjectBasicsHandler;
class IdentityHandler implements ObjectBasicsHandler
{
    public function hash($object)
    {
        return spl_object_hash($object);
    }
    public function equals($a, $b)
    {
        return $a === $b;
    }
}

?>