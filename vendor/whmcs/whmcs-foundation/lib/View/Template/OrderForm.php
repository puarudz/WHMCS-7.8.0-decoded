<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\View\Template;

class OrderForm extends \WHMCS\View\Template
{
    protected static $setting = "OrderFormTemplate";
    protected static $defaultTemplate = "base";
    protected static $templateDirectory = "templates/orderforms";
    protected static $ignoredTemplateDirectories = array();
    protected $children = NULL;
    protected $parent = NULL;
    public static function find($name)
    {
        return parent::find($name);
    }
    public static function all()
    {
        return parent::all();
    }
    public static function factory($requestedTemplateFile = NULL, $systemTemplateName = NULL, $sessionTemplateName = NULL, $requestTemplateName = NULL)
    {
        $template = parent::factory($systemTemplateName, $sessionTemplateName, $requestTemplateName);
        if (is_null($requestedTemplateFile)) {
            return $template;
        }
        while (!is_null($template)) {
            if (file_exists(ROOTDIR . DIRECTORY_SEPARATOR . static::$templateDirectory . DIRECTORY_SEPARATOR . $template->getName() . DIRECTORY_SEPARATOR . $requestedTemplateFile)) {
                return $template;
            }
            $template = $template->getParent();
        }
        throw new \WHMCS\Exception\View\TemplateNotFound();
    }
    protected function buildParent()
    {
        $config = $this->getConfig()->getConfig();
        foreach (static::all() as $template) {
            if (isset($config["parent"]) && $template->getName() == $config["parent"]) {
                $this->parent = $template;
                break;
            }
        }
        if (is_null($this->parent) && $this->name != static::$defaultTemplate) {
            $this->parent = static::find(static::$defaultTemplate);
        }
        return $this;
    }
    public function getParent()
    {
        if (is_null($this->parent)) {
            $this->buildParent();
        }
        return $this->parent;
    }
    public function isRoot()
    {
        return is_null($this->getParent());
    }
    public function getChildren()
    {
        if (is_null($this->children)) {
            $this->buildChildren();
        }
        return $this->children;
    }
    protected function buildChildren()
    {
        $children = array();
        foreach (static::all() as $template) {
            $config = $template->getConfig()->getConfig();
            if (isset($config["parent"]) && $config["parent"] == $this->name) {
                $children[] = $template;
            }
        }
        $this->children = new \Illuminate\Support\Collection($children);
        return $this;
    }
    public function getChild($name)
    {
        foreach ($this->children as $child) {
            if ($child->getName() == $name) {
                return $child;
            }
        }
        return null;
    }
    public function hasChild($name)
    {
        return !is_null($this->getChild($name));
    }
    public function productGroups()
    {
        return \WHMCS\Product\Group::orderBy("order")->where("orderfrmtpl", $this->getName())->get();
    }
    public function getTemplatePath()
    {
        return ROOTDIR . DIRECTORY_SEPARATOR . static::$templateDirectory . DIRECTORY_SEPARATOR . $this->getName() . DIRECTORY_SEPARATOR;
    }
    public function hasTemplate($template, $checkParent = true)
    {
        $parentTemplate = $this->getParent();
        $parentCheck = false;
        if ($parentTemplate && $checkParent) {
            $parentCheck = $this->getParent()->hasTemplate($template, false);
        }
        return file_exists($this->getTemplatePath() . $template . ".tpl") ?: $parentCheck;
    }
    public function getThumbnailWebPath()
    {
        if (file_exists($this->getTemplatePath() . "thumbnail.gif")) {
            return \WHMCS\Utility\Environment\WebHelper::getBaseUrl() . "/" . static::$templateDirectory . "/" . $this->getName() . "/" . "thumbnail.gif";
        }
        return \WHMCS\Utility\Environment\WebHelper::getBaseUrl() . "/" . \App::get_admin_folder_name() . "/images/ordertplpreview.gif";
    }
}

?>