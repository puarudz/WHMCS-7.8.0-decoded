<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\ApplicationSupport\View\Html\Helper;

class TabbedContent implements \WHMCS\View\HtmlPageInterface, \Countable
{
    protected $activeTabId = 0;
    protected $firstTabOpenByDefault = false;
    protected $tabs = NULL;
    protected $tabPrefix = "";
    public function __construct($tabPrefix = "", $firstTabOpenByDefault = false, \SplDoublyLinkedList $dll = NULL)
    {
        if (is_null($dll)) {
            $dll = new \SplDoublyLinkedList();
        }
        if ($dll->isEmpty()) {
            $dll->add(0, "");
        }
        $this->tabs = $dll;
        $this->setFirstTabOpenByDefault($firstTabOpenByDefault);
    }
    public function isFirstTabOpenByDefault()
    {
        return $this->firstTabOpenByDefault;
    }
    public function setFirstTabOpenByDefault($firstTabOpenByDefault)
    {
        $this->firstTabOpenByDefault = $firstTabOpenByDefault;
        return $this;
    }
    public function getTabPrefix()
    {
        return $this->tabPrefix;
    }
    public function setTabPrefix($tabPrefix)
    {
        $this->tabPrefix = $tabPrefix;
        return $this;
    }
    public function addTabContent($label, $content, $addTabParamToUrls = true, $id = NULL)
    {
        $dll = $this->tabs;
        if (is_null($id)) {
            $id = $dll->count();
        }
        $id = (int) $id;
        $tab = new \stdClass();
        $tab->label = $label;
        if ($addTabParamToUrls) {
            $content = $this->addTabParamToUrls($id, $content);
        }
        $tab->content = $content;
        $dll->add($id, $tab);
        return $this;
    }
    public function addTabParamToUrls($id, $content)
    {
        return preg_replace(array("/( href=\")([^\\?#\"]+)\\?([^#\"]+)#([^\"]+)\"([\\s>\\/])/", "/( href=\")([^\\?#\"]+)#([^\"]+)\"([\\s>\\/])/i", "/( href=\")([^\\?\"]+)\\?([^\"]+)\"([\\s>\\/])/", "/( href=\")([^\\?#\"]+)\"([\\s>\\/])/i"), array("\$1\$2?tab=" . $id . "&\$3#\$4\"\$5", "\$1\$2?tab=" . $id . "#\$3\"\$4", "\$1\$2?tab=" . $id . "&\$3\"\$4", "\$1\$2?tab=" . $id . "\"\$3"), $content);
    }
    public function count()
    {
        return $this->tabs->count();
    }
    public function getFormattedHeaderContent()
    {
        return "";
    }
    public function buildHtmlList()
    {
        $tabPrefix = $this->getTabPrefix();
        $tabs = $this->tabs;
        $tabs->rewind();
        $listItems = array();
        foreach ($tabs as $key => $tab) {
            if (!$tab instanceof \stdClass) {
                continue;
            }
            $activeClassAttribute = $this->isActiveTab($key) ? "class=\"active\"" : "";
            $tabIdAttribute = "#tab" . $tabPrefix . $key;
            $listItems[] = sprintf("<li %s>" . "<a class=\"tab-top\" href=\"%s\" role=\"tab\" data-toggle=\"tab\" id=\"%s\" data-tab-id=\"%s\">%s" . "</a></li>" . PHP_EOL, $activeClassAttribute, $tabIdAttribute, "tabLink" . $key, $key, $tab->label);
        }
        return sprintf("<ul class=\"nav nav-tabs admin-tabs\" role=\"tablist\">" . PHP_EOL . "%s" . "</ul>" . PHP_EOL, implode(PHP_EOL, $listItems));
    }
    public function setActiveTabId($id = 0)
    {
        $this->activeTabId = $id;
        return $this;
    }
    public function getActiveTabId()
    {
        return $this->activeTabId;
    }
    public function buildHtmlContentContainer()
    {
        $containers = array();
        $tabs = $this->tabs;
        $tabs->rewind();
        $tabPrefix = $this->getTabPrefix();
        foreach ($tabs as $key => $tab) {
            if (!$tab instanceof \stdClass) {
                continue;
            }
            $tabIdAttribute = "tab" . $tabPrefix . $key;
            $activeClassAttribute = $this->isActiveTab($key) ? "active" : "";
            $containers[] = sprintf("<div class=\"tab-pane %s\" id=\"%s\"" . PHP_EOL . " >%s</div>", $activeClassAttribute, $tabIdAttribute, $tab->content);
        }
        return sprintf("<div class=\"tab-content admin-tabs\">" . PHP_EOL . "%s" . PHP_EOL . "</div>", implode(PHP_EOL, $containers));
    }
    public function getJQueryCode()
    {
        $tabPrefix = $this->getTabPrefix();
        $selectedTab = $this->getActiveTabId();
        $additionalJQueryCode = "";
        if (3 <= $this->tabs->count()) {
            $additionalJQueryCode .= "\$(\".admin-tabs\").tabdrop(); \$(window).resize();";
        }
        $additionalJQueryCode .= "\$( \"a.tab-top\" ).click( function() {\n    var tabId = \$(this).data('tab-id');\n    \$(\"#tab" . $tabPrefix . "\").val(tabId);\n    window.location.hash = 'tab=" . $tabPrefix . "' + tabId;\n});\n\nvar selectedTab = " . $selectedTab . ";\n\nif (selectedTab == 0) {\n    refreshedTab = window.location.hash;\n    if (refreshedTab) {\n        refreshedTab = refreshedTab.substring(5);\n        \$(\"a[href='#tab" . $tabPrefix . "\" + refreshedTab + \"']\").click();\n    }\n}\n";
        if (!$this->isFirstTabOpenByDefault()) {
            $tabPrefix = $this->tabPrefix;
            $idPosition = strlen("#tab" . $tabPrefix);
            $additionalJQueryCode .= "/**\n * We want to make the adminTabs on this page toggle\n */\n\$( \"a[href^='#tab" . $tabPrefix . "']\" ).click( function() {\n    var tabID = \$(this).attr('href').substr(" . $idPosition . ");\n    var tabToHide = \$(\"#tab" . $tabPrefix . "\" + tabID);\n    if(tabToHide.hasClass('active')) {\n        tabToHide.removeClass('active');\n    }  else {\n        tabToHide.addClass('active')\n    }\n});";
        }
        return $additionalJQueryCode;
    }
    public function getFormattedHtmlHeadContent()
    {
        if (3 <= $this->tabs->count()) {
            return \WHMCS\View\Asset::jsInclude("bootstrap-tabdrop.js") . PHP_EOL . \WHMCS\View\Asset::cssInclude("tabdrop.css");
        }
        return "";
    }
    public function getFormattedBodyContent()
    {
        return $this->buildHtmlList() . $this->buildHtmlContentContainer();
    }
    public function getFormattedFooterContent()
    {
        return "";
    }
    protected function isActiveTab($key)
    {
        if ($this->getActiveTabId() && $key == $this->getActiveTabId()) {
            return true;
        }
        if ($this->isFirstTabOpenByDefault() && $key == 1) {
            return true;
        }
        return false;
    }
}

?>