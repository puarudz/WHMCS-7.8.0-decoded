<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\View\Menu;

class Item extends \Knp\Menu\MenuItem
{
    protected $badge = "";
    protected $order = NULL;
    protected $disabled = false;
    protected $icon = "";
    protected $headingHtml = NULL;
    protected $bodyHtml = NULL;
    protected $footerHtml = NULL;
    public function getName()
    {
        return parent::getName();
    }
    public function setName($name)
    {
        return parent::setName($name);
    }
    public function getUri()
    {
        return parent::getUri();
    }
    public function setUri($uri)
    {
        if (!$uri) {
            return $this;
        }
        if (filter_var($uri, FILTER_VALIDATE_URL) === false) {
            $base = \WHMCS\Utility\Environment\WebHelper::getBaseUrl(ROOTDIR, $_SERVER["SCRIPT_NAME"]);
            if (empty($base) || strpos($uri, $base) !== 0) {
                $uri = \WHMCS\Utility\Environment\WebHelper::getBaseUrl(ROOTDIR, $_SERVER["SCRIPT_NAME"]) . "/" . $uri;
            }
            $uri = preg_replace("/\\/+/", "/", $uri);
        }
        $this->uri = $uri;
        return $this;
    }
    public function getLabel()
    {
        return parent::getLabel();
    }
    public function setLabel($label)
    {
        return parent::setLabel($label);
    }
    public function addChild($child, array $options = array())
    {
        if ($child instanceof \WHMCS\View\Client\HomepagePanel || $child instanceof \WHMCS\MarketConnect\Promotion\LoginPanel) {
            return parent::addChild($child->getName(), $child->toArray());
        }
        return parent::addChild($child, $options);
    }
    public function getChild($name)
    {
        return parent::getChild($name);
    }
    public function copy()
    {
        return parent::copy();
    }
    public function getLevel()
    {
        return parent::getLevel();
    }
    public function getRoot()
    {
        return parent::getRoot();
    }
    public function isRoot()
    {
        return parent::isRoot();
    }
    public function getParent()
    {
        return $this->parent;
    }
    public function getChildren()
    {
        return parent::getChildren();
    }
    public function setChildren(array $children)
    {
        return parent::setChildren($children);
    }
    public function removeChild($name)
    {
        return parent::removeChild($name);
    }
    public function hasChildren()
    {
        return parent::hasChildren();
    }
    public function setBadge($badge)
    {
        $this->badge = trim($badge);
        return $this;
    }
    public function getBadge()
    {
        return $this->badge;
    }
    public function hasBadge()
    {
        return $this->badge !== "";
    }
    public function setOrder($order)
    {
        $this->order = (int) $order;
        return $this;
    }
    public function getOrder()
    {
        return $this->order;
    }
    public function setClass($cssClassString)
    {
        $this->attributes["class"] = $cssClassString;
        return $this;
    }
    public function getClass()
    {
        return isset($this->attributes["class"]) ? $this->attributes["class"] : "";
    }
    public function disable()
    {
        $this->disabled = true;
        return $this;
    }
    public function enable()
    {
        $this->disabled = false;
        return $this;
    }
    public function isDisabled()
    {
        return isset($this->disabled) ? $this->disabled : false;
    }
    public function getExtras()
    {
        return parent::getExtras();
    }
    public function getExtra($name, $default = NULL)
    {
        $extra = parent::getExtra($name, $default);
        if ($name === "btn-icon" && $extra) {
            $iconClasses = array_diff(preg_split("/\\s+/", strtolower($extra)), array("fa"));
            if (count(array_intersect($iconClasses, array("fas", "far", "fal", "fab"))) === 0) {
                $iconClasses = array_merge(array("fas"), $iconClasses);
            }
            $extra = implode(" ", $iconClasses);
        }
        return $extra;
    }
    public function setExtras(array $extras)
    {
        return parent::setExtras($extras);
    }
    public function setExtra($name, $value)
    {
        return parent::setExtra($name, $value);
    }
    protected function isFontAwesomeIcon($icon)
    {
        $iconClass = preg_replace("/^(fas|far|fal|fab|fa)\\s+/", "", trim($icon));
        return substr($iconClass, 0, 3) == "fa-";
    }
    protected function isGlyphicon($icon)
    {
        return substr($icon, 0, 10) == "glyphicon-";
    }
    public function setIcon($icon)
    {
        $icon = trim($icon);
        if ($icon != "" && !$this->isFontAwesomeIcon($icon) && !$this->isGlyphicon($icon)) {
            throw new \WHMCS\Exception("Please provide either a Font Awesome or Glyphicon.");
        }
        $this->icon = $icon;
        return $this;
    }
    public function getIcon()
    {
        $icon = "";
        if ($this->hasFontAwesomeIcon()) {
            $icon = trim($this->icon);
            $classes = preg_split("/\\s+/", $icon);
            $classes = array_map(function ($class) {
                return $class === "fa" ? "fas" : $class;
            }, $classes);
            if (count(array_intersect($classes, array("fas", "far", "fal", "fab"))) === 0) {
                array_unshift($classes, "fas");
            }
            $icon = implode(" ", $classes);
        } else {
            if ($this->hasGlyphicon()) {
                $icon = "glyphicon " . $this->icon;
            }
        }
        return $icon;
    }
    public function hasIcon()
    {
        return $this->icon !== "";
    }
    public function hasFontAwesomeIcon()
    {
        return $this->hasIcon() && $this->isFontAwesomeIcon($this->icon);
    }
    public function hasGlyphicon()
    {
        return $this->hasIcon() && $this->isGlyphicon($this->icon);
    }
    public function getBodyHtml()
    {
        return $this->bodyHtml;
    }
    public function setBodyHtml($html)
    {
        $this->bodyHtml = $html;
        return $this;
    }
    public function hasBodyHtml()
    {
        return $this->bodyHtml != "";
    }
    public function getFooterHtml()
    {
        return $this->footerHtml;
    }
    public function setFooterHtml($html)
    {
        $this->footerHtml = $html;
        return $this;
    }
    public function hasFooterHtml()
    {
        return $this->footerHtml != "";
    }
    public function getHeadingHtml()
    {
        return $this->headingHtml;
    }
    public function setHeadingHtml($html)
    {
        $this->headingHtml = $html;
        return $this;
    }
    public function hasHeadingHtml()
    {
        return $this->headingHtml != "";
    }
    public function getId()
    {
        $parentId = "";
        if (!is_null($this->getParent())) {
            $parentId = $this->getParent()->getId() . "-";
        }
        return $parentId . str_replace(array(" ", "/"), "_", $this->getName());
    }
    public static function sort(Item $menu, $sortChildren = true)
    {
        $children = $menu->getChildren();
        if ($sortChildren) {
            foreach ($children as $i => $child) {
                $children[$i] = static::sort($child);
            }
        }
        uasort($children, function (Item $a, Item $b) {
            $aOrder = $a->getOrder();
            $bOrder = $b->getOrder();
            if ($aOrder == $bOrder) {
                return $b->getName() < $a->getName() ? 1 : -1;
            }
            return $bOrder < $aOrder ? 1 : -1;
        });
        $menu->setChildren($children);
        return $menu;
    }
    protected function swapOrder($swapOrder)
    {
        $parent = $this->getParent();
        static::sort($parent, false);
        $siblings = $parent->getChildren();
        reset($siblings);
        $swapItem = null;
        $key = 0;
        $iterator = new \ArrayIterator(array_values($siblings));
        while ($iterator->valid()) {
            $name = $iterator->current()->getName();
            $key = $iterator->key();
            if ($name == $this->getName()) {
                break;
            }
            $iterator->next();
        }
        if ($swapOrder == "up") {
            $key -= 1;
        } else {
            $key += 1;
        }
        if (0 <= $key && $iterator->offsetExists($key)) {
            $swapItem = $iterator->offsetGet($key);
        }
        if ($swapItem) {
            $swapItemOrder = $swapItem->getOrder();
            $thisItemOrder = $this->getOrder();
            if ($swapItemOrder != $thisItemOrder) {
                $swapItem->setOrder($thisItemOrder);
                $this->setOrder($swapItemOrder);
            } else {
                if ($swapOrder == "up") {
                    $thisItemOrder = $swapItemOrder - 1;
                } else {
                    $thisItemOrder = $swapItemOrder + 1;
                }
                $this->setOrder($thisItemOrder);
            }
            $this->getParent()->removeChild($swapItem->getName());
            $this->getParent()->addChild($swapItem);
        }
        return $this;
    }
    public function moveUp()
    {
        return $this->swapOrder("up");
    }
    public function moveDown()
    {
        return $this->swapOrder("down");
    }
    public function moveToFront()
    {
        static::sort($this->getParent(), false);
        $maxCycles = 1000;
        while (!$this->isFirst() && $maxCycles--) {
            $this->moveUp();
        }
        return $this;
    }
    public function moveToBack()
    {
        static::sort($this->getParent(), false);
        $maxCycles = 1000;
        while (!$this->isLast() && $maxCycles--) {
            $this->moveDown();
        }
        return $this;
    }
}

?>