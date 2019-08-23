<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Knp\Menu\Iterator;

use Knp\Menu\Matcher\MatcherInterface;
/**
 * Filter iterator keeping only current items
 */
class CurrentItemFilterIterator extends \FilterIterator
{
    private $matcher;
    public function __construct(\Iterator $iterator, MatcherInterface $matcher)
    {
        $this->matcher = $matcher;
        parent::__construct($iterator);
    }
    public function accept()
    {
        return $this->matcher->isCurrent($this->current());
    }
}

?>