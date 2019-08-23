<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Knp\Menu\Matcher;

use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\Voter\VoterInterface;
/**
 * A MatcherInterface implementation using a voter system
 */
class Matcher implements MatcherInterface
{
    private $cache;
    /**
     * @var VoterInterface[]
     */
    private $voters = array();
    public function __construct()
    {
        $this->cache = new \SplObjectStorage();
    }
    /**
     * Adds a voter in the matcher.
     *
     * @param VoterInterface $voter
     */
    public function addVoter(VoterInterface $voter)
    {
        $this->voters[] = $voter;
    }
    public function isCurrent(ItemInterface $item)
    {
        $current = $item->isCurrent();
        if (null !== $current) {
            return $current;
        }
        if ($this->cache->contains($item)) {
            return $this->cache[$item];
        }
        foreach ($this->voters as $voter) {
            $current = $voter->matchItem($item);
            if (null !== $current) {
                break;
            }
        }
        $current = (bool) $current;
        $this->cache[$item] = $current;
        return $current;
    }
    public function isAncestor(ItemInterface $item, $depth = null)
    {
        if (0 === $depth) {
            return false;
        }
        $childDepth = null === $depth ? null : $depth - 1;
        foreach ($item->getChildren() as $child) {
            if ($this->isCurrent($child) || $this->isAncestor($child, $childDepth)) {
                return true;
            }
        }
        return false;
    }
    public function clear()
    {
        $this->cache = new \SplObjectStorage();
    }
}

?>