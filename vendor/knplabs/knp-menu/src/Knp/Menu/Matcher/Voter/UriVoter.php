<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Knp\Menu\Matcher\Voter;

use Knp\Menu\ItemInterface;
/**
 * Voter based on the uri
 */
class UriVoter implements VoterInterface
{
    private $uri;
    public function __construct($uri = null)
    {
        $this->uri = $uri;
    }
    public function matchItem(ItemInterface $item)
    {
        if (null === $this->uri || null === $item->getUri()) {
            return null;
        }
        if ($item->getUri() === $this->uri) {
            return true;
        }
        return null;
    }
}

?>