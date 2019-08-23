<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Knp\Menu\Iterator;

/**
 * Filter iterator keeping only current items
 */
class DisplayedItemFilterIterator extends \RecursiveFilterIterator
{
    public function accept()
    {
        return $this->current()->isDisplayed();
    }
    public function hasChildren()
    {
        return $this->current()->getDisplayChildren() && parent::hasChildren();
    }
}

?>