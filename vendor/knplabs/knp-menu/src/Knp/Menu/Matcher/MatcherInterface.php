<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Knp\Menu\Matcher;

use Knp\Menu\ItemInterface;
/**
 * Interface implemented by the item matcher
 */
interface MatcherInterface
{
    /**
     * Checks whether an item is current.
     *
     * @param ItemInterface $item
     *
     * @return boolean
     */
    public function isCurrent(ItemInterface $item);
    /**
     * Checks whether an item is the ancestor of a current item.
     *
     * @param ItemInterface $item
     * @param integer       $depth The max depth to look for the item
     *
     * @return boolean
     */
    public function isAncestor(ItemInterface $item, $depth = null);
    /**
     * Clears the state of the matcher.
     */
    public function clear();
}

?>