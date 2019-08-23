<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\ApplicationSupport\View\Traits;

abstract class TemplatePageTrait
{
    protected $templateVariables = NULL;
    protected $templateEngine = NULL;
    protected $templateDirectory = "";
    protected $templateName = "";
    protected abstract function factoryEngine();
    public function getTemplateVariables()
    {
        if (!$this->templateVariables) {
            $this->templateVariables = new \Symfony\Component\HttpFoundation\ParameterBag();
        }
        return $this->templateVariables;
    }
    public function setTemplateVariables($templateVariables)
    {
        if (!$templateVariables instanceof \Symfony\Component\HttpFoundation\ParameterBag) {
            if (!is_array($templateVariables)) {
                $templateVariables = array($templateVariables);
            }
            $templateVariables = new \Symfony\Component\HttpFoundation\ParameterBag($templateVariables);
        }
        $this->templateVariables = $templateVariables;
        return $this;
    }
    public function getTemplateEngine()
    {
        if (!$this->templateEngine) {
            $this->templateEngine = $this->factoryEngine();
        }
        return $this->templateEngine;
    }
    public function setTemplateEngine($templateEngine)
    {
        $this->templateEngine = $templateEngine;
        return $this;
    }
    public function getTemplateDirectory()
    {
        return $this->templateDirectory;
    }
    public function setTemplateDirectory($templateDirectory)
    {
        $this->templateDirectory = $templateDirectory;
        return $this;
    }
    public function getTemplateName()
    {
        return $this->templateName;
    }
    public function setTemplateName($templateName)
    {
        $this->templateName = $templateName;
        return $this;
    }
}

?>