<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace GuzzleHttp;

/**
 * An object that can be represented as an array
 */
interface ToArrayInterface
{
    /**
     * Get the array representation of an object
     *
     * @return array
     */
    public function toArray();
}

?>