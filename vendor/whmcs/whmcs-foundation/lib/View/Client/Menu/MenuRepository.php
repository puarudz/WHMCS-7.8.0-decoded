<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\View\Client\Menu;

class MenuRepository
{
    protected $primaryNavbar = NULL;
    protected $secondaryNavbar = NULL;
    protected $primarySidebar = NULL;
    protected $secondarySidebar = NULL;
    protected $primaryNavbarFactory = NULL;
    protected $secondaryNavbarFactory = NULL;
    protected $primarySidebarFactory = NULL;
    protected $secondarySidebarFactory = NULL;
    protected $context = array();
    public function __construct(PrimaryNavbarFactory $primaryNavbarFactory, SecondaryNavbarFactory $secondaryNavbarFactory, PrimarySidebarFactory $primarySidebarFactory, SecondarySidebarFactory $secondarySidebarFactory)
    {
        $this->setPrimaryNavbarFactory($primaryNavbarFactory)->setSecondaryNavbarFactory($secondaryNavbarFactory)->setPrimarySidebarFactory($primarySidebarFactory)->setSecondarySidebarFactory($secondarySidebarFactory);
    }
    protected function setPrimaryNavbarFactory(PrimaryNavbarFactory $navbarFactory)
    {
        $this->primaryNavbarFactory = $navbarFactory;
        return $this;
    }
    protected function setSecondaryNavbarFactory(SecondaryNavbarFactory $navbarFactory)
    {
        $this->secondaryNavbarFactory = $navbarFactory;
        return $this;
    }
    protected function setPrimarySidebarFactory(PrimarySidebarFactory $primarySidebarFactory)
    {
        $this->primarySidebarFactory = $primarySidebarFactory;
        return $this;
    }
    protected function setSecondarySidebarFactory(SecondarySidebarFactory $secondarySidebarFactory)
    {
        $this->secondarySidebarFactory = $secondarySidebarFactory;
        return $this;
    }
    protected function buildSidebar($sidebar, $type = NULL)
    {
        if (!in_array($sidebar, array("primarySidebar", "secondarySidebar"))) {
            throw new \WHMCS\Exception("Unknown sidebar type \"" . $sidebar . "\".");
        }
        $factoryProperty = $sidebar . "Factory";
        if (!is_null($type)) {
            if (method_exists($this->{$factoryProperty}, $type)) {
                $this->{$sidebar} = $this->{$factoryProperty}->{$type}();
            } else {
                throw new \WHMCS\Exception("Unknown sidebar \"" . $type . "\".");
            }
        }
        if (is_null($this->{$sidebar})) {
            $this->{$sidebar} = $this->{$factoryProperty}->emptySidebar();
        }
        return $this->{$sidebar};
    }
    public function primaryNavbar($firstName = "", array $conditionalLinks = array())
    {
        if (is_null($this->primaryNavbar)) {
            $this->primaryNavbar = $this->primaryNavbarFactory->navbar($firstName, $conditionalLinks);
        }
        return $this->primaryNavbar;
    }
    public function secondaryNavbar($firstName = "", array $conditionalLinks = array())
    {
        if (is_null($this->secondaryNavbar)) {
            $this->secondaryNavbar = $this->secondaryNavbarFactory->navbar($firstName, $conditionalLinks);
        }
        return $this->secondaryNavbar;
    }
    public function primarySidebar($type = NULL)
    {
        return $this->buildSidebar("primarySidebar", $type);
    }
    public function secondarySidebar($type = NULL)
    {
        return $this->buildSidebar("secondarySidebar", $type);
    }
    public function addContext($key, $value)
    {
        $this->context[$key] = $value;
    }
    public function context($key = NULL)
    {
        if (is_null($key)) {
            return new \Illuminate\Support\Collection($this->context);
        }
        return isset($this->context[$key]) ? $this->context[$key] : null;
    }
    public function removeEmptyChildren(\WHMCS\View\Menu\Item $menuBar)
    {
        $childMenus = $menuBar->getChildren();
        foreach ($childMenus as $childMenu) {
            if (!$childMenu->hasChildren() && !$childMenu->hasBodyHtml() && !$childMenu->hasFooterHtml()) {
                $menuBar->removeChild($childMenu->getName());
            }
        }
    }
}

?>