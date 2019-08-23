<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Knp\Menu\Iterator;

/**
 * Recursive iterator iterating on an item
 */
class RecursiveItemIterator extends \IteratorIterator implements \RecursiveIterator
{
    public function hasChildren()
    {
        return 0 < count($this->current());
    }
    public function getChildren()
    {
        return new static($this->current());
    }
}

?>